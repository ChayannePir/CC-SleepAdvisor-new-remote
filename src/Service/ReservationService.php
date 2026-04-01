<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Chambre;
use App\Entity\Client;
use App\Entity\Hotel;
use App\Repository\ReservationRepository;
use App\Repository\ChambreRepository;
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
        private ChambreRepository $chambreRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * Créer une nouvelle réservation
     */
    public function createReservation(
        Client $client,
        Hotel $hotel,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        array $chambres
    ): Reservation {
        if (empty($chambres)) {
            throw new \InvalidArgumentException('Au moins une chambre doit être réservée');
        }

        // Vérifier les chevauchements
        foreach ($chambres as $chambre) {
            $conflicts = $this->reservationRepository->findConflictingReservations(
                $dateDebut,
                $dateFin,
                $chambre->getId()
            );
            
            if (!empty($conflicts)) {
                throw new \InvalidArgumentException(sprintf(
                    'La chambre %d n\'est pas disponible pour cette période',
                    $chambre->getId()
                ));
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
            throw new \InvalidArgumentException('Validation des données échouée');
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
     * Confirmer une réservation
     */
    public function confirmReservation(Reservation $reservation): Reservation
    {
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
     * Paginer les réservations
     */
    public function paginateReservations(int $page, int $limit = 10, ?Hotel $hotel = null): array
    {
        $offset = ($page - 1) * $limit;
        
        $qb = $this->reservationRepository->createQueryBuilder('r');
        
        if ($hotel) {
            $qb->where('r.hotel = :hotel')
                ->setParameter('hotel', $hotel);
        }

        $total = (int) (clone $qb)
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $reservations = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();

        return [
            'items' => $reservations,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Rechercher par numéro de réservation
     */
    public function searchByNumero(string $numero): ?Reservation
    {
        return $this->reservationRepository->findByNumero($numero);
    }
}
