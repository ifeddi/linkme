<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Trouver ou créer une conversation entre deux utilisateurs
     */
    public function findOrCreateConversation(User $user1, User $user2): Conversation
    {
        // S'assurer que user1.id < user2.id pour la cohérence
        if ($user1->getId() > $user2->getId()) {
            [$user1, $user2] = [$user2, $user1];
        }

        $conversation = $this->findOneBy([
            'user1' => $user1,
            'user2' => $user2
        ]);

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setUser1($user1);
            $conversation->setUser2($user2);
            
            $this->getEntityManager()->persist($conversation);
            $this->getEntityManager()->flush();
        }

        return $conversation;
    }

    /**
     * Récupérer toutes les conversations d'un utilisateur avec les utilisateurs mutuellement suivis
     */
    public function findConversationsForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.user1', 'u1')
            ->leftJoin('c.user2', 'u2')
            ->leftJoin('c.user1', 'f1', 'WITH', 'f1.id = :userId')
            ->leftJoin('c.user2', 'f2', 'WITH', 'f2.id = :userId')
            ->where('c.user1 = :userId OR c.user2 = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('c.lastMessageAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupérer les conversations avec les utilisateurs mutuellement suivis
     * Crée automatiquement des conversations pour les utilisateurs mutuellement suivis
     */
    public function findMutualFollowConversations(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // D'abord, trouver tous les utilisateurs mutuellement suivis
        $mutualFollowsSql = '
            SELECT DISTINCT 
                CASE 
                    WHEN f1.followed_id = :userId THEN f1.follower_id
                    ELSE f1.followed_id
                END as mutual_user_id,
                CASE 
                    WHEN f1.followed_id = :userId THEN u1.id
                    ELSE u2.id
                END as other_user_id,
                CASE 
                    WHEN f1.followed_id = :userId THEN u1.name
                    ELSE u2.name
                END as other_user_name,
                CASE 
                    WHEN f1.followed_id = :userId THEN u1.profile_photo
                    ELSE u2.profile_photo
                END as other_user_photo,
                CASE 
                    WHEN f1.followed_id = :userId THEN u1.last_seen_at
                    ELSE u2.last_seen_at
                END as other_user_last_seen
            FROM follow f1
            LEFT JOIN user u1 ON f1.follower_id = u1.id
            LEFT JOIN user u2 ON f1.followed_id = u2.id
            WHERE f1.status = "accepted"
            AND (
                (f1.follower_id = :userId AND EXISTS (
                    SELECT 1 FROM follow f2 
                    WHERE f2.follower_id = f1.followed_id 
                    AND f2.followed_id = :userId 
                    AND f2.status = "accepted"
                ))
                OR
                (f1.followed_id = :userId AND EXISTS (
                    SELECT 1 FROM follow f2 
                    WHERE f2.follower_id = :userId 
                    AND f2.followed_id = f1.follower_id 
                    AND f2.status = "accepted"
                ))
            )
        ';

        $stmt = $conn->executeQuery($mutualFollowsSql, ['userId' => $user->getId()]);
        $mutualFollows = $stmt->fetchAllAssociative();

        $conversations = [];
        
        foreach ($mutualFollows as $mutualFollow) {
            $otherUserId = $mutualFollow['other_user_id'];
            
            // Trouver l'autre utilisateur
            $otherUser = $this->getEntityManager()->getRepository(\App\Entity\User::class)->find($otherUserId);
            if (!$otherUser) {
                continue; // Ignorer si l'utilisateur n'existe pas
            }
            
            // Trouver ou créer la conversation
            $conversation = $this->findOrCreateConversation($user, $otherUser);
            
            // Récupérer les détails de la conversation
            $conversationSql = '
                SELECT c.*, 
                       CASE 
                           WHEN c.user1_id = :userId THEN u2.id
                           ELSE u1.id
                       END as other_user_id,
                       CASE 
                           WHEN c.user1_id = :userId THEN u2.name
                           ELSE u1.name
                       END as other_user_name,
                       CASE 
                           WHEN c.user1_id = :userId THEN u2.profile_photo
                           ELSE u1.profile_photo
                       END as other_user_photo,
                       CASE 
                           WHEN c.user1_id = :userId THEN u2.last_seen_at
                           ELSE u1.last_seen_at
                       END as other_user_last_seen
                FROM conversation c
                LEFT JOIN user u1 ON c.user1_id = u1.id
                LEFT JOIN user u2 ON c.user2_id = u2.id
                WHERE c.id = :conversationId
            ';

            $stmt = $conn->executeQuery($conversationSql, [
                'userId' => $user->getId(),
                'conversationId' => $conversation->getId()
            ]);
            $conversationData = $stmt->fetchAssociative();
            
            if ($conversationData) {
                $conversations[] = $conversationData;
            }
        }

        // Trier par last_message_at DESC (les conversations sans messages en dernier)
        usort($conversations, function($a, $b) {
            $aTime = $a['last_message_at'] ? strtotime($a['last_message_at']) : 0;
            $bTime = $b['last_message_at'] ? strtotime($b['last_message_at']) : 0;
            return $bTime - $aTime;
        });

        return $conversations;
    }

    /**
     * Marquer les messages comme lus pour un utilisateur dans une conversation
     */
    public function markMessagesAsRead(Conversation $conversation, User $user): void
    {
        if ($conversation->getUser1()->getId() === $user->getId()) {
            $conversation->setUnreadUser1(0);
        } else {
            $conversation->setUnreadUser2(0);
        }
        
        $this->getEntityManager()->flush();
    }

    /**
     * Incrémenter le compteur de messages non lus
     */
    public function incrementUnreadCount(Conversation $conversation, User $recipient): void
    {
        if ($conversation->getUser1()->getId() === $recipient->getId()) {
            $conversation->setUnreadUser1($conversation->getUnreadUser1() + 1);
        } else {
            $conversation->setUnreadUser2($conversation->getUnreadUser2() + 1);
        }
        
        $this->getEntityManager()->flush();
    }
}
