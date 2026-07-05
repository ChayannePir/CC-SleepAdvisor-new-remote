<?php

namespace App\Controller\Public;

use App\Entity\Chambre;
use App\Entity\Client;
use App\Exception\ChambreNotAvailableException;
use App\Exception\ReservationException;
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
    /**
     * Recherche de chambres disponibles entre deux dates et filtrage par hôtel.
     * Méthode GET pour affichage, POST pour soumission du formulaire.
     *
     * @param Request $request
     * @param ChambreRepository $chambreRepository
     * @param HotelRepository $hotelRepository
     * @return Response
     */
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
        $dateDebutStr = $request->query->getString('date_debut');
        $dateFinStr = $request->query->getString('date_fin');

        if ($request->isMethod('POST')) {
            $dateDebutStr = $request->request->getString('date_debut');
            $dateFinStr = $request->request->getString('date_fin');
            $hotelId = $request->request->getInt('hotel_id');
        } elseif ($request->query->has('hotel_id')) {
            $hotelId = $request->query->getInt('hotel_id');
        }

        if ($dateDebutStr !== '' && $dateFinStr !== '') {
            try {
                $dateDebut = new \DateTime($dateDebutStr);
                $dateFin = new \DateTime($dateFinStr);

                if ($dateFin <= $dateDebut) {
                    $this->addFlash('error', 'La date de fin doit être après la date de début');
                } else {
                    $hotel = $hotelId ? $hotelRepository->find($hotelId) : null;
                    $chambres = $chambreRepository->findAvailableChambres($dateDebut, $dateFin, $hotel);
                }
            } catch (\Exception) {
                $this->addFlash('error', 'Dates invalides');
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
    /**
     * Vue détaillée d'une chambre avec possibilité de pré-remplir les dates.
     *
     * @param Chambre $chambre
     * @param Request $request
     * @return Response
     */
    public function detail(Chambre $chambre, Request $request): Response
    {
        return $this->render('public/chambre_detail.html.twig', [
            'chambre' => $chambre,
            'date_debut' => $request->query->getString('date_debut'),
            'date_fin' => $request->query->getString('date_fin'),
        ]);
    }

    #[Route('/chambre/{id}/reserver', name: 'public_reserver_chambre', methods: ['GET', 'POST'])]
    /**
     * Page de réservation pour une chambre particulière.
     * - GET : affiche le formulaire
     * - POST : ajoute la chambre au panier si disponible
     *
     * @param Request $request
     * @param Chambre $chambre
     * @param BasketReservationService $basketService
     * @param ReservationService $reservationService
     * @return Response
     */
    public function reserver(
        Request $request,
        Chambre $chambre,
        BasketReservationService $basketService,
        ReservationService $reservationService
    ): Response {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Veuillez vous connecter ou vous inscrire pour réserver');
            return $this->redirectToRoute('app_login');
        }

        $defaultDateDebut = $request->query->getString('date_debut', $request->request->getString('date_debut'));
        $defaultDateFin = $request->query->getString('date_fin', $request->request->getString('date_fin'));

        if ($request->isMethod('POST')) {
            try {
                /** @var Client $user */
                $user = $this->getUser();
                if (!$user instanceof Client) {
                    throw new \InvalidArgumentException('Seuls les clients peuvent réserver');
                }

                $dateDebut = new \DateTime($request->request->getString('date_debut'));
                $dateFin = new \DateTime($request->request->getString('date_fin'));

                if ($dateFin <= $dateDebut) {
                    $this->addFlash('error', 'La date de fin doit être après la date de début');

                    return $this->render('public/reserver.html.twig', [
                        'chambre' => $chambre,
                        'date_debut' => $defaultDateDebut,
                        'date_fin' => $defaultDateFin,
                    ]);
                }

                if (!$reservationService->isChambreAvailable($chambre, $dateDebut, $dateFin)) {
                    $this->addFlash('error', 'Cette chambre n\'est plus disponible pour ces dates');

                    return $this->render('public/reserver.html.twig', [
                        'chambre' => $chambre,
                        'date_debut' => $defaultDateDebut,
                        'date_fin' => $defaultDateFin,
                    ]);
                }

                $basketService->addChamber($chambre, $dateDebut, $dateFin);
                $this->addFlash('success', sprintf('Chambre %s ajoutée au panier', $chambre->getType()));

                return $this->redirectToRoute('public_basket');
            } catch (ReservationException $e) {
                $this->addFlash('error', $e->getUserMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur inattendue est survenue. Veuillez réessayer.');
            }
        }

        return $this->render('public/reserver.html.twig', [
            'chambre' => $chambre,
            'date_debut' => $defaultDateDebut,
            'date_fin' => $defaultDateFin,
        ]);
    }

    #[Route('/panier/ajouter', name: 'public_basket_add', methods: ['POST'])]
    /**
     * Ajoute une ou plusieurs chambres au panier depuis la page de recherche.
     * Vérifie les dates et la disponibilité pour chaque chambre.
     *
     * @param Request $request
     * @param ChambreRepository $chambreRepository
     * @param BasketReservationService $basketService
     * @param ReservationService $reservationService
     * @return Response
     */
    public function addToBasket(
        Request $request,
        ChambreRepository $chambreRepository,
        BasketReservationService $basketService,
        ReservationService $reservationService
    ): Response {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Veuillez vous connecter pour ajouter des chambres au panier');

            return $this->redirectToRoute('app_login');
        }

        if (!$this->getUser() instanceof Client) {
            throw $this->createAccessDeniedException();
        }

        $dateDebutStr = $request->request->getString('date_debut');
        $dateFinStr = $request->request->getString('date_fin');
        $chambreIds = $request->request->all('chambre_ids');

        if ($dateDebutStr === '' || $dateFinStr === '') {
            $this->addFlash('error', 'Les dates de séjour sont requises');

            return $this->redirectToRoute('public_search');
        }

        if (!is_array($chambreIds) || count($chambreIds) === 0) {
            $this->addFlash('error', 'Sélectionnez au moins une chambre');

            return $this->redirectToRoute('public_search', [
                'date_debut' => $dateDebutStr,
                'date_fin' => $dateFinStr,
            ]);
        }

        try {
            $dateDebut = new \DateTime($dateDebutStr);
            $dateFin = new \DateTime($dateFinStr);

            if ($dateFin <= $dateDebut) {
                $this->addFlash('error', 'La date de fin doit être après la date de début');

                return $this->redirectToRoute('public_search');
            }

            $added = 0;
            foreach ($chambreIds as $chambreId) {
                $chambre = $chambreRepository->find((int) $chambreId);
                if ($chambre === null) {
                    continue;
                }

                if (!$reservationService->isChambreAvailable($chambre, $dateDebut, $dateFin)) {
                    $this->addFlash('warning', sprintf(
                        'La chambre %s (étage %d) n\'est plus disponible',
                        $chambre->getType(),
                        $chambre->getEtage()
                    ));
                    continue;
                }

                $basketService->addChamber($chambre, $dateDebut, $dateFin);
                ++$added;
            }

            if ($added === 0) {
                $this->addFlash('error', 'Aucune chambre n\'a pu être ajoutée au panier');
            } else {
                $this->addFlash('success', sprintf('%d chambre(s) ajoutée(s) au panier', $added));
            }

            return $this->redirectToRoute('public_basket');
        } catch (ReservationException $e) {
            $this->addFlash('error', $e->getUserMessage());

            return $this->redirectToRoute('public_search');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur inattendue est survenue.');

            return $this->redirectToRoute('public_search');
        }
    }

    #[Route('/panier', name: 'public_basket', methods: ['GET'])]
    /**
     * Affiche le panier de réservation pour l'utilisateur connecté.
     *
     * @param BasketReservationService $basketService
     * @return Response
     */
    public function basket(BasketReservationService $basketService): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->getUser() instanceof Client) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('public/basket.html.twig', [
            'basket' => $basketService->getBasket(),
            'basket_info' => $basketService->getBasketInfo(),
        ]);
    }

    #[Route('/panier/retirer/{chambreKey}', name: 'public_basket_remove', methods: ['POST'])]
    /**
     * Retire une chambre du panier identifiée par sa clé.
     *
     * @param string $chambreKey
     * @param BasketReservationService $basketService
     * @return Response
     */
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
    /**
     * Finalisation des réservations présentes dans le panier.
     * - GET : affiche le formulaire de contact
     * - POST : crée une ou plusieurs réservations groupées par hôtel et dates
     *
     * @param Request $request
     * @param BasketReservationService $basketService
     * @param ReservationService $reservationService
     * @param HotelRepository $hotelRepository
     * @param ChambreRepository $chambreRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
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

        if ($basketService->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide');

            return $this->redirectToRoute('public_search');
        }

        $needsContactInfo = $this->clientNeedsContactInfo($user);

        if ($request->isMethod('POST')) {
            try {
                $email = trim($request->request->getString('email', (string) $user->getEmail()));
                $telephone = trim($request->request->getString('telephone', (string) $user->getTelephone()));

                if ($needsContactInfo && ($email === '' || $telephone === '')) {
                    $this->addFlash('error', 'Email et téléphone sont obligatoires pour finaliser la réservation');

                    return $this->redirectToRoute('public_checkout');
                }

                if ($email !== '' && $user->getEmail() !== $email) {
                    $user->setEmail($email);
                }
                if ($telephone !== '' && $user->getTelephone() !== $telephone) {
                    $user->setTelephone($telephone);
                }
                $entityManager->flush();

                $basket = $basketService->getBasket();
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

                foreach ($reservationsByHotelAndDates as $reservationData) {
                    $hotel = $hotelRepository->find($reservationData['hotel_id']);
                    if ($hotel === null) {
                        throw new \InvalidArgumentException('Hôtel introuvable');
                    }

                    $chambres = [];
                    foreach ($reservationData['chambres'] as $chambreId) {
                        $chambre = $chambreRepository->find($chambreId);
                        if ($chambre !== null) {
                            $chambres[] = $chambre;
                        }
                    }

                    if ($chambres === []) {
                        throw new \InvalidArgumentException('Aucune chambre valide dans le panier');
                    }

                    $reservationService->createReservation(
                        $user,
                        $hotel,
                        $reservationData['date_debut'],
                        $reservationData['date_fin'],
                        $chambres
                    );
                }

                $basketService->clearBasket();
                $this->addFlash('success', 'Réservation(s) créée(s) avec succès !');

                return $this->redirectToRoute('client_reservations');
            } catch (ChambreNotAvailableException $e) {
                $this->addFlash('error', $e->getUserMessage());
            } catch (ReservationException $e) {
                $this->addFlash('error', $e->getUserMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'La réservation n\'a pas pu être finalisée. Vérifiez votre panier et réessayez.');
            }
        }

        return $this->render('public/checkout.html.twig', [
            'basket' => $basketService->getBasket(),
            'basket_info' => $basketService->getBasketInfo(),
            'user' => $user,
            'needs_contact_info' => $needsContactInfo,
        ]);
    }

    private function clientNeedsContactInfo(Client $client): bool
    {
        return trim((string) $client->getEmail()) === ''
            || trim((string) $client->getTelephone()) === '';
    }
}
