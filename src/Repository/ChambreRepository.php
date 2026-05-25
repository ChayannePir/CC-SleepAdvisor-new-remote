<?php

namespace App\Repository;

use App\Entity\Chambre;
use App\Entity\Hotel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Chambre>
 */
class ChambreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chambre::class);
    }

    /**
     * Rechercher des chambres par type ou étage
     */
    public function searchByTypeOrEtage(string $type, ?int $etage = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.type = :type')
            ->setParameter('type', $type);

        if ($etage !== null) {
            $qb->andWhere('c.etage = :etage')
                ->setParameter('etage', $etage);
        }

        return $qb->orderBy('c.etage', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Chercher chambres disponibles pour une plage de dates
     * Une chambre est disponible si elle n'a PAS de réservation CONFIRMÉE qui chevauche
     *
     * Les réservations "En attente" et "Annulée" ne bloquent pas une chambre
     */
    public function findAvailableChambres(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin, ?Hotel $hotel = null): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($hotel) {
            $qb->where('c.hotel = :hotel')
                ->setParameter('hotel', $hotel);
        }

        $qb->leftJoin('c.reservations', 'r',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'r.statut = :confirmed AND r.dateDebut < :dateFin AND r.dateFin > :dateDebut'
        )
            ->where('r.id IS NULL')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->setParameter('confirmed', 'Confirmée')
            ->orderBy('c.etage', 'ASC')
            ->groupBy('c.id');

        return $qb->getQuery()->getResult();
    }
}
