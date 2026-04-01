<?php

namespace App\Controller\Admin;

use App\Entity\Chambre;
use App\Entity\Hotel;
use App\Repository\HotelRepository;
use App\Service\ChambreService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur admin pour les chambres
 * CRUD pour les chambres avec pagination et recherche
 * 
 * @package App\Controller\Admin
 */
#[Route('/admin/chambres')]
#[IsGranted('ROLE_ADMIN')]
class ChambreController extends AbstractController
{
    #[Route('', name: 'admin_chambres_list', methods: ['GET'])]
    public function index(Request $request, ChambreService $chambreService): Response
    {
        $page = $request->query->getInt('page', 1);
        $data = $chambreService->paginateChambres($page, 10);

        return $this->render('admin/chambres/list.html.twig', [
            'chambres' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'pages' => $data['pages'],
        ]);
    }

    #[Route('/creer', name: 'admin_chambre_create', methods: ['GET', 'POST'])]
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

        $hotels = $hotelRepository->findAll();
        return $this->render('admin/chambres/form.html.twig', [
            'hotels' => $hotels,
            'chambre' => null,
        ]);
    }

    #[Route('/{id}/editer', name: 'admin_chambre_edit', methods: ['GET', 'POST'])]
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

                $hotelId = $request->request->getInt('hotel_id');
                $hotel = $hotelRepository->find($hotelId);
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

        $hotels = $hotelRepository->findAll();
        return $this->render('admin/chambres/form.html.twig', [
            'hotels' => $hotels,
            'chambre' => $chambre,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_chambre_delete', methods: ['POST'])]
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
