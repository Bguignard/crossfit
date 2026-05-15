<?php

namespace App\Controller\Competition;

use App\Entity\Competition\Athlete;
use App\Services\Competition\AthletePublicAnalysisGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class AthletePublicAnalysisController extends AbstractController
{
    #[Route('/api/athletes/{id}/public-analysis', name: 'api_athlete_public_analysis_generate', methods: ['POST'])]
    public function __invoke(
        string $id,
        EntityManagerInterface $entityManager,
        AthletePublicAnalysisGenerator $generator,
    ): JsonResponse {
        $athlete = $entityManager->getRepository(Athlete::class)->find($id);

        if (!$athlete instanceof Athlete) {
            throw new NotFoundHttpException('Athlete not found.');
        }

        $analysis = $generator->generateIfNeeded($athlete);

        if ($analysis === null) {
            return $this->json([
                'analysis' => null,
                'eligible' => false,
            ]);
        }

        return $this->json([
            'analysis' => $analysis->toPublicPayload(),
            'eligible' => true,
        ]);
    }
}
