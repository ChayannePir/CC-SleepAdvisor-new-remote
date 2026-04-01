<?php

namespace App\Controller\Admin;

use App\Service\ClientService;
use App\Service\ChambreService;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur admin dashboard
 * 
 * @package App\Controller\Admin
 */
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function index(
        ClientService $clientService,
        ChambreService $chambreService,
        ReservationService $reservationService
    ): Response {
        $stats = [
            'clients' => $clientService->paginateClients(1, 1)['total'],
            'chambres' => $chambreService->paginateChambres(1, 1)['total'],
            'reservations' => $reservationService->paginateReservations(1, 1)['total'],
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
        ]);
    }
}
