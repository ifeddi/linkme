<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DevDebugController extends AbstractController
{
    #[Route('/dev-debug/exception', name: 'dev_debug_exception', methods: ['GET'])]
    public function throwException(): Response
    {
        throw new \RuntimeException('DEV DEBUG: intentional exception for testing API Platform error output');
    }
}

