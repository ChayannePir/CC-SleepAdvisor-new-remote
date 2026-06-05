<?php

namespace App\Controller\Admin;

use App\Entity\Hotel;
use App\Service\HotelService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/hotels')]
#[IsGranted('ROLE_ADMIN')]
class HotelController extends AbstractController
{
    #[Route('', name: 'admin_hotels_list', methods: ['GET'])]
    /**
     * Liste paginée des hôtels avec filtres (recherche).
     *
     * @param Request $request
     * @param HotelService $hotelService
     * @return Response
     */
    public function index(Request $request, HotelService $hotelService): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $search = $request->query->getString('q', '') ?: null;

        $data = $hotelService->paginateHotels($page, $limit, $search);

        return $this->render('admin/hotels/list.html.twig', [
            'hotels' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'pages' => $data['pages'],
            'limit' => $data['limit'],
            'page_range' => $data['page_range'],
            'q' => $search ?? '',
        ]);
    }

    #[Route('/creer', name: 'admin_hotel_create', methods: ['GET', 'POST'])]
    /**
     * Création d'un hôtel (formulaire + soumission).
     *
     * @param Request $request
     * @param HotelService $hotelService
     * @return Response
     */
    public function create(Request $request, HotelService $hotelService): Response
    {
        $hotel = new Hotel();
        if ($request->isMethod('POST')) {
            try {
                $hotel->setNom($request->request->getString('nom'))
                    ->setAdresse($request->request->getString('adresse'))
                    ->setCategorie($request->request->getString('categorie'));

                $hotelService->saveHotel($hotel);
                $this->addFlash('success', 'Hôtel créé avec succès');

                return $this->redirectToRoute('admin_hotels_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }

        return $this->render('admin/hotels/form.html.twig', [
            'hotel' => $hotel,
        ]);
    }

    #[Route('/{id}/editer', name: 'admin_hotel_edit', methods: ['GET', 'POST'])]
    /**
     * Édition d'un hôtel existant.
     *
     * @param Request $request
     * @param Hotel $hotel
     * @param HotelService $hotelService
     * @return Response
     */
    public function edit(Request $request, Hotel $hotel, HotelService $hotelService): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $hotel->setNom($request->request->getString('nom'))
                    ->setAdresse($request->request->getString('adresse'))
                    ->setCategorie($request->request->getString('categorie'));

                $hotelService->saveHotel($hotel);
                $this->addFlash('success', 'Hôtel modifié avec succès');

                return $this->redirectToRoute('admin_hotels_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }

        return $this->render('admin/hotels/form.html.twig', [
            'hotel' => $hotel,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_hotel_delete', methods: ['POST'])]
    /**
     * Suppression d'un hôtel.
     *
     * @param Hotel $hotel
     * @param HotelService $hotelService
     * @return Response
     */
    public function delete(Hotel $hotel, HotelService $hotelService): Response
    {
        try {
            $hotelService->deleteHotel($hotel);
            $this->addFlash('success', 'Hôtel supprimé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression');
        }

        return $this->redirectToRoute('admin_hotels_list');
    }
}
