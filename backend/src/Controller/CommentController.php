<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{
    #[Route('/api/posts/{postId}/comments', name: 'api_posts_comments_list', methods: ['GET'])]
    public function getComments(int $postId, EntityManagerInterface $entityManager): Response
    {
        $post = $entityManager->getRepository(Post::class)->find($postId);

        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $comments = $entityManager->getRepository(Comment::class)->findBy(
            ['post' => $post],
            ['createdAt' => 'ASC']
        );

        $commentsData = [];
        foreach ($comments as $comment) {
            $commentsData[] = [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'createdAt' => $comment->getCreatedAt()->format('c'),
                'author' => [
                    'id' => $comment->getAuthor()->getId(),
                    'name' => $comment->getAuthor()->getName(),
                    'email' => $comment->getAuthor()->getEmail(),
                    'profilePhoto' => $comment->getAuthor()->getProfilePhoto(),
                ],
            ];
        }

        return new JsonResponse($commentsData);
    }

    #[Route('/api/posts/{postId}/comments', name: 'api_posts_comments_create', methods: ['POST'])]
    public function createComment(
        int $postId,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $post = $entityManager->getRepository(Post::class)->find($postId);

        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $content = isset($data['content']) ? trim((string) $data['content']) : '';

        if ($content === '') {
            return new JsonResponse(['message' => 'Content is required'], Response::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setAuthor($user);
        $comment->setPost($post);

        $entityManager->persist($comment);
        
        // Créer une notification si l'utilisateur qui commente n'est pas l'auteur du post
        if ($user->getId() !== $post->getAuthor()->getId()) {
            $notification = new Notification();
            $notification->setType('comment');
            $notification->setMessage($user->getName() . ' comments your post: "' . substr($post->getContent(), 0, 50) . (strlen($post->getContent()) > 50 ? '..."' : '"'));
            $notification->setRecipient($post->getAuthor());
            $notification->setActor($user);
            $notification->setPost($post);
            
            $entityManager->persist($notification);
        }
        
        $entityManager->flush();

        return new JsonResponse([
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format('c'),
            'author' => [
                'id' => $comment->getAuthor()->getId(),
                'name' => $comment->getAuthor()->getName(),
                'email' => $comment->getAuthor()->getEmail(),
                'profilePhoto' => $comment->getAuthor()->getProfilePhoto(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/comments/{id}', name: 'api_comments_delete', methods: ['DELETE'])]
    public function deleteComment(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $comment = $entityManager->getRepository(Comment::class)->find($id);

        if (!$comment) {
            return new JsonResponse(['message' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est l'auteur du commentaire
        if ($comment->getAuthor()->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($comment);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Comment deleted successfully']);
    }
}
