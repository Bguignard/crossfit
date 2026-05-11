<?php

namespace App\Controller\Admin;

use App\Services\Admin\AdminDashboardMetricsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminDashboardMetricsController extends AbstractController
{
    public function __construct(private readonly AdminDashboardMetricsProvider $metricsProvider)
    {
    }

    #[Route('/api/admin/metrics', name: 'api_admin_metrics', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(): JsonResponse
    {
        return $this->json($this->metricsProvider->getMetrics());
    }
}
