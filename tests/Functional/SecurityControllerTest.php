<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * SecurityControllerTest - Tests fonctionnels du contrôleur SecurityController
 * 
 * Tests de bout en bout pour vérifier:
 * - La page d'accueil se charge
 * - La page de login est accessible
 * - La page d'enregistrement est accessible
 * - Les redirections fonctionnent correctement
 */
class SecurityControllerTest extends WebTestCase
{
    public function testHomepageIsPublic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testRegisterPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/inscription');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testLogoutRedirectsToHomepage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/logout');

        $this->assertResponseRedirects('/');
    }

    public function testForgotPasswordPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mot-de-passe-perdu');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
