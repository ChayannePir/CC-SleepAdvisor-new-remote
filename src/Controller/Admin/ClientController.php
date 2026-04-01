<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Service\ClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur admin pour les clients
 * CRUD pour les clients avec pagination et recherche
 * 
 * @package App\Controller\Admin
 */
#[Route('/admin/clients')]
#[IsGranted('ROLE_ADMIN')]
class ClientController extends AbstractController
{
    #[Route('', name: 'admin_clients_list', methods: ['GET'])]
    public function index(Request $request, ClientService $clientService): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->getString('search', '');

        if ($search) {
            $clients = $clientService->searchClients($search);
            $total = count($clients);
        } else {
            $data = $clientService->paginateClients($page, 10);
            $clients = $data['items'];
            $total = $data['total'];
        }

        return $this->render('admin/clients/list.html.twig', [
            'clients' => $clients,
            'total' => $total,
            'page' => $page,
            'search' => $search,
        ]);
    }

    #[Route('/{id}', name: 'admin_client_detail', methods: ['GET'])]
    public function detail(Client $client): Response
    {
        return $this->render('admin/clients/detail.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_client_delete', methods: ['POST'])]
    public function delete(Client $client, ClientService $clientService): Response
    {
        try {
            $clientService->deleteClient($client);
            $this->addFlash('success', 'Client supprimé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression du client');
        }

        return $this->redirectToRoute('admin_clients_list');
    }
}
