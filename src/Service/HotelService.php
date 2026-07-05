<?php

namespace App\Service;

use App\Entity\Hotel;
use App\Repository\HotelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class HotelService
{
    private EntityManagerInterface $em;
    private HotelRepository $hotelRepository;
    private PaginatorInterface $paginator;
    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $em,
        HotelRepository $hotelRepository,
        PaginatorInterface $paginator,
        RequestStack $requestStack
    ) {
        $this->em = $em;
        $this->hotelRepository = $hotelRepository;
        $this->paginator = $paginator;
        $this->requestStack = $requestStack;
    }

    /**
     * Paginate hotels with optional search.
     *
     * @param int $page
     * @param int $limit
     * @param string|null $search
     * @return array
     */
    public function paginateHotels(int $page, int $limit, ?string $search): array
    {
        $query = $this->hotelRepository->createQueryBuilder('h');

        if ($search) {
            $query->andWhere('h.nom LIKE :search OR h.adresse LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $pagination = $this->paginator->paginate(
            $query->getQuery(),
            $page,
            $limit
        );

        return [
            'items' => $pagination->getItems(),
            'total' => $pagination->getTotalItemCount(),
            'page' => $pagination->getCurrentPageNumber(),
            'pages' => $pagination->getPageCount(),
            'limit' => $pagination->getItemNumberPerPage(),
            'page_range' => $pagination->getPaginationData()['pagesInRange'],
        ];
    }

    /**
     * Save a hotel entity.
     *
     * @param Hotel $hotel
     * @return void
     */
    public function saveHotel(Hotel $hotel): void
    {
        if (!$hotel->getId()) {
            $hotel->setCreatedAt(new \DateTimeImmutable());
        }
        $hotel->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($hotel);
        $this->em->flush();
    }

    /**
     * Delete a hotel entity.
     *
     * @param Hotel $hotel
     * @return void
     */
    public function deleteHotel(Hotel $hotel): void
    {
        $this->em->remove($hotel);
        $this->em->flush();
    }
}
