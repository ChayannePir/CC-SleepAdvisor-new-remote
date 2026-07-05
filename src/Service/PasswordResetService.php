<?php

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Gestion du flux « mot de passe oublié » : jeton, e-mail, réinitialisation.
 */
class PasswordResetService
{
    private const TOKEN_TTL_HOURS = 1;

    public function __construct(
        private UserRepository $userRepository,
        private PasswordResetTokenRepository $tokenRepository,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UserPasswordHasherInterface $passwordHasher,
        private UrlGeneratorInterface $urlGenerator,
        private ValidatorInterface $validator,
        private string $mailerFrom = 'noreply@sleepadvisor.com',
    ) {
    }

    /**
     * Demande de réinitialisation. Ne révèle pas si l'e-mail existe (sécurité).
     */
    public function requestPasswordReset(string $email): void
    {
        $email = trim($email);
        $violations = $this->validator->validate($email, [new Assert\NotBlank(), new Assert\Email()]);
        if (count($violations) > 0) {
            return;
        }

        $user = $this->userRepository->findByEmail($email);
        if ($user === null || !$user->isActive()) {
            return;
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $this->tokenRepository->invalidateActiveTokensForUser($user);

        $resetToken = new PasswordResetToken();
        $resetToken->setUser($user)
            ->setTokenHash($tokenHash)
            ->setExpiresAt(new \DateTimeImmutable('+' . self::TOKEN_TTL_HOURS . ' hours'));

        $this->entityManager->persist($resetToken);
        $this->entityManager->flush();

        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $plainToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $message = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe SleepAdvisor')
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'resetUrl' => $resetUrl,
                'user' => $user,
                'expiresHours' => self::TOKEN_TTL_HOURS,
            ]);

        $this->mailer->send($message);
    }

    /**
     * Valide un jeton brut (chaîne URL).
     */
    public function validateToken(string $plainToken): ?PasswordResetToken
    {
        if ($plainToken === '' || strlen($plainToken) < 32) {
            return null;
        }

        return $this->tokenRepository->findValidByTokenHash(hash('sha256', $plainToken));
    }

    /**
     * Applique un nouveau mot de passe après validation du jeton.
     *
     * @throws \InvalidArgumentException si mot de passe trop court
     */
    public function resetPassword(string $plainToken, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        $resetToken = $this->validateToken($plainToken);
        if ($resetToken === null) {
            throw new \InvalidArgumentException('Ce lien de réinitialisation est invalide ou a expiré.');
        }

        $user = $resetToken->getUser();
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('Utilisateur introuvable.');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $resetToken->markAsUsed();
        $this->entityManager->flush();
    }
}
