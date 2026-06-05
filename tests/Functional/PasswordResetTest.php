<?php

namespace App\Tests\Functional;

use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use App\Service\PasswordResetService;
use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du flux mot de passe oublié.
 */
class PasswordResetTest extends WebTestCase
{
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureTestDatabase();
    }

    public function testForgotPasswordCreatesTokenInDatabase(): void
    {
        $client = static::createClient();
        $client->request('POST', '/mot-de-passe-perdu', [
            'email' => 'client1@example.com',
        ]);
        $this->assertResponseRedirects('/login');

        $tokenRepo = static::getContainer()->get(PasswordResetTokenRepository::class);
        $this->assertNotEmpty($tokenRepo->findAll());
    }

    public function testResetPasswordWithValidToken(): void
    {
        $container = static::getContainer();
        $passwordResetService = $container->get(PasswordResetService::class);
        $passwordResetService->requestPasswordReset('client1@example.com');

        $tokenRepo = $container->get(PasswordResetTokenRepository::class);
        $stored = $tokenRepo->findAll()[0];
        $plainToken = bin2hex(random_bytes(32));
        $stored->setTokenHash(hash('sha256', $plainToken));
        $container->get('doctrine')->getManager()->flush();

        $client = static::createClient();
        $client->request('POST', '/reinitialiser-mot-de-passe/' . $plainToken, [
            'token' => $plainToken,
            'password' => 'nouveauMotDePasse123',
            'password_confirm' => 'nouveauMotDePasse123',
        ]);
        $this->assertResponseRedirects('/login');

        $userRepo = $container->get(UserRepository::class);
        $user = $userRepo->findByEmail('client1@example.com');
        $this->assertTrue(
            $container->get('security.password_hasher')->isPasswordValid($user, 'nouveauMotDePasse123')
        );
    }

    public function testForgotPasswordAlwaysRedirectsWithSuccessMessage(): void
    {
        $client = static::createClient();
        $client->request('POST', '/mot-de-passe-perdu', [
            'email' => 'inexistant@example.com',
        ]);
        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.alert-success');
    }
}
