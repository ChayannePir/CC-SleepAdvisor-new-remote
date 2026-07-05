<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Exception\ChambreNotAvailableException;
use App\Exception\ReservationException;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reservations')]
#[IsGranted('ROLE_ADMIN')]
class ReservationController extends AbstractController
{
    #[Route('', name: 'admin_reservations_list', methods: ['GET'])]
    /**
     * Liste et recherche des réservations (pagination). Recherche par numéro possible.
     *
     * @param Request $request
     * @param ReservationService $reservationService
     * @return Response
     */
    public function index(Request $request, ReservationService $reservationService): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $numero = trim($request->query->getString('numero', ''));
        $statut = $request->query->getString('statut', '') ?: null;

        if ($numero !== '') {
            $reservations = $reservationService->searchByNumeroLike($numero);
            $total = count($reservations);
            $pages = 1;
            $page = 1;
            $pageRange = [1];
        } else {
            $data = $reservationService->paginateReservations($page, $limit, null, $statut);
            $reservations = $data['items'];
            $total = $data['total'];
            $pages = $data['pages'];
            $pageRange = $data['page_range'];
            $page = $data['page'];
            $limit = $data['limit'];
        }

        return $this->render('admin/reservations/list.html.twig', [
            'reservations' => $reservations,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'page_range' => $pageRange,
            'numero' => $numero,
            'statut' => $statut ?? '',
        ]);
    }

    #[Route('/{id}', name: 'admin_reservation_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    /**
     * Détail d'une réservation avec les chambres associées.
     *
     * @param int $id
     * @param ReservationService $reservationService
     * @return Response
     */
    public function detail(int $id, ReservationService $reservationService): Response
    {
        $reservation = $reservationService->findWithDetails($id);
        if ($reservation === null) {
            throw $this->createNotFoundException('Réservation introuvable');
        }

        return $this->render('admin/reservations/detail.html.twig', [
            'reservation' => $reservation,
            'chambres_count' => $reservation->getChambres()->count(),
        ]);
    }

    #[Route('/{id}/confirmer', name: 'admin_reservation_confirm', methods: ['POST'], requirements: ['id' => '\d+'])]
    /**
     * Confirme une réservation après vérification de disponibilité.
     *
     * @param Reservation $reservation
     * @param ReservationService $reservationService
     * @return Response
     */
    public function confirm(Reservation $reservation, ReservationService $reservationService): Response
    {
        try {
            $reservationService->confirmReservation($reservation);
            $this->addFlash('success', 'Réservation confirmée');
        } catch (ReservationException $e) {
            $this->addFlash('error', $e->getUserMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la confirmation.');
        }

        return $this->redirectToRoute('admin_reservation_detail', ['id' => $reservation->getId()]);
    }

    #[Route('/{id}/annuler', name: 'admin_reservation_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    /**
     * Annule une réservation.
     *
     * @param Reservation $reservation
     * @param ReservationService $reservationService
     * @return Response
     */
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

    #[Route('/{id}/supprimer', name: 'admin_reservation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    /**
     * Supprime une réservation.
     *
     * @param Reservation $reservation
     * @param ReservationService $reservationService
     * @return Response
     */
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
