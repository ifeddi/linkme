<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StaticController extends AbstractController
{
    #[Route('/uploads/{filename}', name: 'serve_uploaded_file', methods: ['GET'])]
    public function serveUploadedFile(string $filename): Response
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        $filePath = $uploadDir . '/' . $filename;
        
        // Vérifier que le fichier existe
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }
        
        // Vérifier que c'est bien dans le dossier uploads (sécurité)
        $realPath = realpath($filePath);
        $realUploadDir = realpath($uploadDir);
        
        if (!$realPath || strpos($realPath, $realUploadDir) !== 0) {
            throw $this->createNotFoundException('File not found');
        }
        
        // Déterminer le type MIME
        $mimeType = mime_content_type($filePath);
        if (!$mimeType) {
            $mimeType = 'application/octet-stream';
        }
        
        return new BinaryFileResponse($filePath, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000', // Cache pour 1 an
        ]);
    }
}
