<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Client;
use App\Entity\Reservation;
use App\Entity\Hotel;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'entité Client
 */
class ClientTest extends TestCase
{
    /**
     * Test création client
     */
    public function testCreateClient(): void
    {
        $client = new Client();
        $client->setEmail('test@example.com');
        $client->setNom('Jean Dupont');
        $client->setAdresse('123 Rue de Paris');
        $client->setTelephone('0123456789');
        $client->setPassword('hashed_password');

        $this->assertEquals('test@example.com', $client->getEmail());
        $this->assertEquals('Jean Dupont', $client->getNom());
        $this->assertEquals('123 Rue de Paris', $client->getAdresse());
        $this->assertEquals('0123456789', $client->getTelephone());
        $this->assertContains('ROLE_CLIENT', $client->getRoles());
    }

    /**
     * Test ajout réservation
     */
    public function testAddReservation(): void
    {
        $client = new Client();
        $hotel = new Hotel();
        $reservation = new Reservation();
        $reservation->setHotel($hotel);
        $reservation->setClient($client);
        $reservation->setDateDebut(new \DateTime());
        $reservation->setDateFin(new \DateTime('+1 day'));

        $client->addReservation($reservation);

        $this->assertCount(1, $client->getReservations());
        $this->assertTrue($client->getReservations()->contains($reservation));
    }
}
