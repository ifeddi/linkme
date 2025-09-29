<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Notification;
use App\Entity\Follow;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/api/users/search', name: 'api_users_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $entityManager): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        if ($q === '') {
            return new JsonResponse([]);
        }

        $repo = $entityManager->getRepository(User::class);
        $qb = $repo->createQueryBuilder('u')
            ->where('LOWER(u.name) LIKE :q')
            ->setParameter('q', strtolower($q) . '%')
            ->setMaxResults(5);

        $users = $qb->getQuery()->getResult();
        $results = array_map(function (User $u) {
            return [
                'id' => $u->getId(),
                'username' => $u->getName(),
                'profilePhoto' => $u->getProfilePhoto(),
            ];
        }, $users);

        return new JsonResponse($results);
    }

    #[Route('/api/users/{username}', name: 'api_users_public_profile', methods: ['GET'])]
    public function getPublicProfile(string $username, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['name' => $username]);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getUser();
        $isSelf = $current && $current->getId() === $user->getId();
        $followStatus = 'none';
        
        if ($current && !$isSelf) {
            $follow = $entityManager->getRepository(Follow::class)->findOneBy([
                'follower' => $current,
                'followed' => $user
            ]);
            
            if ($follow) {
                $followStatus = $follow->getStatus();
            }
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'username' => $user->getName(),
            'bio' => $user->getBio(),
            'profilePhoto' => $user->getProfilePhoto(),
            'postsCount' => $user->getPosts()->count(),
            'followersCount' => $user->getFollowers()->count(),
            'followingCount' => $user->getFollowing()->count(),
            'isSelf' => $isSelf,
            'followStatus' => $followStatus,
        ]);
    }

    #[Route('/api/user/followings', name: 'api_user_followings', methods: ['GET'])]
    public function getFollowings(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $conn = $entityManager->getConnection();
        $stmt = $conn->executeQuery(
            'SELECT u.id, u.name, u.profile_photo 
             FROM user u 
             INNER JOIN follow f ON u.id = f.followed_id 
             WHERE f.follower_id = :current_user_id AND f.status = "accepted"
             ORDER BY f.created_at DESC',
            ['current_user_id' => $user->getId()]
        );

        $followings = [];
        while ($row = $stmt->fetchAssociative()) {
            $followings[] = [
                'id' => $row['id'],
                'username' => $row['name'],
                'profilePhoto' => $row['profile_photo']
            ];
        }

        return new JsonResponse($followings);
    }

    #[Route('/api/users/{userId}/follow', name: 'api_users_toggle_follow', methods: ['POST'])]
    public function toggleFollow(int $userId, EntityManagerInterface $entityManager): Response
    {
        $current = $this->getUser();
        if (!$current) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        if ($current->getId() === $userId) {
            return new JsonResponse(['message' => 'Cannot follow yourself'], Response::HTTP_BAD_REQUEST);
        }

        $target = $entityManager->getRepository(User::class)->find($userId);
        if (!$target) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier s'il existe déjà une relation de follow
        $existingFollow = $entityManager->getRepository(Follow::class)->findOneBy([
            'follower' => $current,
            'followed' => $target
        ]);

        if ($existingFollow) {
            // Si la demande est pending, on peut l'annuler
            if ($existingFollow->getStatus() === 'pending') {
                $entityManager->remove($existingFollow);
                $entityManager->flush();
                $followStatus = 'none';
            } else {
                // Si c'est accepté, on unfollow
                $entityManager->remove($existingFollow);
                $entityManager->flush();
                $followStatus = 'none';
            }
        } else {
            // Créer une nouvelle demande de follow avec status pending
            $follow = new Follow();
            $follow->setFollower($current);
            $follow->setFollowed($target);
            $follow->setStatus('pending');
            
            $entityManager->persist($follow);
            
            // Créer une notification pour l'utilisateur suivi
            $notification = new Notification();
            $notification->setType('follow_request');
            $notification->setMessage($current->getName() . ' veut vous suivre');
            $notification->setRecipient($target);
            $notification->setActor($current);
            
            $entityManager->persist($notification);
            $entityManager->flush();
            
            $followStatus = 'pending';
        }

        return new JsonResponse([
            'followStatus' => $followStatus,
        ]);
    }

    #[Route('/api/users/{userId}/follow/accept', name: 'api_users_accept_follow', methods: ['POST'])]
    public function acceptFollow(int $userId, EntityManagerInterface $entityManager): Response
    {
        $current = $this->getUser();
        if (!$current) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $follower = $entityManager->getRepository(User::class)->find($userId);
        if (!$follower) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Trouver la demande de follow pending
        $follow = $entityManager->getRepository(Follow::class)->findOneBy([
            'follower' => $follower,
            'followed' => $current,
            'status' => 'pending'
        ]);

        if (!$follow) {
            return new JsonResponse(['message' => 'Follow request not found'], Response::HTTP_NOT_FOUND);
        }

        // Accepter la demande
        $follow->setStatus('accepted');
        $entityManager->flush();

        return new JsonResponse(['message' => 'Follow request accepted']);
    }

    #[Route('/api/users/{userId}/follow/reject', name: 'api_users_reject_follow', methods: ['POST'])]
    public function rejectFollow(int $userId, EntityManagerInterface $entityManager): Response
    {
        $current = $this->getUser();
        if (!$current) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $follower = $entityManager->getRepository(User::class)->find($userId);
        if (!$follower) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Trouver la demande de follow pending
        $follow = $entityManager->getRepository(Follow::class)->findOneBy([
            'follower' => $follower,
            'followed' => $current,
            'status' => 'pending'
        ]);

        if (!$follow) {
            return new JsonResponse(['message' => 'Follow request not found'], Response::HTTP_NOT_FOUND);
        }

        // Supprimer la demande
        $entityManager->remove($follow);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Follow request rejected']);
    }

    #[Route('/api/users/{userId}/followers', name: 'api_users_followers', methods: ['GET'])]
    public function getFollowers(int $userId, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $conn = $entityManager->getConnection();
        $stmt = $conn->executeQuery(
            'SELECT u.id, u.name, u.profile_photo 
             FROM user u 
             INNER JOIN follow f ON u.id = f.follower_id 
             WHERE f.followed_id = :userId AND f.status = "accepted"
             ORDER BY f.created_at DESC
             LIMIT 20',
            ['userId' => $userId]
        );

        $followers = [];
        while ($row = $stmt->fetchAssociative()) {
            $followers[] = [
                'id' => $row['id'],
                'username' => $row['name'],
                'profilePhoto' => $row['profile_photo']
            ];
        }

        // Compter le total
        $totalStmt = $conn->executeQuery(
            'SELECT COUNT(*) as total FROM follow WHERE followed_id = :userId AND status = "accepted"',
            ['userId' => $userId]
        );
        $total = $totalStmt->fetchAssociative()['total'];

        return new JsonResponse([
            'users' => $followers,
            'total' => $total
        ]);
    }

    #[Route('/api/users/{userId}/following', name: 'api_users_following', methods: ['GET'])]
    public function getFollowing(int $userId, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $conn = $entityManager->getConnection();
        $stmt = $conn->executeQuery(
            'SELECT u.id, u.name, u.profile_photo 
             FROM user u 
             INNER JOIN follow f ON u.id = f.followed_id 
             WHERE f.follower_id = :userId AND f.status = "accepted"
             ORDER BY f.created_at DESC
             LIMIT 20',
            ['userId' => $userId]
        );

        $following = [];
        while ($row = $stmt->fetchAssociative()) {
            $following[] = [
                'id' => $row['id'],
                'username' => $row['name'],
                'profilePhoto' => $row['profile_photo']
            ];
        }

        // Compter le total
        $totalStmt = $conn->executeQuery(
            'SELECT COUNT(*) as total FROM follow WHERE follower_id = :userId AND status = "accepted"',
            ['userId' => $userId]
        );
        $total = $totalStmt->fetchAssociative()['total'];

        return new JsonResponse([
            'users' => $following,
            'total' => $total
        ]);
    }
}


