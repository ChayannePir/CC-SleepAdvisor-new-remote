<?php

namespace App\Service;

use App\Entity\Chambre;
use App\Repository\ChambreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service pour gérer les chambres
 * Contient la logique métier pour les chambres
 * 
 * @package App\Service
 */
class ChambreService
{
    public function __construct(
        private ChambreRepository $chambreRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * Créer ou mettre à jour une chambre
     */
    public function saveChambre(Chambre $chambre): Chambre
    {
        $errors = $this->validator->validate($chambre);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException('Validation des données échouée');
        }

        $this->entityManager->persist($chambre);
        $this->entityManager->flush();

        return $chambre;
    }

    /**
     * Supprimer une chambre
     */
    public function deleteChambre(Chambre $chambre): void
    {
        $this->entityManager->remove($chambre);
        $this->entityManager->flush();
    }

    /**
     * Chercher les chambres disponibles
     */
    public function findAvailableChambres(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin, $hotel = null): array
    {
        return $this->chambreRepository->findAvailableChambres($dateDebut, $dateFin, $hotel);
    }

    /**
     * Paginer les chambres
     */
    public function paginateChambres(int $page, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $qb = $this->chambreRepository->createQueryBuilder('c');
        $total = (int) (clone $qb)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $chambres = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('c.etage', 'ASC')
            ->getQuery()
            ->getResult();

        return [
            'items' => $chambres,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
}
