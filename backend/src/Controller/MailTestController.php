<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailTestController extends AbstractController
{
    #[Route('/test-mail', name: 'test_mail')]
    public function index(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('admin-support@linkme.com')
            ->to('test@example.com')
            ->subject('Test Mailhog')
            ->text('Ceci est un test d\'envoi d\'email via Mailhog.');

        $mailer->send($email);

        return new Response('Email envoyé — vérifie Mailhog : http://localhost:8025');
    }
}
