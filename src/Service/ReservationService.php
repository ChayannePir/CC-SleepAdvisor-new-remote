<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Chambre;
use App\Entity\Client;
use App\Entity\Hotel;
use App\Exception\ChambreNotAvailableException;
use App\Exception\ReservationValidationException;
use App\Repository\ReservationRepository;
use App\Service\Admin\PaginationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service pour gérer les réservations
 * Contient la logique métier pour les réservations
 *
 * @package App\Service
 */
class ReservationService
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * Une chambre est disponible si aucune réservation active ne chevauche la période.
     */
    public function isChambreAvailable(
        Chambre $chambre,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $excludeReservationId = null
    ): bool {
        if ($chambre->getId() === null) {
            return false;
        }

        $conflicts = $this->reservationRepository->findActiveConflictingReservations(
            $dateDebut,
            $dateFin,
            $chambre->getId()
        );

        if ($excludeReservationId === null) {
            return $conflicts === [];
        }

        foreach ($conflicts as $reservation) {
            if ($reservation->getId() !== $excludeReservationId) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vérifie que toutes les chambres sont disponibles sur la période.
     *
     * @param Chambre[] $chambres
     */
    public function areChambresAvailable(
        array $chambres,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $excludeReservationId = null
    ): bool {
        foreach ($chambres as $chambre) {
            if (!$chambre instanceof Chambre) {
                return false;
            }
            if (!$this->isChambreAvailable($chambre, $dateDebut, $dateFin, $excludeReservationId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Créer une réservation (relation ternaire Client + Hôtel + une ou plusieurs Chambres).
     *
     * @param Chambre[] $chambres
     */
    public function createReservation(
        Client $client,
        Hotel $hotel,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        array $chambres
    ): Reservation {
        if (empty($chambres)) {
            throw ReservationValidationException::emptyChambres();
        }

        if ($dateFin <= $dateDebut) {
            throw ReservationValidationException::invalidDates();
        }

        foreach ($chambres as $chambre) {
            if (!$chambre instanceof Chambre) {
                throw ReservationValidationException::invalidChambre();
            }

            if ($chambre->getHotel()?->getId() !== $hotel->getId()) {
                throw ReservationValidationException::chambreWrongHotel(
                    $chambre->getType(),
                    $chambre->getEtage()
                );
            }

            if (!$this->isChambreAvailable($chambre, $dateDebut, $dateFin)) {
                throw ChambreNotAvailableException::forChambre($chambre);
            }
        }

        $reservation = new Reservation();
        $reservation->setClient($client)
            ->setHotel($hotel)
            ->setDateDebut($dateDebut)
            ->setDateFin($dateFin)
            ->setStatut('En attente');

        foreach ($chambres as $chambre) {
            $reservation->addChambre($chambre);
        }

        return $this->saveReservation($reservation);
    }

    /**
     * Sauvegarder une réservation
     */
    public function saveReservation(Reservation $reservation): Reservation
    {
        $errors = $this->validator->validate($reservation);
        if (count($errors) > 0) {
            throw ReservationValidationException::validationFailed();
        }

        if (!$reservation->getId()) {
            $this->entityManager->persist($reservation);
        }

        $this->entityManager->flush();

        return $reservation;
    }

    /**
     * Supprimer une réservation
     */
    public function deleteReservation(Reservation $reservation): void
    {
        $this->entityManager->remove($reservation);
        $this->entityManager->flush();
    }

    /**
     * Confirmer une réservation (vérifie à nouveau la disponibilité des chambres).
     */
    public function confirmReservation(Reservation $reservation): Reservation
    {
        foreach ($reservation->getChambres() as $chambre) {
            if (!$this->isChambreAvailable(
                $chambre,
                $reservation->getDateDebut(),
                $reservation->getDateFin(),
                $reservation->getId()
            )) {
                throw ChambreNotAvailableException::forChambre($chambre);
            }
        }

        $reservation->setStatut('Confirmée');

        return $this->saveReservation($reservation);
    }

    /**
     * Annuler une réservation
     */
    public function cancelReservation(Reservation $reservation): Reservation
    {
        $reservation->setStatut('Annulée');

        return $this->saveReservation($reservation);
    }

    /**
     * @return array{items: Reservation[], total: int, page: int, limit: int, pages: int, page_range: int[]}
     */
    public function paginateReservations(
        int $page,
        int $limit = 20,
        ?Hotel $hotel = null,
        ?string $statut = null
    ): array {
        if ($hotel !== null) {
            return $this->paginateReservationsForHotel($page, $limit, $hotel, $statut);
        }

        $probe = $this->reservationRepository->paginateAdmin(0, 1, $statut);
        $meta = PaginationHelper::normalize($page, $limit, $probe['total']);
        $result = $this->reservationRepository->paginateAdmin($meta['offset'], $meta['limit'], $statut);

        return [
            'items' => $result['items'],
            'total' => $meta['total'],
            'page' => $meta['page'],
            'limit' => $meta['limit'],
            'pages' => $meta['pages'],
            'page_range' => PaginationHelper::pageRange($meta['page'], $meta['pages']),
        ];
    }

    /**
     * @return array{items: Reservation[], total: int, page: int, limit: int, pages: int, page_range: int[]}
     */
    private function paginateReservationsForHotel(
        int $page,
        int $limit,
        Hotel $hotel,
        ?string $statut
    ): array {
        $qb = $this->reservationRepository->createQueryBuilder('r')
            ->where('r.hotel = :hotel')
            ->setParameter('hotel', $hotel);

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('r.statut = :statut')->setParameter('statut', $statut);
        }

        $total = (int) (clone $qb)->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();
        $meta = PaginationHelper::normalize($page, $limit, $total);

        $items = $qb
            ->setFirstResult($meta['offset'])
            ->setMaxResults($meta['limit'])
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $meta['total'],
            'page' => $meta['page'],
            'limit' => $meta['limit'],
            'pages' => $meta['pages'],
            'page_range' => PaginationHelper::pageRange($meta['page'], $meta['pages']),
        ];
    }

    public function searchByNumero(string $numero): ?Reservation
    {
        return $this->reservationRepository->findByNumero($numero);
    }

    /**
     * @return Reservation[]
     */
    public function searchByNumeroLike(string $numero): array
    {
        return $this->reservationRepository->searchByNumeroLike($numero);
    }

    public function findWithDetails(int $id): ?Reservation
    {
        return $this->reservationRepository->findOneWithDetails($id);
    }
}
