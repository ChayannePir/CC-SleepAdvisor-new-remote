<?php

namespace App\Controller\Client;

use App\Entity\Client;
use App\Entity\CommentaireReservation;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour l'espace client
 * Gère les réservations et commentaires des clients
 * 
 * @package App\Controller\Client
 */
#[Route('/client')]
#[IsGranted('ROLE_CLIENT')]
class ReservationController extends AbstractController
{
    #[Route('/reservations', name: 'client_reservations', methods: ['GET'])]
    public function mesReservations(ReservationRepository $reservationRepository): Response
    {
        /** @var Client $user */
        $user = $this->getUser();
        $reservations = $reservationRepository->findByClientId($user->getId());

        return $this->render('client/reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/reservation/{id}', name: 'client_reservation_detail', methods: ['GET'])]
    public function detailReservation(Reservation $reservation): Response
    {
        /** @var Client $user */
        $user = $this->getUser();
        // Vérifier que c'est la réservation du client actuel
        if ($reservation->getClient()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('client/reservation_detail.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservation/{id}/commentaire', name: 'client_add_commentaire', methods: ['POST'])]
    public function addCommentaire(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var Client $user */
        $user = $this->getUser();
        // Vérifier que c'est la réservation du client actuel
        if ($reservation->getClient()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $commentaire = new CommentaireReservation();
        $commentaire->setContenu($request->request->getString('contenu'))
            ->setType($request->request->getString('type', 'Demande spéciale'))
            ->setReservation($reservation);

        try {
            $entityManager->persist($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire ajouté avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'ajout du commentaire');
        }

        return $this->redirectToRoute('client_reservation_detail', ['id' => $reservation->getId()]);
    }

    #[Route('/reservation/{id}/annuler', name: 'client_cancel_reservation', methods: ['POST'])]
    public function cancelReservation(
        Reservation $reservation,
        ReservationService $reservationService
    ): Response {
        /** @var Client $user */
        $user = $this->getUser();
        // Vérifier que c'est la réservation du client actuel
        if ($reservation->getClient()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $reservationService->cancelReservation($reservation);
            $this->addFlash('success', 'Réservation annulée');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'annulation');
        }

        return $this->redirectToRoute('client_reservations');
    }
}
