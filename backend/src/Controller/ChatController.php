<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ChatController extends AbstractController
{
    #[Route('/api/chat/conversations', name: 'api_chat_conversations', methods: ['GET'])]
    public function getConversations(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $conversationRepo = $entityManager->getRepository(Conversation::class);
        $conversations = $conversationRepo->findMutualFollowConversations($user);

        $conversationsData = [];
        foreach ($conversations as $conv) {
            $otherUserId = $conv['other_user_id'];
            $otherUserName = $conv['other_user_name'];
            $otherUserPhoto = $conv['other_user_photo'];
            $lastSeen = $conv['other_user_last_seen'];
            
            // Déterminer le statut en ligne
            $isOnline = false;
            if ($lastSeen) {
                $lastSeenTime = new \DateTimeImmutable($lastSeen);
                $now = new \DateTimeImmutable();
                $diff = $now->getTimestamp() - $lastSeenTime->getTimestamp();
                $isOnline = $diff < 180; // 3 minutes
            }

            $conversationsData[] = [
                'id' => $conv['id'],
                'otherUser' => [
                    'id' => $otherUserId,
                    'name' => $otherUserName,
                    'profilePhoto' => $otherUserPhoto,
                    'isOnline' => $isOnline,
                    'lastSeenAt' => $lastSeen
                ],
                'lastMessage' => [
                    'preview' => $conv['last_message_preview'] ?: 'Start the conversation',
                    'createdAt' => $conv['last_message_at']
                ],
                'unreadCount' => $conv['user1_id'] == $user->getId() ? $conv['unread_user1'] : $conv['unread_user2']
            ];
        }

        return new JsonResponse($conversationsData);
    }

    #[Route('/api/chat/conversations/{conversationId}/messages', name: 'api_chat_messages', methods: ['GET'])]
    public function getMessages(int $conversationId, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $conversation = $entityManager->getRepository(Conversation::class)->find($conversationId);
        if (!$conversation) {
            return new JsonResponse(['message' => 'Conversation not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur fait partie de cette conversation
        if ($conversation->getUser1()->getId() !== $user->getId() && 
            $conversation->getUser2()->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $messageRepo = $entityManager->getRepository(Message::class);
        $messages = $messageRepo->findRecentMessages($conversation, 50);

        $messagesData = [];
        foreach ($messages as $message) {
            $messagesData[] = [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'isSticker' => $message->isSticker(),
                'stickerCode' => $message->getStickerCode(),
                'createdAt' => $message->getCreatedAt()->format('c'),
                'readAt' => $message->getReadAt()?->format('c'),
                'sender' => [
                    'id' => $message->getSender()->getId(),
                    'name' => $message->getSender()->getName(),
                    'profilePhoto' => $message->getSender()->getProfilePhoto()
                ],
                'isOwn' => $message->getSender()->getId() === $user->getId()
            ];
        }

        // Marquer les messages comme lus
        $conversationRepo = $entityManager->getRepository(Conversation::class);
        $conversationRepo->markMessagesAsRead($conversation, $user);

        return new JsonResponse(array_reverse($messagesData)); // Plus récent en premier
    }

    #[Route('/api/chat/conversations/{conversationId}/messages', name: 'api_chat_send_message', methods: ['POST'])]
    public function sendMessage(
        int $conversationId,
        Request $request,
        EntityManagerInterface $entityManager,
        HubInterface $hub = null
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        error_log("Attempting to send message to conversation {$conversationId} by user {$user->getId()}");

        $conversation = $entityManager->getRepository(Conversation::class)->find($conversationId);
        if (!$conversation) {
            error_log("Conversation {$conversationId} not found");
            return new JsonResponse(['message' => 'Conversation not found'], Response::HTTP_NOT_FOUND);
        }

        error_log("Conversation found: user1={$conversation->getUser1()->getId()}, user2={$conversation->getUser2()->getId()}");

        // Vérifier que l'utilisateur fait partie de cette conversation
        if ($conversation->getUser1()->getId() !== $user->getId() && 
            $conversation->getUser2()->getId() !== $user->getId()) {
            error_log("Access denied: user {$user->getId()} not in conversation {$conversationId}");
            return new JsonResponse(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $content = trim($data['content'] ?? '');
        $isSticker = $data['isSticker'] ?? false;
        $stickerCode = $data['stickerCode'] ?? null;

        if (empty($content)) {
            return new JsonResponse(['message' => 'Content is required'], Response::HTTP_BAD_REQUEST);
        }

        $message = new Message();
        $message->setConversation($conversation);
        $message->setSender($user);
        $message->setContent($content);
        $message->setIsSticker($isSticker);
        $message->setStickerCode($stickerCode);

        // Mettre à jour le last_seen_at de l'utilisateur qui envoie le message
        $user->setLastSeenAt(new \DateTimeImmutable());

        $entityManager->persist($message);

        // Mettre à jour la conversation
        $conversation->setLastMessageAt(new \DateTimeImmutable());
        $conversation->setLastMessagePreview($isSticker ? 'Sticker' : substr($content, 0, 50));

        // Incrémenter le compteur de messages non lus pour l'autre utilisateur
        $conversationRepo = $entityManager->getRepository(Conversation::class);
        $otherUser = $conversation->getUser1()->getId() === $user->getId() 
            ? $conversation->getUser2() 
            : $conversation->getUser1();
        $conversationRepo->incrementUnreadCount($conversation, $otherUser);

        $entityManager->flush();

        // Publier via Mercure (si disponible)
        if ($hub) {
            try {
                $update = new Update(
                    "chat/{$conversationId}",
                    json_encode([
                        'type' => 'message',
                        'message' => [
                            'id' => $message->getId(),
                            'content' => $message->getContent(),
                            'isSticker' => $message->isSticker(),
                            'stickerCode' => $message->getStickerCode(),
                            'createdAt' => $message->getCreatedAt()->format('c'),
                            'sender' => [
                                'id' => $message->getSender()->getId(),
                                'name' => $message->getSender()->getName(),
                                'profilePhoto' => $message->getSender()->getProfilePhoto()
                            ],
                            'isOwn' => false
                        ]
                    ])
                );
                $hub->publish($update);
            } catch (\Exception $e) {
                // Ignorer les erreurs Mercure pour l'instant
                error_log('Mercure publish error: ' . $e->getMessage());
            }
        } else {
            error_log('Mercure hub not available - message sent without real-time update');
        }

        return new JsonResponse([
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'isSticker' => $message->isSticker(),
            'stickerCode' => $message->getStickerCode(),
            'createdAt' => $message->getCreatedAt()->format('c'),
            'sender' => [
                'id' => $message->getSender()->getId(),
                'name' => $message->getSender()->getName(),
                'profilePhoto' => $message->getSender()->getProfilePhoto()
            ],
            'isOwn' => true
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/chat/conversations/{conversationId}/read', name: 'api_chat_mark_read', methods: ['POST'])]
    public function markAsRead(int $conversationId, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $conversation = $entityManager->getRepository(Conversation::class)->find($conversationId);
        if (!$conversation) {
            return new JsonResponse(['message' => 'Conversation not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur fait partie de cette conversation
        if ($conversation->getUser1()->getId() !== $user->getId() && 
            $conversation->getUser2()->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $conversationRepo = $entityManager->getRepository(Conversation::class);
        $conversationRepo->markMessagesAsRead($conversation, $user);

        return new JsonResponse(['message' => 'Messages marked as read']);
    }

    #[Route('/api/chat/stickers', name: 'api_chat_stickers', methods: ['GET'])]
    public function getStickers(): Response
    {
        $stickers = [
            ['code' => '😀', 'name' => 'Grinning Face'],
            ['code' => '😃', 'name' => 'Grinning Face with Big Eyes'],
            ['code' => '😄', 'name' => 'Grinning Face with Smiling Eyes'],
            ['code' => '😁', 'name' => 'Beaming Face with Smiling Eyes'],
            ['code' => '😆', 'name' => 'Grinning Squinting Face'],
            ['code' => '😅', 'name' => 'Grinning Face with Sweat'],
            ['code' => '🤣', 'name' => 'Rolling on the Floor Laughing'],
            ['code' => '😂', 'name' => 'Face with Tears of Joy'],
            ['code' => '🙂', 'name' => 'Slightly Smiling Face'],
            ['code' => '🙃', 'name' => 'Upside-Down Face'],
            ['code' => '😉', 'name' => 'Winking Face'],
            ['code' => '😊', 'name' => 'Smiling Face with Smiling Eyes'],
            ['code' => '😇', 'name' => 'Smiling Face with Halo'],
            ['code' => '🥰', 'name' => 'Smiling Face with Hearts'],
            ['code' => '😍', 'name' => 'Smiling Face with Heart-Eyes'],
            ['code' => '🤩', 'name' => 'Star-Struck'],
            ['code' => '😘', 'name' => 'Face Blowing a Kiss'],
            ['code' => '😗', 'name' => 'Kissing Face'],
            ['code' => '😚', 'name' => 'Kissing Face with Closed Eyes'],
            ['code' => '😙', 'name' => 'Kissing Face with Smiling Eyes']
        ];

        return new JsonResponse($stickers);
    }

    #[Route('/api/chat/online-status', name: 'api_chat_online_status', methods: ['POST'])]
    public function updateOnlineStatus(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $user->setLastSeenAt(new \DateTimeImmutable());
        $entityManager->flush();

        return new JsonResponse(['message' => 'Online status updated']);
    }
}
