<?php

namespace App\Tests\Unit\Service;

use App\Entity\Client;
use App\Entity\Hotel;
use App\Entity\Chambre;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Repository\ChambreRepository;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests unitaires pour le service de réservation
 */
class ReservationServiceTest extends TestCase
{
    private ReservationService $service;
    private ReservationRepository $reservationRepository;
    private ChambreRepository $chambreRepository;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->chambreRepository = $this->createMock(ChambreRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->service = new ReservationService(
            $this->reservationRepository,
            $this->chambreRepository,
            $this->entityManager,
            $this->validator
        );
    }

    /**
     * Test de création d'une réservation avec données valides
     */
    public function testCreateReservationWithValidData(): void
    {
        // Créer les données de test
        $client = new Client();
        $client->setEmail('test@test.com');
        $client->setNom('Test User');
        $client->setAdresse('123 Rue de Test');
        $client->setTelephone('0123456789');

        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setAdresse('456 Hotel Street');
        $hotel->setCategorie('***');

        $chambre = new Chambre();
        $chambre->setType('Double');
        $chambre->setEtage(1);
        $chambre->setNombreLits(2);
        $chambre->setHotel($hotel);
        
        // Définir l'ID en utilisant la réflexion (simuler une chambre persistée)
        $reflection = new \ReflectionClass($chambre);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($chambre, 1);

        $dateDebut = new \DateTime('2024-04-15');
        $dateFin = new \DateTime('2024-04-20');

        // Configurer les mocks
        $this->reservationRepository
            ->expects($this->any())
            ->method('findConflictingReservations')
            ->willReturn([]);

        // Mock pour validate() retournant une liste vide (pas d'erreurs)
        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violationList->method('count')->willReturn(0);
        
        $this->validator
            ->method('validate')
            ->willReturn($violationList);

        $this->entityManager->method('persist')->with();
        $this->entityManager->method('flush')->with();

        // Exécuter la méthode
        $reservation = $this->service->createReservation(
            $client,
            $hotel,
            $dateDebut,
            $dateFin,
            [$chambre]
        );

        // Vérifier les résultats
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertEquals('En attente', $reservation->getStatut());
        $this->assertEquals($client, $reservation->getClient());
        $this->assertEquals($hotel, $reservation->getHotel());
        $this->assertCount(1, $reservation->getChambres());
    }

    /**
     * Test que la création échoue sans chambre
     */
    public function testCreateReservationFailsWithoutChambre(): void
    {
        $client = new Client();
        $client->setEmail('test@test.com');
        $client->setNom('Test');

        $hotel = new Hotel();
        $hotel->setNom('Hotel');
        $hotel->setAdresse('Adresse');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Au moins une chambre doit être réservée');

        $this->service->createReservation(
            $client,
            $hotel,
            new \DateTime('2024-04-15'),
            new \DateTime('2024-04-20'),
            []
        );
    }

    /**
     * Test que la création échoue si les chambres sont en conflit
     */
    public function testCreateReservationFailsWithConflict(): void
    {
        $client = new Client();
        $client->setEmail('test@test.com');
        $client->setNom('Test');

        $hotel = new Hotel();
        $hotel->setNom('Hotel');

        $chambre = new Chambre();
        $chambre->setType('Double');
        $chambre->setEtage(1);
        $chambre->setNombreLits(2);
        
        // Définir l'ID en utilisant la réflexion (simuler une chambre persistée)
        $reflection = new \ReflectionClass($chambre);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($chambre, 1);

        $dateDebut = new \DateTime('2024-04-15');
        $dateFin = new \DateTime('2024-04-20');

        // Simuler un conflit de réservation
        $conflictingReservation = new Reservation();
        
        $this->reservationRepository
            ->expects($this->any())
            ->method('findConflictingReservations')
            ->willReturn([$conflictingReservation]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('n\'est pas disponible');

        $this->service->createReservation(
            $client,
            $hotel,
            $dateDebut,
            $dateFin,
            [$chambre]
        );
    }
}
