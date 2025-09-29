<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AuthController extends AbstractController
{
    #[Route('/api/user/register', name: 'api_user_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer
    ): Response {
        $data = json_decode($request->getContent(), true) ?? [];

        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        $plainPassword = isset($data['password']) ? (string) $data['password'] : '';
        $username = isset($data['name']) ? trim((string) $data['name']) : '';

        if ($email === '' || $plainPassword === '' || $username === '') {
            return new JsonResponse(['message' => 'Email, password and username are required'], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already exists
        $existing = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return new JsonResponse(['message' => 'User already exists'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($email);

        // Ensure unique username
        $existingUsername = $entityManager->getRepository(User::class)->findOneBy(['name' => $username]);
        if ($existingUsername) {
            return new JsonResponse(['message' => 'Username already exists'], Response::HTTP_CONFLICT);
        }
        $user->setName($username);
        $user->setBio('');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false); // User not verified by default

        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // Generate verification token
        $verificationToken = bin2hex(random_bytes(32));
        $user->setVerificationToken($verificationToken);

        $entityManager->persist($user);
        $entityManager->flush();

        // Send verification email
        $this->sendVerificationEmail($mailer, $user, $verificationToken);

        return new JsonResponse([
            'message' => 'User created successfully. Please check your email to verify your account.',
            'email' => $email
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/user/profile', name: 'api_user_profile', methods: ['GET'])]
    public function getProfile(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'bio' => $user->getBio(),
            'profilePhoto' => $user->getProfilePhoto(),
            'postsCount' => $user->getPosts()->count(),
            'followersCount' => $user->getFollowers()->count(),
            'followingCount' => $user->getFollowing()->count()
        ]);
    }

    #[Route('/api/user/profile', name: 'api_user_update_profile', methods: ['PUT'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $bio = isset($data['bio']) ? trim((string) $data['bio']) : '';
        $profilePhoto = isset($data['profilePhoto']) ? trim((string) $data['profilePhoto']) : null;

        if ($email === '') {
            return new JsonResponse(['message' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        if ($email !== $user->getEmail()) {
            $existing = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing) {
                return new JsonResponse(['message' => 'Email already exists'], Response::HTTP_CONFLICT);
            }
        }

        // Vérifier si le nom d'utilisateur est déjà utilisé par un autre utilisateur
        if ($name !== '' && $name !== $user->getName()) {
            $existing = $entityManager->getRepository(User::class)->findOneBy(['name' => $name]);
            if ($existing) {
                return new JsonResponse(['message' => 'Username already exists'], Response::HTTP_CONFLICT);
            }
        }

        // Mettre à jour les données
        $user->setEmail($email);
        $user->setName($name);
        $user->setBio($bio);
        $user->setProfilePhoto($profilePhoto);

        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'bio' => $user->getBio(),
                'profilePhoto' => $user->getProfilePhoto(),
                'postsCount' => $user->getPosts()->count(),
                'followersCount' => $user->getFollowers()->count(),
                'followingCount' => $user->getFollowing()->count()
            ]
        ]);
    }

    #[Route('/api/user/verify-email', name: 'api_user_verify_email', methods: ['GET'])]
    public function verifyEmail(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->query->get('token');

        if (!$token) {
            return new JsonResponse(['message' => 'Token is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return new JsonResponse(['message' => 'Invalid or expired token'], Response::HTTP_BAD_REQUEST);
        }

        if ($user->isVerified()) {
            return new JsonResponse(['message' => 'Email already verified'], Response::HTTP_BAD_REQUEST);
        }

        // Verify the user
        $user->setIsVerified(true);
        $user->setVerificationToken(null); // Clear the token
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Email verified successfully',
            'email' => $user->getEmail()
        ]);
    }

    #[Route('/api/user/forgot-password', name: 'api_user_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = isset($data['email']) ? trim((string) $data['email']) : '';

        if ($email === '') {
            return new JsonResponse(['message' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            // Don't reveal if email exists or not for security
            return new JsonResponse(['message' => 'If the email exists, a reset link has been sent'], Response::HTTP_OK);
        }

        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $user->setResetPasswordToken($resetToken);
        $user->setResetPasswordExpiresAt(new \DateTimeImmutable('+30 minutes')); // Token expires in 30 minutes

        $entityManager->flush();

        // Send reset email
        $this->sendPasswordResetEmail($mailer, $user, $resetToken);

        return new JsonResponse(['message' => 'If the email exists, a reset link has been sent'], Response::HTTP_OK);
    }

    #[Route('/api/user/reset-password', name: 'api_user_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $data = json_decode($request->getContent(), true) ?? [];
        $token = isset($data['token']) ? trim((string) $data['token']) : '';
        $newPassword = isset($data['password']) ? (string) $data['password'] : '';

        if ($token === '' || $newPassword === '') {
            return new JsonResponse(['message' => 'Token and password are required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['resetPasswordToken' => $token]);

        if (!$user) {
            return new JsonResponse(['message' => 'Invalid or expired token'], Response::HTTP_BAD_REQUEST);
        }

        // Check if token is expired
        if ($user->getResetPasswordExpiresAt() < new \DateTimeImmutable()) {
            return new JsonResponse(['message' => 'Token has expired'], Response::HTTP_BAD_REQUEST);
        }

        // Update password
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->setResetPasswordToken(null);
        $user->setResetPasswordExpiresAt(null);

        $entityManager->flush();

        return new JsonResponse(['message' => 'Password reset successfully'], Response::HTTP_OK);
    }

    private function sendVerificationEmail(MailerInterface $mailer, User $user, string $token): void
    {
        $verificationUrl = "http://localhost:5173/verify-email?token=" . $token;

        $email = (new Email())
            ->from('admin-support@linkme.com')
            ->to($user->getEmail())
            ->subject('Verify your email address - LinkMe')
            ->html("
                <h2>Welcome to LinkMe!</h2>
                <p>Hi {$user->getName()},</p>
                <p>Thank you for registering with LinkMe. Please click the link below to verify your email address:</p>
                <p><a href=\"{$verificationUrl}\" style=\"background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Verify Email</a></p>
                <p>Or copy and paste this link in your browser:</p>
                <p>{$verificationUrl}</p>
                <p>If you didn't create an account, please ignore this email.</p>
                <p>Best regards,<br>The LinkMe Team</p>
            ");

        $mailer->send($email);
    }

    private function sendPasswordResetEmail(MailerInterface $mailer, User $user, string $token): void
    {
        $resetUrl = "http://localhost:5173/reset-password?token=" . $token;

        $email = (new Email())
            ->from('admin-support@linkme.com')
            ->to($user->getEmail())
            ->subject('Reset your password - LinkMe')
            ->html("
                <h2>Password Reset Request</h2>
                <p>Hi {$user->getName()},</p>
                <p>You requested to reset your password. Click the link below to reset it:</p>
                <p><a href=\"{$resetUrl}\" style=\"background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Reset Password</a></p>
                <p>Or copy and paste this link in your browser:</p>
                <p>{$resetUrl}</p>
                <p>This link will expire in 30 minutes.</p>
                <p>If you didn't request this, please ignore this email.</p>
                <p>Best regards,<br>The LinkMe Team</p>
            ");

        $mailer->send($email);
    }
}


