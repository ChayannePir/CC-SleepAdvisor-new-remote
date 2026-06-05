<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * Rechercher des clients par nom ou email
     */
    public function searchByNomOrEmail(string $search): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.nom LIKE :search')
            ->orWhere('c.email LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter tous les clients
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{items: Client[], total: int}
     */
    public function paginateAdmin(int $offset, int $limit, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($search !== null && $search !== '') {
            $qb->andWhere('c.nom LIKE :search OR c.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $total = (int) (clone $qb)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->orderBy('c.nom', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }
}
