<?php

namespace App\Service\Admin;

use App\Repository\ChambreRepository;
use App\Repository\ClientRepository;
use App\Repository\HotelRepository;
use App\Repository\ReservationRepository;

/**
 * Indicateurs clés pour le tableau de bord administrateur.
 */
class DashboardStatisticsService
{
    public function __construct(
        private ClientRepository $clientRepository,
        private ChambreRepository $chambreRepository,
        private ReservationRepository $reservationRepository,
        private HotelRepository $hotelRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        $today = new \DateTimeImmutable('today');
        $totalChambres = $this->chambreRepository->countAll();
        $occupiedToday = $this->reservationRepository->countOccupiedChambresOnDate($today);

        $occupancyRate = $totalChambres > 0
            ? round(($occupiedToday / $totalChambres) * 100, 1)
            : 0.0;

        return [
            'total_clients' => $this->clientRepository->countAll(),
            'total_chambres' => $totalChambres,
            'total_hotels' => $this->hotelRepository->countAll(),
            'total_reservations' => $this->reservationRepository->countAll(),
            'reservations_en_attente' => $this->reservationRepository->countByStatut('En attente'),
            'reservations_confirmees' => $this->reservationRepository->countByStatut('Confirmée'),
            'reservations_annulees' => $this->reservationRepository->countByStatut('Annulée'),
            'occupied_chambres_today' => $occupiedToday,
            'occupancy_rate' => $occupancyRate,
            'recent_reservations' => $this->reservationRepository->findRecent(8),
        ];
    }
}
