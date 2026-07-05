<?php

namespace App\Tests\Unit\Service;

use App\Entity\Client;
use App\Entity\Hotel;
use App\Entity\Chambre;
use App\Entity\Reservation;
use App\Exception\ChambreNotAvailableException;
use App\Exception\ReservationValidationException;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests unitaires du service de réservation (disponibilité et création).
 */
class ReservationServiceTest extends TestCase
{
    private ReservationService $service;
    private ReservationRepository $reservationRepository;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->service = new ReservationService(
            $this->reservationRepository,
            $this->entityManager,
            $this->validator
        );
    }

    private function createChambreWithId(Hotel $hotel, int $id = 1): Chambre
    {
        $chambre = new Chambre();
        $chambre->setType('Double');
        $chambre->setEtage(1);
        $chambre->setNombreLits(2);
        $chambre->setHotel($hotel);

        $reflection = new \ReflectionClass($chambre);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($chambre, $id);

        return $chambre;
    }

    private function createHotelWithId(int $id = 1): Hotel
    {
        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setAdresse('456 Hotel Street');
        $hotel->setCategorie('***');

        $reflection = new \ReflectionClass($hotel);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($hotel, $id);

        return $hotel;
    }

    public function testCreateReservationWithValidData(): void
    {
        $client = new Client();
        $client->setEmail('test@test.com');
        $client->setNom('Test User');
        $client->setAdresse('123 Rue de Test');
        $client->setTelephone('0123456789');

        $hotel = $this->createHotelWithId();
        $chambre = $this->createChambreWithId($hotel);

        $this->reservationRepository
            ->expects($this->once())
            ->method('findActiveConflictingReservations')
            ->willReturn([]);

        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violationList->method('count')->willReturn(0);
        $this->validator->method('validate')->willReturn($violationList);

        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $reservation = $this->service->createReservation(
            $client,
            $hotel,
            new \DateTime('2024-04-15'),
            new \DateTime('2024-04-20'),
            [$chambre]
        );

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertEquals('En attente', $reservation->getStatut());
        $this->assertCount(1, $reservation->getChambres());
    }

    public function testIsChambreAvailableReturnsTrueWhenNoConflict(): void
    {
        $hotel = $this->createHotelWithId();
        $chambre = $this->createChambreWithId($hotel);

        $this->reservationRepository->method('findActiveConflictingReservations')->willReturn([]);

        $this->assertTrue($this->service->isChambreAvailable(
            $chambre,
            new \DateTime('2024-04-15'),
            new \DateTime('2024-04-20')
        ));
    }

    public function testIsChambreAvailableReturnsFalseWhenConflict(): void
    {
        $hotel = $this->createHotelWithId();
        $chambre = $this->createChambreWithId($hotel);

        $this->reservationRepository->method('findActiveConflictingReservations')
            ->willReturn([new Reservation()]);

        $this->assertFalse($this->service->isChambreAvailable(
            $chambre,
            new \DateTime('2024-04-15'),
            new \DateTime('2024-04-20')
        ));
    }

    public function testAreChambresAvailableWithMultipleChambres(): void
    {
        $hotel = $this->createHotelWithId();
        $c1 = $this->createChambreWithId($hotel, 1);
        $c2 = $this->createChambreWithId($hotel, 2);

        $this->reservationRepository->method('findActiveConflictingReservations')->willReturn([]);

        $this->assertTrue($this->service->areChambresAvailable(
            [$c1, $c2],
            new \DateTime('2025-06-10'),
            new \DateTime('2025-06-15')
        ));
    }

    public function testCreateReservationFailsWithoutChambre(): void
    {
        $client = new Client();
        $client->setEmail('test@test.com');
        $client->setNom('Test');

        $hotel = $this->createHotelWithId();

        $this->expectException(ReservationValidationException::class);
        $this->service->createReservation(
            $client,
            $hotel,
            new \DateTime('2024-04-15'),
            new \DateTime('2024-04-20'),
            []
        );
    }

    public function testCreateReservationFailsWithConflict(): void
    {
        $client = new Client();
        $client->setEmail('test@test.com');
        $client->setNom('Test');

        $hotel = $this->createHotelWithId();
        $chambre = $this->createChambreWithId($hotel);

        $this->reservationRepository->method('findActiveConflictingReservations')
            ->willReturn([new Reservation()]);

        $this->expectException(ChambreNotAvailableException::class);

        $this->service->createReservation(
            $client,
            $hotel,
            new \DateTime('2024-04-15'),
            new \DateTime('2024-04-20'),
            [$chambre]
        );
    }
}
