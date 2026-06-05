<?php

namespace App\Tests\Unit\Service;

use App\Entity\Client;
use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use App\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests unitaires du service de réinitialisation de mot de passe.
 */
class PasswordResetServiceTest extends TestCase
{
    public function testValidateTokenReturnsNullForEmptyToken(): void
    {
        $service = $this->createService();
        $this->assertNull($service->validateToken(''));
    }

    public function testResetPasswordThrowsWhenTokenInvalid(): void
    {
        $tokenRepo = $this->createMock(PasswordResetTokenRepository::class);
        $tokenRepo->method('findValidByTokenHash')->willReturn(null);

        $service = $this->createService(tokenRepository: $tokenRepo);

        $this->expectException(\InvalidArgumentException::class);
        $service->resetPassword('invalid-token-that-is-long-enough-32chars!!', 'newpassword123');
    }

    public function testResetPasswordUpdatesUserPassword(): void
    {
        $user = new Client();
        $user->setEmail('client@test.com');
        $user->setNom('Test');
        $user->setAdresse('addr');
        $user->setTelephone('0123456789');
        $user->setPassword('old');

        $plainToken = bin2hex(random_bytes(32));
        $resetToken = new PasswordResetToken();
        $resetToken->setUser($user);
        $resetToken->setTokenHash(hash('sha256', $plainToken));
        $resetToken->setExpiresAt(new \DateTimeImmutable('+1 hour'));

        $tokenRepo = $this->createMock(PasswordResetTokenRepository::class);
        $tokenRepo->method('findValidByTokenHash')
            ->with(hash('sha256', $plainToken))
            ->willReturn($resetToken);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'newpassword123')
            ->willReturn('hashed-new');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = $this->createService(
            tokenRepository: $tokenRepo,
            entityManager: $em,
            passwordHasher: $hasher
        );

        $service->resetPassword($plainToken, 'newpassword123');
        $this->assertSame('hashed-new', $user->getPassword());
        $this->assertNotNull($resetToken->getUsedAt());
    }

    private function createService(
        ?UserRepository $userRepository = null,
        ?PasswordResetTokenRepository $tokenRepository = null,
        ?EntityManagerInterface $entityManager = null,
        ?UserPasswordHasherInterface $passwordHasher = null,
    ): PasswordResetService {
        $userRepository ??= $this->createMock(UserRepository::class);
        $tokenRepository ??= $this->createMock(PasswordResetTokenRepository::class);
        $entityManager ??= $this->createMock(EntityManagerInterface::class);
        $passwordHasher ??= $this->createMock(UserPasswordHasherInterface::class);

        return new PasswordResetService(
            $userRepository,
            $tokenRepository,
            $entityManager,
            $this->createMock(MailerInterface::class),
            $passwordHasher,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(ValidatorInterface::class),
        );
    }
}
