<?php

namespace App\Controller\Public;

use App\Entity\Chambre;
use App\Entity\Client;
use App\Repository\ChambreRepository;
use App\Repository\HotelRepository;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour l'espace public
 * Gère la recherche et la réservation de chambres
 * 
 * @package App\Controller\Public
 */
#[Route('/recherche')]
class SearchController extends AbstractController
{
    #[Route('', name: 'public_search', methods: ['GET', 'POST'])]
    public function search(
        Request $request,
        ChambreRepository $chambreRepository,
        HotelRepository $hotelRepository
    ): Response {
        $chambres = [];
        $hotels = $hotelRepository->findAll();
        $dateDebut = null;
        $dateFin = null;
        $hotelId = null;

        if ($request->isMethod('POST')) {
            $dateDebut = $request->request->get('date_debut');
            $dateFin = $request->request->get('date_fin');
            $hotelId = $request->request->getInt('hotel_id');

            if ($dateDebut && $dateFin) {
                try {
                    $dateDebut = new \DateTime($dateDebut);
                    $dateFin = new \DateTime($dateFin);

                    if ($dateFin <= $dateDebut) {
                        $this->addFlash('error', 'La date de fin doit être après la date de début');
                    } else {
                        $hotel = $hotelId ? $hotelRepository->find($hotelId) : null;
                        $chambres = $chambreRepository->findAvailableChambres($dateDebut, $dateFin, $hotel);
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la recherche');
                }
            }
        }

        return $this->render('public/search.html.twig', [
            'chambres' => $chambres,
            'hotels' => $hotels,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'hotel_id' => $hotelId,
        ]);
    }

    #[Route('/chambre/{id}', name: 'public_chambre_detail', methods: ['GET'])]
    public function detail(Chambre $chambre): Response
    {
        return $this->render('public/chambre_detail.html.twig', [
            'chambre' => $chambre,
        ]);
    }

    #[Route('/chambre/{id}/reserver', name: 'public_reserver_chambre', methods: ['GET', 'POST'])]
    public function reserver(
        Request $request,
        Chambre $chambre,
        ReservationService $reservationService
    ): Response {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Veuillez vous connecter ou vous inscrire pour réserver');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            try {
                /** @var Client $user */
                $user = $this->getUser();
                if (!$user instanceof Client) {
                    throw new \InvalidArgumentException('Seuls les clients peuvent réserver');
                }

                $dateDebut = new \DateTime($request->request->getString('date_debut'));
                $dateFin = new \DateTime($request->request->getString('date_fin'));

                $reservation = $reservationService->createReservation(
                    $user,
                    $chambre->getHotel(),
                    $dateDebut,
                    $dateFin,
                    [$chambre]
                );

                $this->addFlash('success', 'Réservation créée avec succès!');
                return $this->redirectToRoute('client_reservations');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de la réservation');
            }
        }

        return $this->render('public/reserver.html.twig', [
            'chambre' => $chambre,
        ]);
    }
}
