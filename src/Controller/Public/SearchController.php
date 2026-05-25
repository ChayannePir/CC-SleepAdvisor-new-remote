<?php

namespace App\Controller\Public;

use App\Entity\Chambre;
use App\Entity\Client;
use App\Repository\ChambreRepository;
use App\Repository\HotelRepository;
use App\Service\ReservationService;
use App\Service\BasketReservationService;
use Doctrine\ORM\EntityManagerInterface;
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
        BasketReservationService $basketService
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

                // Valider les dates
                if ($dateFin <= $dateDebut) {
                    $this->addFlash('error', 'La date de fin doit être après la date de début');
                    return $this->render('public/reserver.html.twig', ['chambre' => $chambre]);
                }

                // Ajouter au panier
                $basketService->addChamber($chambre, $dateDebut, $dateFin);
                $this->addFlash('success', sprintf('Chambre %s ajoutée au panier', $chambre->getType()));

                return $this->redirectToRoute('public_basket');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }

        return $this->render('public/reserver.html.twig', [
            'chambre' => $chambre,
        ]);
    }

    #[Route('/panier', name: 'public_basket', methods: ['GET'])]
    public function basket(BasketReservationService $basketService): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        /** @var Client $user */
        $user = $this->getUser();
        if (!$user instanceof Client) {
            throw $this->createAccessDeniedException();
        }

        $basket = $basketService->getBasket();

        return $this->render('public/basket.html.twig', [
            'basket' => $basket,
            'basket_info' => $basketService->getBasketInfo(),
        ]);
    }

    #[Route('/panier/retirer/{chambreKey}', name: 'public_basket_remove', methods: ['POST'])]
    public function removeFromBasket(
        string $chambreKey,
        BasketReservationService $basketService
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $basketService->removeChamber($chambreKey);
        $this->addFlash('info', 'Chambre retirée du panier');

        return $this->redirectToRoute('public_basket');
    }

    #[Route('/panier/checkout', name: 'public_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        BasketReservationService $basketService,
        ReservationService $reservationService,
        HotelRepository $hotelRepository,
        ChambreRepository $chambreRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        /** @var Client $user */
        $user = $this->getUser();
        if (!$user instanceof Client) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier le panier n'est pas vide
        if ($basketService->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide');
            return $this->redirectToRoute('public_search');
        }

        // Affichage du formulaire de validation des données
        if ($request->isMethod('POST')) {
            try {
                // Récupérer les données du formulaire
                $email = $request->request->getString('email', $user->getEmail());
                $telephone = $request->request->getString('telephone', $user->getTelephone());

                // Valider email et téléphone
                if (empty($email) || empty($telephone)) {
                    $this->addFlash('error', 'Email et téléphone sont requis');
                    return $this->redirectToRoute('public_checkout');
                }

                // Mettre à jour le client si nécessaire
                if ($user->getEmail() !== $email || $user->getTelephone() !== $telephone) {
                    $user->setEmail($email);
                    $user->setTelephone($telephone);
                    $entityManager->flush();
                }

                // Récupérer le panier
                $basket = $basketService->getBasket();

                // Grouper par hôtel et dates pour créer les réservations
                $reservationsByHotelAndDates = [];
                foreach ($basket as $item) {
                    $key = $item['hotel_id'] . '_' . $item['date_debut'] . '_' . $item['date_fin'];
                    if (!isset($reservationsByHotelAndDates[$key])) {
                        $reservationsByHotelAndDates[$key] = [
                            'hotel_id' => $item['hotel_id'],
                            'date_debut' => new \DateTime($item['date_debut']),
                            'date_fin' => new \DateTime($item['date_fin']),
                            'chambres' => [],
                        ];
                    }
                    $reservationsByHotelAndDates[$key]['chambres'][] = $item['chambre_id'];
                }

                // Créer les réservations
                foreach ($reservationsByHotelAndDates as $reservationData) {
                    $hotel = $hotelRepository->find($reservationData['hotel_id']);
                    $chambres = [];
                    foreach ($reservationData['chambres'] as $chambreId) {
                        $chambres[] = $chambreRepository->find($chambreId);
                    }

                    $reservationService->createReservation(
                        $user,
                        $hotel,
                        $reservationData['date_debut'],
                        $reservationData['date_fin'],
                        $chambres
                    );
                }

                // Vider le panier
                $basketService->clearBasket();
                $this->addFlash('success', 'Réservations créées avec succès!');
                return $this->redirectToRoute('client_reservations');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la confirmation: ' . $e->getMessage());
            }
        }

        return $this->render('public/checkout.html.twig', [
            'basket' => $basketService->getBasket(),
            'basket_info' => $basketService->getBasketInfo(),
            'user' => $user,
        ]);
    }
