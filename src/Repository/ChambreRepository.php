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
     * Chambres libres sur une plage de dates.
     * Disponible = aucune réservation active (statut différent de « Annulée ») qui chevauche.
     */
    public function findAvailableChambres(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin, ?Hotel $hotel = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin(
                'c.reservations',
                'r',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'r.statut != :cancelled AND r.dateDebut < :dateFin AND r.dateFin > :dateDebut'
            )
            ->andWhere('r.id IS NULL')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->setParameter('cancelled', 'Annulée')
            ->orderBy('c.etage', 'ASC')
            ->groupBy('c.id');

        if ($hotel !== null) {
            $qb->andWhere('c.hotel = :hotel')
                ->setParameter('hotel', $hotel);
        }

        return $qb->getQuery()->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Liste paginée avec filtres (gros volumes).
     *
     * @return array{items: Chambre[], total: int}
     */
    public function paginateAdmin(
        int $offset,
        int $limit,
        ?string $search = null,
        ?int $hotelId = null,
        ?string $type = null
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.hotel', 'h')
            ->addSelect('h');

        if ($search !== null && $search !== '') {
            if (ctype_digit($search)) {
                $qb->andWhere('c.type LIKE :search OR h.nom LIKE :search OR c.etage = :etageSearch')
                    ->setParameter('search', '%' . $search . '%')
                    ->setParameter('etageSearch', (int) $search);
            } else {
                $qb->andWhere('c.type LIKE :search OR h.nom LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }
        }

        if ($hotelId !== null && $hotelId > 0) {
            $qb->andWhere('c.hotel = :hotelId')
                ->setParameter('hotelId', $hotelId);
        }

        if ($type !== null && $type !== '') {
            $qb->andWhere('c.type = :type')
                ->setParameter('type', $type);
        }

        $total = (int) (clone $qb)
            ->select('COUNT(DISTINCT c.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->orderBy('h.nom', 'ASC')
            ->addOrderBy('c.etage', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }
}
