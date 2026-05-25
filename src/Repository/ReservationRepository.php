<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Chercher par numéro de réservation
     */
    public function findByNumero(string $numero): ?Reservation
    {
        return $this->findOneBy(['numeroReservation' => $numero]);
    }

    /**
     * Chercher réservations d'un client
     */
    public function findByClientId(int $clientId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Chercher réservations d'un hôtel
     */
    public function findByHotelId(int $hotelId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.hotel = :hotelId')
            ->setParameter('hotelId', $hotelId)
            ->orderBy('r.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifier les chevauchements de dates
     */
    public function findConflictingReservations(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin, int $chambreId): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.chambres', 'c')
            ->where('c.id = :chambreId')
            ->andWhere(
                'r.dateDebut < :dateFin AND r.dateFin > :dateDebut'
            )
            ->andWhere('r.statut != :cancelled')
            ->setParameter('chambreId', $chambreId)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->setParameter('cancelled', 'Annulée')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifier les chevauchements de dates avec réservations CONFIRMÉES seulement
     * Une chambre est indisponible que si elle a une réservation CONFIRMÉE qui chevauche
     */
    public function findConfirmedConflictingReservations(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin, int $chambreId): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.chambres', 'c')
            ->where('c.id = :chambreId')
            ->andWhere(
                'r.dateDebut < :dateFin AND r.dateFin > :dateDebut'
            )
            ->andWhere('r.statut = :confirmed')
            ->setParameter('chambreId', $chambreId)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->setParameter('confirmed', 'Confirmée')
            ->getQuery()
            ->getResult();
    }
}
