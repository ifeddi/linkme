<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UploadController extends AbstractController
{
    #[Route('/api/upload/image', name: 'api_upload_image', methods: ['POST'])]
    public function uploadImage(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $uploadedFile = $request->files->get('image');
        
        if (!$uploadedFile) {
            return new JsonResponse(['message' => 'No image file provided'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($uploadedFile->getMimeType(), $allowedTypes)) {
            return new JsonResponse(['message' => 'Invalid file type. Only JPEG, PNG, GIF and WebP are allowed.'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier la taille (max 5MB)
        if ($uploadedFile->getSize() > 5 * 1024 * 1024) {
            return new JsonResponse(['message' => 'File too large. Maximum size is 5MB.'], Response::HTTP_BAD_REQUEST);
        }

        // Générer un nom de fichier unique
        $extension = $uploadedFile->guessExtension();
        $fileName = uniqid() . '_' . time() . '.' . $extension;
        
        // Créer le dossier uploads s'il n'existe pas
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Déplacer le fichier
        try {
            $uploadedFile->move($uploadDir, $fileName);
            
            // Retourner l'URL relative de l'image
            $imageUrl = '/uploads/' . $fileName;
            
            return new JsonResponse([
                'message' => 'Image uploaded successfully',
                'imageUrl' => $imageUrl
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Failed to upload image'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
