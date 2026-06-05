<?php

namespace App\Controller\Admin;

use App\Entity\Chambre;
use App\Repository\HotelRepository;
use App\Service\ChambreService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/chambres')]
#[IsGranted('ROLE_ADMIN')]
class ChambreController extends AbstractController
{
    #[Route('', name: 'admin_chambres_list', methods: ['GET'])]
    /**
     * Liste paginée des chambres avec filtres (hôtel, type, recherche).
     *
     * @param Request $request
     * @param ChambreService $chambreService
     * @param HotelRepository $hotelRepository
     * @return Response
     */
    public function index(Request $request, ChambreService $chambreService, HotelRepository $hotelRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $search = $request->query->getString('q', '') ?: null;
        $hotelId = $request->query->getInt('hotel_id') ?: null;
        $type = $request->query->getString('type', '') ?: null;

        $data = $chambreService->paginateChambres($page, $limit, $search, $hotelId, $type);

        return $this->render('admin/chambres/list.html.twig', [
            'chambres' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'pages' => $data['pages'],
            'limit' => $data['limit'],
            'page_range' => $data['page_range'],
            'q' => $search ?? '',
            'hotel_id' => $hotelId,
            'type' => $type ?? '',
            'hotels' => $hotelRepository->findAll(),
        ]);
    }

    #[Route('/creer', name: 'admin_chambre_create', methods: ['GET', 'POST'])]
    /**
     * Création d'une chambre (formulaire + soumission).
     * Valide la présence de l'hôtel et enregistre la chambre via ChambreService.
     *
     * @param Request $request
     * @param ChambreService $chambreService
     * @param HotelRepository $hotelRepository
     * @return Response
     */
    public function create(
        Request $request,
        ChambreService $chambreService,
        HotelRepository $hotelRepository
    ): Response {
        if ($request->isMethod('POST')) {
            try {
                $hotel = $hotelRepository->find($request->request->getInt('hotel_id'));
                if (!$hotel) {
                    throw new \InvalidArgumentException('Hôtel non trouvé');
                }

                $chambre = new Chambre();
                $chambre->setEtage($request->request->getInt('etage'))
                    ->setType($request->request->getString('type'))
                    ->setNombreLits($request->request->getInt('nombre_lits'))
                    ->setHotel($hotel);

                $chambreService->saveChambre($chambre);
                $this->addFlash('success', 'Chambre créée avec succès');

                return $this->redirectToRoute('admin_chambres_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }

        return $this->render('admin/chambres/form.html.twig', [
            'hotels' => $hotelRepository->findAll(),
            'chambre' => null,
        ]);
    }

    #[Route('/{id}/editer', name: 'admin_chambre_edit', methods: ['GET', 'POST'])]
    /**
     * Édition d'une chambre existante.
     * Met à jour les champs modifiables et sauvegarde via le service.
     *
     * @param Request $request
     * @param Chambre $chambre
     * @param ChambreService $chambreService
     * @param HotelRepository $hotelRepository
     * @return Response
     */
    public function edit(
        Request $request,
        Chambre $chambre,
        ChambreService $chambreService,
        HotelRepository $hotelRepository
    ): Response {
        if ($request->isMethod('POST')) {
            try {
                $chambre->setEtage($request->request->getInt('etage'))
                    ->setType($request->request->getString('type'))
                    ->setNombreLits($request->request->getInt('nombre_lits'));

                $hotel = $hotelRepository->find($request->request->getInt('hotel_id'));
                if ($hotel) {
                    $chambre->setHotel($hotel);
                }

                $chambreService->saveChambre($chambre);
                $this->addFlash('success', 'Chambre modifiée avec succès');

                return $this->redirectToRoute('admin_chambres_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }

        return $this->render('admin/chambres/form.html.twig', [
            'hotels' => $hotelRepository->findAll(),
            'chambre' => $chambre,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_chambre_delete', methods: ['POST'])]
    /**
     * Suppression d'une chambre.
     *
     * @param Chambre $chambre
     * @param ChambreService $chambreService
     * @return Response
     */
    public function delete(Chambre $chambre, ChambreService $chambreService): Response
    {
        try {
            $chambreService->deleteChambre($chambre);
            $this->addFlash('success', 'Chambre supprimée avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression');
        }

        return $this->redirectToRoute('admin_chambres_list');
    }
}
