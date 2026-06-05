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
     * Recherche partielle par numéro (LIKE).
     *
     * @return Reservation[]
     */
    public function searchByNumeroLike(string $numero, int $limit = 50): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.client', 'cl')->addSelect('cl')
            ->leftJoin('r.hotel', 'h')->addSelect('h')
            ->leftJoin('r.chambres', 'ch')->addSelect('ch')
            ->where('r.numeroReservation LIKE :numero')
            ->setParameter('numero', '%' . trim($numero) . '%')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneWithDetails(int $id): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.client', 'cl')->addSelect('cl')
            ->leftJoin('r.hotel', 'h')->addSelect('h')
            ->leftJoin('r.chambres', 'ch')->addSelect('ch')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByStatut(string $statut): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Chambres distinctes occupées à une date (réservations actives).
     */
    public function countOccupiedChambresOnDate(\DateTimeInterface $date): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT ch.id)')
            ->innerJoin('r.chambres', 'ch')
            ->where('r.statut != :cancelled')
            ->andWhere('r.dateDebut <= :date')
            ->andWhere('r.dateFin > :date')
            ->setParameter('cancelled', 'Annulée')
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Reservation[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.client', 'cl')->addSelect('cl')
            ->leftJoin('r.hotel', 'h')->addSelect('h')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{items: Reservation[], total: int}
     */
    public function paginateAdmin(int $offset, int $limit, ?string $statut = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.client', 'cl')->addSelect('cl')
            ->leftJoin('r.hotel', 'h')->addSelect('h');

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('r.statut = :statut')
                ->setParameter('statut', $statut);
        }

        $total = (int) (clone $qb)
            ->select('COUNT(DISTINCT r.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->orderBy('r.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
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
     * Réservations actives (non annulées) en conflit sur une chambre et une période.
     */
    public function findActiveConflictingReservations(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin, int $chambreId): array
    {
        return $this->findConflictingReservations($dateDebut, $dateFin, $chambreId);
    }

    /**
     * Vérifier les chevauchements de dates (réservations actives = statut ≠ Annulée)
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
