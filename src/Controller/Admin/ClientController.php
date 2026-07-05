<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Service\ClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/clients')]
#[IsGranted('ROLE_ADMIN')]
class ClientController extends AbstractController
{
    #[Route('', name: 'admin_clients_list', methods: ['GET'])]
    /**
     * Liste paginée des clients avec recherche par nom/email.
     *
     * @param Request $request
     * @param ClientService $clientService
     * @return Response
     */
    public function index(Request $request, ClientService $clientService): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $search = trim($request->query->getString('search', ''));
        $searchParam = $search !== '' ? $search : null;

        $data = $clientService->paginateClients($page, $limit, $searchParam);

        return $this->render('admin/clients/list.html.twig', [
            'clients' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'pages' => $data['pages'],
            'limit' => $data['limit'],
            'page_range' => $data['page_range'],
            'search' => $search,
        ]);
    }

    #[Route('/{id}', name: 'admin_client_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    /**
     * Détail d'un client.
     *
     * @param Client $client
     * @return Response
     */
    public function detail(Client $client): Response
    {
        return $this->render('admin/clients/detail.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_client_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    /**
     * Suppression d'un client.
     *
     * @param Client $client
     * @param ClientService $clientService
     * @return Response
     */
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
