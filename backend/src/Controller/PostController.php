<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PostController extends AbstractController
{
    #[Route('/api/posts', name: 'api_posts_list', methods: ['GET'])]
    public function getPosts(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Get all posts from users that the current user follows (only accepted), plus their own posts
        $conn = $entityManager->getConnection();
        $stmt = $conn->executeQuery(
            'SELECT p.* FROM post p 
             WHERE p.author_id = :current_user_id 
             OR p.author_id IN (
                 SELECT followed_id FROM follow WHERE follower_id = :current_user_id AND status = "accepted"
             )
             ORDER BY p.created_at DESC',
            ['current_user_id' => $user->getId()]
        );

        $postsData = [];
        while ($row = $stmt->fetchAssociative()) {
            // Get the post entity to access relationships
            $post = $entityManager->getRepository(Post::class)->find($row['id']);
            if ($post) {
                $postsData[] = [
                    'id' => $post->getId(),
                    'content' => $post->getContent(),
                    'media' => $post->getMedia(),
                    'createdAt' => $post->getCreatedAt()->format('c'),
                    'author' => [
                        'id' => $post->getAuthor()->getId(),
                        'name' => $post->getAuthor()->getName(),
                        'email' => $post->getAuthor()->getEmail(),
                        'profilePhoto' => $post->getAuthor()->getProfilePhoto(),
                    ],
                    'likesCount' => $post->getLikesCount(),
                    'commentsCount' => $post->getCommentsCount(),
                ];
            }
        }

        return new JsonResponse($postsData);
    }

    #[Route('/api/posts', name: 'api_posts_create', methods: ['POST'])]
    public function createPost(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $content = isset($data['content']) ? trim((string) $data['content']) : '';
        $mediaBase64 = isset($data['media']) ? trim((string) $data['media']) : null;

        if ($content === '') {
            return new JsonResponse(['message' => 'Content is required'], Response::HTTP_BAD_REQUEST);
        }

        $post = new Post();
        $post->setContent($content);
        $post->setMedia($mediaBase64);
        $post->setAuthor($user);

        $entityManager->persist($post);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $post->getId(),
            'content' => $post->getContent(),
            'media' => $post->getMedia(),
            'createdAt' => $post->getCreatedAt()->format('c'),
            'author' => [
                'id' => $post->getAuthor()->getId(),
                'name' => $post->getAuthor()->getName(),
                'email' => $post->getAuthor()->getEmail(),
                'profilePhoto' => $post->getAuthor()->getProfilePhoto(),
            ],
            'likesCount' => $post->getLikesCount(),
            'commentsCount' => $post->getCommentsCount(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/posts/{id}', name: 'api_posts_get', methods: ['GET'])]
    public function getPost(int $id, EntityManagerInterface $entityManager): Response
    {
        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $post->getId(),
            'content' => $post->getContent(),
            'media' => $post->getMedia(),
            'createdAt' => $post->getCreatedAt()->format('c'),
            'author' => [
                'id' => $post->getAuthor()->getId(),
                'name' => $post->getAuthor()->getName(),
                'email' => $post->getAuthor()->getEmail(),
                'profilePhoto' => $post->getAuthor()->getProfilePhoto(),
            ],
            'likesCount' => $post->getLikesCount(),
            'commentsCount' => $post->getCommentsCount(),
        ]);
    }

    #[Route('/api/posts/{id}', name: 'api_posts_update', methods: ['PUT'])]
    public function updatePost(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est l'auteur du post
        if ($post->getAuthor()->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $content = isset($data['content']) ? trim((string) $data['content']) : '';
        $mediaBase64 = isset($data['media']) ? trim((string) $data['media']) : null;

        if ($content === '') {
            return new JsonResponse(['message' => 'Content is required'], Response::HTTP_BAD_REQUEST);
        }

        // Mettre à jour le contenu
        $post->setContent($content);
        
        // Mettre à jour le média (peut être null pour supprimer l'image)
        $post->setMedia($mediaBase64);

        $entityManager->flush();

        return new JsonResponse([
            'id' => $post->getId(),
            'content' => $post->getContent(),
            'media' => $post->getMedia(),
            'createdAt' => $post->getCreatedAt()->format('c'),
            'author' => [
                'id' => $post->getAuthor()->getId(),
                'name' => $post->getAuthor()->getName(),
                'email' => $post->getAuthor()->getEmail(),
                'profilePhoto' => $post->getAuthor()->getProfilePhoto(),
            ],
            'likesCount' => $post->getLikesCount(),
            'commentsCount' => $post->getCommentsCount(),
        ]);
    }

    #[Route('/api/posts/{id}', name: 'api_posts_delete', methods: ['DELETE'])]
    public function deletePost(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est l'auteur du post
        if ($post->getAuthor()->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($post);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Post deleted successfully']);
    }

    #[Route('/api/posts/user/{userId}', name: 'api_posts_user', methods: ['GET'])]
    public function getUserPosts(int $userId, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Only allow viewing posts if requester is the same user or has accepted follow
        $requester = $this->getUser();
        $canView = false;
        if ($requester && $requester->getId() === $user->getId()) {
            $canView = true;
        } else if ($requester) {
            $conn = $entityManager->getConnection();
            $stmt = $conn->executeQuery(
                'SELECT 1 FROM follow WHERE follower_id = :follower AND followed_id = :followed AND status = "accepted" LIMIT 1',
                ['follower' => $requester->getId(), 'followed' => $user->getId()]
            );
            $canView = (bool) $stmt->fetchOne();
        }

        if (!$canView) {
            return new JsonResponse([]);
        }

        $posts = $entityManager->getRepository(Post::class)->findBy(
            ['author' => $user],
            ['createdAt' => 'DESC']
        );

        $postsData = [];
        foreach ($posts as $post) {
            $postsData[] = [
                'id' => $post->getId(),
                'content' => $post->getContent(),
                'media' => $post->getMedia(),
                'createdAt' => $post->getCreatedAt()->format('c'),
                'author' => [
                    'id' => $post->getAuthor()->getId(),
                    'name' => $post->getAuthor()->getName(),
                    'email' => $post->getAuthor()->getEmail(),
                    'profilePhoto' => $post->getAuthor()->getProfilePhoto(),
                ],
                'likesCount' => $post->getLikesCount(),
                'commentsCount' => $post->getCommentsCount(),
            ];
        }

        return new JsonResponse($postsData);
    }
}
