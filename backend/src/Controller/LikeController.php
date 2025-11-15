<?php

namespace App\Controller;

use App\Entity\Like;
use App\Entity\Post;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LikeController extends AbstractController
{
    #[Route('/api/posts/{postId}/like', name: 'api_posts_like', methods: ['POST'])]
    public function toggleLike(int $postId, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $post = $entityManager->getRepository(Post::class)->find($postId);

        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur a déjà liké ce post
        $existingLike = $entityManager->getRepository(Like::class)->findOneBy([
            'user' => $user,
            'post' => $post
        ]);

        if ($existingLike) {
            // Supprimer le like
            $entityManager->remove($existingLike);
            $entityManager->flush();
            
            return new JsonResponse([
                'liked' => false,
                'likesCount' => $post->getLikesCount()
            ]);
        } else {
            // Ajouter le like
            $like = new Like();
            $like->setUser($user);
            $like->setPost($post);
            
            $entityManager->persist($like);
            
            // Créer une notification si l'utilisateur qui like n'est pas l'auteur du post
            if ($user->getId() !== $post->getAuthor()->getId()) {
                $notification = new Notification();
                $notification->setType('like');
                $notification->setMessage($user->getName() . ' likes your post: "' . substr($post->getContent(), 0, 50) . (strlen($post->getContent()) > 50 ? '..."' : '"'));
                $notification->setRecipient($post->getAuthor());
                $notification->setActor($user);
                $notification->setPost($post);
                
                $entityManager->persist($notification);
            }
            
            $entityManager->flush();
            
            return new JsonResponse([
                'liked' => true,
                'likesCount' => $post->getLikesCount()
            ]);
        }
    }

    #[Route('/api/posts/{postId}/like', name: 'api_posts_like_status', methods: ['GET'])]
    public function getLikeStatus(int $postId, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $post = $entityManager->getRepository(Post::class)->find($postId);

        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur a liké ce post
        $existingLike = $entityManager->getRepository(Like::class)->findOneBy([
            'user' => $user,
            'post' => $post
        ]);

        return new JsonResponse([
            'liked' => $existingLike !== null,
            'likesCount' => $post->getLikesCount()
        ]);
    }

    #[Route('/api/posts/{postId}/likes', name: 'api_posts_likes_users', methods: ['GET'])]
    public function getLikesUsers(int $postId, EntityManagerInterface $entityManager): Response
    {
        $post = $entityManager->getRepository(Post::class)->find($postId);
        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $conn = $entityManager->getConnection();
        $stmt = $conn->executeQuery(
            'SELECT u.id, u.name, u.profile_photo 
             FROM user u 
             INNER JOIN `like` l ON u.id = l.user_id 
             WHERE l.post_id = :postId
             ORDER BY l.created_at DESC
             LIMIT 20',
            ['postId' => $postId]
        );

        $likes = [];
        while ($row = $stmt->fetchAssociative()) {
            $likes[] = [
                'id' => $row['id'],
                'username' => $row['name'],
                'profilePhoto' => $row['profile_photo']
            ];
        }

        // Compter le total
        $totalStmt = $conn->executeQuery(
            'SELECT COUNT(*) as total FROM `like` WHERE post_id = :postId',
            ['postId' => $postId]
        );
        $total = $totalStmt->fetchAssociative()['total'];

        return new JsonResponse([
            'users' => $likes,
            'total' => $total
        ]);
    }
}
