<?php

namespace App\Controller\Admin;

use App\Service\Admin\DashboardStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function index(DashboardStatisticsService $statisticsService): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $statisticsService->getDashboardData(),
        ]);
    }
}
