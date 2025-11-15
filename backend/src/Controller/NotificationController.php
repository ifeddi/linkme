<?php

namespace App\Controller;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/api/notifications', name: 'api_notifications_list', methods: ['GET'])]
    public function getNotifications(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $notifications = $entityManager->getRepository(Notification::class)->findByRecipient($user);

        $notificationsData = [];
        foreach ($notifications as $notification) {
            $notificationsData[] = [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'message' => $notification->getMessage(),
                'isRead' => $notification->isRead(),
                'createdAt' => $notification->getCreatedAt()->format('c'),
                'timeAgo' => $notification->getTimeAgo(),
                'actor' => $notification->getActor() ? [
                    'id' => $notification->getActor()->getId(),
                    'name' => $notification->getActor()->getName(),
                    'profilePhoto' => $notification->getActor()->getProfilePhoto(),
                ] : null,
                'post' => $notification->getPost() ? [
                    'id' => $notification->getPost()->getId(),
                    'content' => $notification->getPost()->getContent(),
                ] : null,
            ];
        }

        return new JsonResponse($notificationsData);
    }

    #[Route('/api/notifications/unread-count', name: 'api_notifications_unread_count', methods: ['GET'])]
    public function getUnreadCount(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $count = $entityManager->getRepository(Notification::class)->countUnreadByRecipient($user);

        return new JsonResponse(['count' => $count]);
    }

    #[Route('/api/notifications/mark-all-read', name: 'api_notifications_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $entityManager->getRepository(Notification::class)->markAllAsReadByRecipient($user);

        return new JsonResponse(['message' => 'All notifications marked as read']);
    }
}
