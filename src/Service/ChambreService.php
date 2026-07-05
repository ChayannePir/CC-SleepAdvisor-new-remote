<?php

namespace App\Service;

use App\Entity\Chambre;
use App\Entity\Hotel;
use App\Repository\ChambreRepository;
use App\Service\Admin\PaginationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service pour gérer les chambres
 */
class ChambreService
{
    public function __construct(
        private ChambreRepository $chambreRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

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

    public function deleteChambre(Chambre $chambre): void
    {
        $this->entityManager->remove($chambre);
        $this->entityManager->flush();
    }

    public function findAvailableChambres(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin, $hotel = null): array
    {
        return $this->chambreRepository->findAvailableChambres($dateDebut, $dateFin, $hotel);
    }

    /**
     * Pagination admin avec filtres.
     *
     * @return array{items: Chambre[], total: int, page: int, limit: int, pages: int, page_range: int[]}
     */
    public function paginateChambres(
        int $page,
        int $limit = 20,
        ?string $search = null,
        ?int $hotelId = null,
        ?string $type = null
    ): array {
        $result = $this->chambreRepository->paginateAdmin(0, 1, $search, $hotelId, $type);
        $meta = PaginationHelper::normalize($page, $limit, $result['total']);
        $result = $this->chambreRepository->paginateAdmin(
            $meta['offset'],
            $meta['limit'],
            $search,
            $hotelId,
            $type
        );

        return [
            'items' => $result['items'],
            'total' => $meta['total'],
            'page' => $meta['page'],
            'limit' => $meta['limit'],
            'pages' => $meta['pages'],
            'page_range' => PaginationHelper::pageRange($meta['page'], $meta['pages']),
        ];
    }
}
