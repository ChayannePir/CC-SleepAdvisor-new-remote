<?php

namespace App\Repository;

use App\Entity\Gestionnaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Gestionnaire>
 */
class GestionnaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gestionnaire::class);
    }

    /**
     * Trouve un gestionnaire par email
     */
    public function findByEmail(string $email): ?Gestionnaire
    {
        return $this->findOneBy(['email' => $email]);
    }
}
