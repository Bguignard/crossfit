<?php

namespace App\Controller\Admin;

use App\Services\Admin\AiGenerationCostMetricsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminAiGenerationCostMetricsController extends AbstractController
{
    public function __construct(private readonly AiGenerationCostMetricsProvider $metricsProvider)
    {
    }

    #[Route('/api/admin/ai-generation-costs', name: 'api_admin_ai_generation_costs', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            return $this->json($this->metricsProvider->summarize(
                $this->dateQuery($request, 'from'),
                $this->dateQuery($request, 'to'),
            ));
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    private function dateQuery(Request $request, string $key): ?\DateTimeImmutable
    {
        $value = $request->query->get($key);
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new \InvalidArgumentException(sprintf('Invalid "%s" date.', $key));
        }
    }
}
