<?php

namespace App\Tests\Functional;

use App\Entity\Client;
use App\Repository\ChambreRepository;
use App\Repository\UserRepository;
use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Valide le tunnel de réservation public de bout en bout.
 */
class ReservationTunnelTest extends WebTestCase
{
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureTestDatabase();
    }

    public function testFullReservationTunnel(): void
    {
        $client = static::createClient();
        $dateDebut = (new \DateTime('+14 days'))->format('Y-m-d');
        $dateFin = (new \DateTime('+17 days'))->format('Y-m-d');

        /** @var UserRepository $userRepo */
        $userRepo = static::getContainer()->get(UserRepository::class);
        $user = $userRepo->findByEmail('client1@example.com');
        $this->assertInstanceOf(Client::class, $user);
        $client->loginUser($user);

        $client->request('POST', '/recherche', [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
        ]);
        $this->assertResponseIsSuccessful();

        /** @var ChambreRepository $chambreRepo */
        $chambreRepo = static::getContainer()->get(ChambreRepository::class);
        $chambres = $chambreRepo->findAll();
        $this->assertNotEmpty($chambres);

        $client->request('POST', '/recherche/chambre/' . $chambres[0]->getId() . '/reserver', [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
        ]);
        $this->assertResponseRedirects('/recherche/panier');
        $client->followRedirect();

        $client->request('POST', '/recherche/panier/checkout', [
            'email' => $user->getEmail(),
            'telephone' => $user->getTelephone(),
        ]);
        $this->assertResponseRedirects();
    }

    public function testCheckoutRedirectsWhenBasketEmpty(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get(UserRepository::class);
        $user = $userRepo->findByEmail('client1@example.com');
        $client->loginUser($user);

        $client->request('GET', '/recherche/panier/checkout');
        $this->assertResponseRedirects('/recherche');
    }
}
