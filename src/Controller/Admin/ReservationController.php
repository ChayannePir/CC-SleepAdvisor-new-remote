<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur admin pour les réservations
 * CRUD pour les réservations avec pagination et recherche
 * 
 * @package App\Controller\Admin
 */
#[Route('/admin/reservations')]
#[IsGranted('ROLE_ADMIN')]
class ReservationController extends AbstractController
{
    #[Route('', name: 'admin_reservations_list', methods: ['GET'])]
    public function index(Request $request, ReservationService $reservationService): Response
    {
        $page = $request->query->getInt('page', 1);
        $numero = $request->query->getString('numero', '');

        if ($numero) {
            $reservation = $reservationService->searchByNumero($numero);
            $reservations = $reservation ? [$reservation] : [];
            $total = count($reservations);
        } else {
            $data = $reservationService->paginateReservations($page, 10);
            $reservations = $data['items'];
            $total = $data['total'];
        }

        return $this->render('admin/reservations/list.html.twig', [
            'reservations' => $reservations,
            'total' => $total,
            'page' => $page,
            'numero' => $numero,
        ]);
    }

    #[Route('/{id}', name: 'admin_reservation_detail', methods: ['GET'])]
    public function detail(Reservation $reservation): Response
    {
        return $this->render('admin/reservations/detail.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/confirmer', name: 'admin_reservation_confirm', methods: ['POST'])]
    public function confirm(Reservation $reservation, ReservationService $reservationService): Response
    {
        try {
            $reservationService->confirmReservation($reservation);
            $this->addFlash('success', 'Réservation confirmée');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la confirmation');
        }

        return $this->redirectToRoute('admin_reservation_detail', ['id' => $reservation->getId()]);
    }

    #[Route('/{id}/annuler', name: 'admin_reservation_cancel', methods: ['POST'])]
    public function cancel(Reservation $reservation, ReservationService $reservationService): Response
    {
        try {
            $reservationService->cancelReservation($reservation);
            $this->addFlash('success', 'Réservation annulée');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'annulation');
        }

        return $this->redirectToRoute('admin_reservations_list');
    }

    #[Route('/{id}/supprimer', name: 'admin_reservation_delete', methods: ['POST'])]
    public function delete(Reservation $reservation, ReservationService $reservationService): Response
    {
        try {
            $reservationService->deleteReservation($reservation);
            $this->addFlash('success', 'Réservation supprimée');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression');
        }

        return $this->redirectToRoute('admin_reservations_list');
    }
}
