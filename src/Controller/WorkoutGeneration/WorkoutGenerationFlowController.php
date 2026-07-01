<?php

declare(strict_types=1);

namespace App\Controller\WorkoutGeneration;

use App\Entity\Workout\BodyPart;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementType;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutMovementGenerationType;
use App\Entity\Workout\WorkoutType;
use App\Entity\WorkoutGeneration\WorkoutAiGenerationUsage;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Repository\Workout\MovementRepository;
use App\Services\Workout\AiGeneration\WorkoutAiGenerationActor;
use App\Services\Workout\AiGeneration\WorkoutAiGenerationActorResolver;
use App\Services\Workout\AiGeneration\WorkoutAiGenerationUsageTracker;
use App\Services\Workout\MovementDifficultyService;
use App\Services\Workout\WorkoutCreatorServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/workout-generation-flow')]
class WorkoutGenerationFlowController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MovementRepository $movementRepository,
        private readonly MovementDifficultyService $movementDifficultyService,
        private readonly WorkoutCreatorServiceInterface $workoutCreator,
        private readonly WorkoutAiGenerationActorResolver $aiGenerationActorResolver,
        private readonly WorkoutAiGenerationUsageTracker $aiGenerationUsageTracker,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/options', name: 'workout_generation_flow_options', methods: ['GET'])]
    public function options(): JsonResponse
    {
        return $this->json([
            'workoutTypes' => $this->catalog(WorkoutType::class),
            'movementGenerationTypes' => $this->catalog(WorkoutMovementGenerationType::class),
            'movementTypes' => $this->catalog(MovementType::class),
            'movementDifficulties' => $this->catalog(MovementDifficulty::class),
            'bodyParts' => $this->catalog(BodyPart::class),
            'implements' => $this->catalog(Implement::class),
        ]);
    }

    #[Route('/quota', name: 'workout_generation_flow_quota', methods: ['GET'])]
    public function quota(Request $request): JsonResponse
    {
        return $this->json([
            'quota' => $this->aiGenerationUsageTracker->quotaFor($this->aiGenerationActor($request))->toArray(),
            'timezone' => $this->aiGenerationUsageTracker->quotaTimezone(),
        ]);
    }

    #[Route('', name: 'workout_generation_flow_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = $this->payload($request);
        $this->requirePayloadFields($payload, [
            'name',
            'timeCap',
            'movementGenerationType',
            'workoutType',
            'movementDifficulty',
            'numberOfDifferentMovements',
        ]);

        $workoutGeneration = new WorkoutGeneration();
        $this->hydrate($workoutGeneration, $payload);
        $this->entityManager->persist($workoutGeneration);
        $this->entityManager->flush();

        return $this->json($this->serializeWorkoutGeneration($workoutGeneration), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'workout_generation_flow_update', requirements: ['id' => '[0-9a-fA-F\-]{36}'], methods: ['PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $workoutGeneration = $this->findWorkoutGeneration($id);
        if ($workoutGeneration === null) {
            return $this->json(['error' => 'Workout generation not found'], Response::HTTP_NOT_FOUND);
        }

        $this->hydrate($workoutGeneration, $this->payload($request));
        $this->entityManager->flush();

        return $this->json($this->serializeWorkoutGeneration($workoutGeneration));
    }

    #[Route('/{id}/possible-movements', name: 'workout_generation_flow_possible_movements', requirements: ['id' => '[0-9a-fA-F\-]{36}'], methods: ['GET'])]
    public function possibleMovements(string $id): JsonResponse
    {
        $workoutGeneration = $this->findWorkoutGeneration($id);
        if ($workoutGeneration === null) {
            return $this->json(['error' => 'Workout generation not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'workoutGenerationId' => $id,
            'movements' => array_map(
                fn (Movement $movement): array => $this->serializeMovement($movement),
                $this->getPossibleMovements($workoutGeneration),
            ),
        ]);
    }

    #[Route('/{id}/workout', name: 'workout_generation_flow_generate_workout', requirements: ['id' => '[0-9a-fA-F\-]{36}'], methods: ['POST'])]
    public function generateWorkout(string $id, Request $request): JsonResponse
    {
        $workoutGeneration = $this->findWorkoutGeneration($id);
        if ($workoutGeneration === null) {
            return $this->json(['error' => 'Workout generation not found'], Response::HTTP_NOT_FOUND);
        }

        $actor = $this->aiGenerationActor($request);
        $stimulusContext = $this->stimulusContext($workoutGeneration->getStimulus());
        $generationType = $this->generationUsageType('workout', $workoutGeneration, $stimulusContext);
        $startedAt = microtime(true);
        $baseLogContext = $this->workoutGenerationLogContext($workoutGeneration, $actor, $generationType, $stimulusContext);
        $this->logger->info('monwod.workout_generation.received_request', $baseLogContext);
        if ($stimulusContext['supported'] === false) {
            $this->logger->warning('monwod.workout_generation.unsupported_stimulus', [
                ...$baseLogContext,
                'elapsedMs' => $this->elapsedMs($startedAt),
            ]);

            return $this->unsupportedStimulusResponse($stimulusContext);
        }

        $quota = $this->aiGenerationUsageTracker->quotaFor($actor);
        if (!$quota->isAllowed) {
            $this->logger->info('monwod.workout_generation.quota_reached', [
                ...$baseLogContext,
                'elapsedMs' => $this->elapsedMs($startedAt),
                'quota' => $quota->toArray(),
            ]);

            return $this->quotaExceededResponse($quota);
        }

        try {
            $this->logger->info('monwod.workout_generation.before_create_workout', $baseLogContext);
            $this->applyCanonicalStimulus($workoutGeneration, $stimulusContext);
            $workout = $this->workoutCreator->createWorkout($workoutGeneration);
            $workout = $this->upsertGeneratedWorkout($workoutGeneration, $workout);
            $this->entityManager->persist($workout);
            $this->aiGenerationUsageTracker->recordSuccess(
                $actor,
                WorkoutAiGenerationUsage::ENDPOINT_WORKOUT,
                $generationType,
                $workout->getAiUsage(),
            );
            $this->logger->info('monwod.workout_generation.before_flush', [
                ...$baseLogContext,
                'lastAiUsage' => $workout->getAiUsage(),
            ]);
            $this->entityManager->flush();
        } catch (\InvalidArgumentException $exception) {
            $failure = $this->recordAiGenerationFailure($actor, WorkoutAiGenerationUsage::ENDPOINT_WORKOUT, $generationType, $exception);
            $this->logWorkoutGenerationException('monwod.workout_generation.invalid_request', $exception, $baseLogContext, $startedAt, $failure);

            return $this->json([
                'error' => $exception->getMessage(),
                'code' => 'workout_generation_invalid_request',
                'failureId' => $failure->getId() === null ? null : (string) $failure->getId(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\RuntimeException $exception) {
            $failure = $this->recordAiGenerationFailure($actor, WorkoutAiGenerationUsage::ENDPOINT_WORKOUT, $generationType, $exception);
            $this->logWorkoutGenerationException('monwod.workout_generation.runtime_failure', $exception, $baseLogContext, $startedAt, $failure);

            return $this->aiGenerationFailureResponse($exception->getMessage(), $failure);
        } catch (\Throwable $exception) {
            $failure = $this->recordAiGenerationFailure($actor, WorkoutAiGenerationUsage::ENDPOINT_WORKOUT, $generationType, $exception);
            $this->logWorkoutGenerationException('monwod.workout_generation.unhandled_failure', $exception, $baseLogContext, $startedAt, $failure);

            return $this->aiGenerationFailureResponse(
                sprintf('Workout generation failed: %s: %s', $exception::class, $exception->getMessage()),
                $failure,
            );
        }

        $payload = $this->serializeWorkout($workout);
        $payload['quota'] = $this->aiGenerationUsageTracker->quotaFor($actor)->toArray();
        $this->logger->info('monwod.workout_generation.success', [
            ...$baseLogContext,
            'elapsedMs' => $this->elapsedMs($startedAt),
            'workoutId' => $workout->getId() === null ? null : (string) $workout->getId(),
            'lastAiUsage' => $workout->getAiUsage(),
        ]);

        return $this->json($payload, Response::HTTP_CREATED);
    }

    #[Route('/{id}/variants', name: 'workout_generation_flow_variants', requirements: ['id' => '[0-9a-fA-F\-]{36}'], methods: ['POST'])]
    public function variants(string $id, Request $request): JsonResponse
    {
        $workoutGeneration = $this->findWorkoutGeneration($id);
        if ($workoutGeneration === null) {
            return $this->json(['error' => 'Workout generation not found'], Response::HTTP_NOT_FOUND);
        }

        $actor = $this->aiGenerationActor($request);
        $stimulusContext = $this->stimulusContext($workoutGeneration->getStimulus());
        $generationType = $this->generationUsageType('variants', $workoutGeneration, $stimulusContext);
        if ($stimulusContext['supported'] === false) {
            return $this->unsupportedStimulusResponse($stimulusContext);
        }

        $quota = $this->aiGenerationUsageTracker->quotaFor($actor);
        if (!$quota->isAllowed) {
            return $this->quotaExceededResponse($quota);
        }

        try {
            $this->applyCanonicalStimulus($workoutGeneration, $stimulusContext);
            $variants = $this->workoutCreator->createWorkoutVariants($workoutGeneration);
            $this->aiGenerationUsageTracker->recordSuccess(
                $actor,
                WorkoutAiGenerationUsage::ENDPOINT_VARIANTS,
                $generationType,
                $this->workoutCreator->getLastAiUsage(),
            );
            $this->entityManager->flush();
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\RuntimeException $exception) {
            $failure = $this->recordAiGenerationFailure($actor, WorkoutAiGenerationUsage::ENDPOINT_VARIANTS, $generationType, $exception);

            return $this->aiGenerationFailureResponse($exception->getMessage(), $failure);
        } catch (\Throwable $exception) {
            $failure = $this->recordAiGenerationFailure($actor, WorkoutAiGenerationUsage::ENDPOINT_VARIANTS, $generationType, $exception);

            return $this->aiGenerationFailureResponse(
                sprintf('Workout variant generation failed: %s: %s', $exception::class, $exception->getMessage()),
                $failure,
            );
        }

        return $this->json([
            'workoutGenerationId' => $id,
            'variants' => $variants,
            'quota' => $this->aiGenerationUsageTracker->quotaFor($actor)->toArray(),
        ]);
    }

    private function aiGenerationActor(Request $request): WorkoutAiGenerationActor
    {
        return $this->aiGenerationActorResolver->resolve($request, $this->getUser(), $this->isGranted('ROLE_ADMIN'));
    }

    /**
     * @param array{normalized: ?string, family: ?string, canonical: ?string, supported: bool} $stimulusContext
     */
    private function generationUsageType(string $baseType, WorkoutGeneration $workoutGeneration, array $stimulusContext): string
    {
        $stimulus = $stimulusContext['family'] ?? strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $workoutGeneration->getStimulus()), '-'));
        if ($stimulus === '') {
            return $baseType;
        }

        return substr($baseType.':'.$stimulus, 0, 64);
    }

    /**
     * @param array{normalized: ?string, family: ?string, canonical: ?string, supported: bool} $stimulusContext
     *
     * @return array<string, mixed>
     */
    private function workoutGenerationLogContext(WorkoutGeneration $workoutGeneration, WorkoutAiGenerationActor $actor, string $generationType, array $stimulusContext): array
    {
        return [
            'draftId' => $workoutGeneration->getId() === null ? null : (string) $workoutGeneration->getId(),
            'actorType' => $actor->type,
            'generationType' => $generationType,
            'stimulus' => $workoutGeneration->getStimulus(),
            'normalizedStimulus' => $stimulusContext['normalized'],
            'stimulusFamily' => $stimulusContext['family'],
            'canonicalStimulus' => $stimulusContext['canonical'],
            'workoutType' => $this->workoutTypeNameForLog($workoutGeneration),
            'movementCount' => $this->movementCountForLog($workoutGeneration),
            'timeCap' => $this->timeCapForLog($workoutGeneration),
            'level' => $this->movementDifficultyNameForLog($workoutGeneration),
            'isTeamWorkout' => $this->isTeamWorkoutForLog($workoutGeneration),
        ];
    }

    private function workoutTypeNameForLog(WorkoutGeneration $workoutGeneration): ?string
    {
        try {
            return $workoutGeneration->getWorkoutType()->getName();
        } catch (\Throwable) {
            return null;
        }
    }

    private function movementDifficultyNameForLog(WorkoutGeneration $workoutGeneration): ?string
    {
        try {
            return $workoutGeneration->getMovementDifficulty()->getName();
        } catch (\Throwable) {
            return null;
        }
    }

    private function movementCountForLog(WorkoutGeneration $workoutGeneration): ?int
    {
        try {
            return $workoutGeneration->getNumberOfDifferentMovements();
        } catch (\Throwable) {
            return null;
        }
    }

    private function timeCapForLog(WorkoutGeneration $workoutGeneration): ?int
    {
        try {
            return $workoutGeneration->getTimeCap();
        } catch (\Throwable) {
            return null;
        }
    }

    private function isTeamWorkoutForLog(WorkoutGeneration $workoutGeneration): ?bool
    {
        try {
            return $workoutGeneration->isTeamWorkout();
        } catch (\Throwable) {
            return null;
        }
    }

    private function logWorkoutGenerationException(
        string $event,
        \Throwable $exception,
        array $baseLogContext,
        float $startedAt,
        WorkoutAiGenerationUsage $failure,
    ): void {
        $this->logger->error($event, [
            ...$baseLogContext,
            'elapsedMs' => $this->elapsedMs($startedAt),
            'exceptionClass' => $exception::class,
            'message' => $exception->getMessage(),
            'failureId' => $failure->getId() === null ? null : (string) $failure->getId(),
            'lastAiUsage' => $this->workoutCreator->getLastAiUsage(),
        ]);
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function quotaExceededResponse(\App\Services\Workout\AiGeneration\WorkoutAiGenerationQuota $quota): JsonResponse
    {
        return $this->json([
            'error' => 'Workout generation quota reached.',
            'code' => 'workout_generation_quota_reached',
            'quota' => $quota->toArray(),
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }

    private function aiGenerationFailureResponse(string $message, WorkoutAiGenerationUsage $failure): JsonResponse
    {
        return $this->json([
            'error' => $message,
            'code' => 'workout_generation_failed',
            'failureId' => $failure->getId() === null ? null : (string) $failure->getId(),
        ], Response::HTTP_BAD_GATEWAY);
    }

    /**
     * @param array{normalized: ?string, family: ?string, canonical: ?string, supported: bool} $stimulusContext
     */
    private function unsupportedStimulusResponse(array $stimulusContext): JsonResponse
    {
        return $this->json([
            'error' => sprintf('Unsupported workout stimulus "%s".', $stimulusContext['normalized'] ?? ''),
            'code' => 'workout_generation_unsupported_stimulus',
            'normalizedStimulus' => $stimulusContext['normalized'],
            'supportedStimulusFamilies' => $this->supportedStimulusFamilies(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return array{normalized: ?string, family: ?string, canonical: ?string, supported: bool}
     */
    private function stimulusContext(?string $stimulus): array
    {
        $normalized = $this->normalizeStimulus($stimulus);
        if ($normalized === null) {
            return [
                'normalized' => null,
                'family' => null,
                'canonical' => null,
                'supported' => true,
            ];
        }

        $family = $this->stimulusFamily($normalized);

        return [
            'normalized' => $normalized,
            'family' => $family,
            'canonical' => $family === null ? null : $this->canonicalStimulus($family),
            'supported' => $family !== null,
        ];
    }

    /**
     * @param array{normalized: ?string, family: ?string, canonical: ?string, supported: bool} $stimulusContext
     */
    private function applyCanonicalStimulus(WorkoutGeneration $workoutGeneration, array $stimulusContext): void
    {
        if ($stimulusContext['canonical'] === null) {
            return;
        }

        $workoutGeneration->setStimulus($stimulusContext['canonical']);
    }

    private function normalizeStimulus(?string $stimulus): ?string
    {
        $stimulus = trim((string) $stimulus);
        if ($stimulus === '') {
            return null;
        }

        $stimulus = strtolower(strtr($stimulus, [
            'à' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'ç' => 'c',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
        ]));
        $stimulus = preg_replace('/[^a-z0-9]+/', ' ', $stimulus);

        return trim((string) $stimulus);
    }

    private function stimulusFamily(string $normalizedStimulus): ?string
    {
        if (str_contains($normalizedStimulus, 'strength endurance')
            || str_contains($normalizedStimulus, 'force endurance')
            || str_contains($normalizedStimulus, 'endurance force')
        ) {
            return 'strength_endurance';
        }
        if (str_contains($normalizedStimulus, 'simulation hyrox')
            || str_contains($normalizedStimulus, 'hyrox complet')
            || str_contains($normalizedStimulus, 'full hyrox')
            || str_contains($normalizedStimulus, 'complete hyrox')
        ) {
            return 'hyrox_simulation';
        }
        if (str_contains($normalizedStimulus, 'gymnastics')
            || str_contains($normalizedStimulus, 'gym')
            || str_contains($normalizedStimulus, 'skill')
            || str_contains($normalizedStimulus, 'technique')
        ) {
            return 'gymnastics_skill';
        }
        if (str_contains($normalizedStimulus, 'competition')
            || str_contains($normalizedStimulus, 'mixed modal')
            || str_contains($normalizedStimulus, 'mixed modality')
        ) {
            return 'competition';
        }
        if (str_contains($normalizedStimulus, 'threshold') || str_contains($normalizedStimulus, 'seuil')) {
            return 'threshold';
        }
        if (str_contains($normalizedStimulus, 'engine') || str_contains($normalizedStimulus, 'endurance')) {
            return 'engine';
        }
        if (str_contains($normalizedStimulus, 'sprint')) {
            return 'sprint';
        }
        if (str_contains($normalizedStimulus, 'hyrox')) {
            return 'hyrox_training';
        }
        if (str_contains($normalizedStimulus, 'metcon')) {
            return 'metcon';
        }
        if (str_contains($normalizedStimulus, 'strength') || str_contains($normalizedStimulus, 'force')) {
            return 'strength';
        }

        return null;
    }

    private function canonicalStimulus(string $family): string
    {
        return match ($family) {
            'strength' => 'Strength',
            'sprint' => 'Sprint',
            'threshold' => 'Threshold',
            'engine' => 'Engine',
            'hyrox_training' => 'Entrainement Hyrox',
            'hyrox_simulation' => 'Simulation Hyrox',
            'strength_endurance' => 'Strength Endurance',
            'gymnastics_skill' => 'Gymnastics / Skill',
            'competition' => 'Mixed Modal / Competition',
            'metcon' => 'Metcon',
            default => $family,
        };
    }

    /**
     * @return list<string>
     */
    private function supportedStimulusFamilies(): array
    {
        return [
            'strength',
            'sprint',
            'threshold',
            'engine',
            'hyrox_training',
            'hyrox_simulation',
            'strength_endurance',
            'gymnastics_skill',
            'competition',
            'metcon',
        ];
    }

    private function recordAiGenerationFailure(WorkoutAiGenerationActor $actor, string $endpoint, string $generationType, \Throwable $exception): WorkoutAiGenerationUsage
    {
        $usage = $this->aiGenerationUsageTracker->recordFailure(
            $actor,
            $endpoint,
            $generationType,
            $exception,
            $this->workoutCreator->getLastAiUsage(),
        );
        $this->entityManager->flush();

        return $usage;
    }

    private function upsertGeneratedWorkout(WorkoutGeneration $workoutGeneration, Workout $generatedWorkout): Workout
    {
        $existingWorkout = $this->entityManager->getRepository(Workout::class)->findOneBy([
            'workoutGeneration' => $workoutGeneration,
        ]);

        if (!$existingWorkout instanceof Workout) {
            return $generatedWorkout;
        }

        $existingWorkout
            ->setName($generatedWorkout->getName())
            ->setFlow($generatedWorkout->getFlow())
            ->setNumberOfRounds($generatedWorkout->getNumberOfRounds())
            ->setTimeCap($generatedWorkout->getTimeCap())
            ->setWorkoutType($generatedWorkout->getWorkoutType())
            ->setWorkoutOrigin($generatedWorkout->getWorkoutOrigin())
            ->setGenerationPrompt($generatedWorkout->getGenerationPrompt())
            ->setAiUsage($generatedWorkout->getAiUsage());

        foreach ($existingWorkout->getImplements()->toArray() as $implement) {
            $existingWorkout->removeImplement($implement);
        }
        foreach ($generatedWorkout->getImplements() as $implement) {
            $existingWorkout->addImplement($implement);
        }
        foreach ($existingWorkout->getMovements()->toArray() as $movement) {
            $existingWorkout->removeMovement($movement);
        }
        foreach ($generatedWorkout->getMovements() as $movement) {
            $existingWorkout->addMovement($movement);
        }

        return $existingWorkout;
    }

    private function hydrate(WorkoutGeneration $workoutGeneration, array $payload): void
    {
        if (array_key_exists('name', $payload)) {
            $workoutGeneration->setName($this->requiredString($payload['name'], 'name'));
        }
        if (array_key_exists('stimulus', $payload)) {
            $workoutGeneration->setStimulus($this->nullableString($payload['stimulus'] ?? null, 'stimulus', 120));
        }
        if (array_key_exists('stimulusIntent', $payload)) {
            $workoutGeneration->setStimulusIntent($this->nullableString($payload['stimulusIntent'] ?? null, 'stimulusIntent'));
        }
        if (array_key_exists('timeCap', $payload)) {
            $workoutGeneration->setTimeCap($this->positiveInt($payload['timeCap'], 'timeCap'));
        }
        if (array_key_exists('numberOfDifferentMovements', $payload)) {
            $workoutGeneration->setNumberOfDifferentMovements($this->positiveInt($payload['numberOfDifferentMovements'], 'numberOfDifferentMovements'));
        }
        if (array_key_exists('isTeamWorkout', $payload)) {
            $workoutGeneration->setIsTeamWorkout($this->requiredBool($payload['isTeamWorkout'], 'isTeamWorkout'));
        }
        if (array_key_exists('intervalsTime', $payload)) {
            $workoutGeneration->setIntervalsTime($payload['intervalsTime'] === null ? null : $this->positiveInt($payload['intervalsTime'], 'intervalsTime'));
        }
        if (array_key_exists('intervalsRestTime', $payload)) {
            $workoutGeneration->setIntervalsRestTime($payload['intervalsRestTime'] === null ? null : $this->positiveInt($payload['intervalsRestTime'], 'intervalsRestTime'));
        }
        if (array_key_exists('numberOfRounds', $payload)) {
            $workoutGeneration->setNumberOfRounds($payload['numberOfRounds'] === null ? null : $this->positiveInt($payload['numberOfRounds'], 'numberOfRounds'));
        }
        if (array_key_exists('workoutType', $payload)) {
            $workoutGeneration->setWorkoutType($this->requiredCatalogEntity(WorkoutType::class, $payload['workoutType']));
        }
        if (array_key_exists('movementDifficulty', $payload)) {
            $workoutGeneration->setMovementDifficulty($this->requiredCatalogEntity(MovementDifficulty::class, $payload['movementDifficulty']));
        }
        if (array_key_exists('movementGenerationType', $payload)) {
            $workoutGeneration->setMovementGenerationType($this->requiredCatalogEntity(WorkoutMovementGenerationType::class, $payload['movementGenerationType']));
        }
        if (array_key_exists('movementTypes', $payload)) {
            $workoutGeneration->setMovementTypes($this->catalogEntities(MovementType::class, $payload['movementTypes'], 'movementTypes'));
        }
        if (array_key_exists('availableImplements', $payload)) {
            $workoutGeneration->setAvailableImplements($this->catalogEntities(Implement::class, $payload['availableImplements'], 'availableImplements'));
        }
        if (array_key_exists('mandatoryBodyParts', $payload)) {
            $workoutGeneration->setMandatoryBodyParts($this->catalogEntities(BodyPart::class, $payload['mandatoryBodyParts'], 'mandatoryBodyParts'));
        }
        if (array_key_exists('bannedMovements', $payload)) {
            $workoutGeneration->setBannedMovements($this->movementEntities($payload['bannedMovements'], 'bannedMovements'));
        }
        if (array_key_exists('mandatoryMovements', $payload)) {
            $workoutGeneration->setMandatoryMovements($this->movementEntities($payload['mandatoryMovements'], 'mandatoryMovements'));
        }

        $this->assertMandatoryMovementCountFitsRequestedCount($workoutGeneration);
        $this->assertNoConflictingMovementFilters($workoutGeneration);
    }

    private function assertMandatoryMovementCountFitsRequestedCount(WorkoutGeneration $workoutGeneration): void
    {
        if (count($workoutGeneration->getMandatoryMovements()) > $workoutGeneration->getNumberOfDifferentMovements()) {
            throw new UnprocessableEntityHttpException('The number of mandatory movements cannot be greater than the number of different movements.');
        }
    }

    private function assertNoConflictingMovementFilters(WorkoutGeneration $workoutGeneration): void
    {
        $bannedMovementNames = [];
        foreach ($workoutGeneration->getBannedMovements() as $movement) {
            $bannedMovementNames[$this->normalizeMovementName($movement->getName())] = true;
        }

        foreach ($workoutGeneration->getMandatoryMovements() as $movement) {
            if (isset($bannedMovementNames[$this->normalizeMovementName($movement->getName())])) {
                throw new UnprocessableEntityHttpException(sprintf('Movement "%s" cannot be both mandatory and banned.', $movement->getName()));
            }
        }
    }

    private function getPossibleMovements(WorkoutGeneration $workoutGeneration): array
    {
        return $this->movementRepository->getMovementsByMovementTypesAndDifficultyAndImplementsAndMuscles(
            $workoutGeneration->getMovementTypes()->toArray(),
            $this->movementDifficultyService->getWorkoutDifficultiesFromOne($workoutGeneration->getMovementDifficulty()),
            $workoutGeneration->getBannedMovements()->toArray(),
            $workoutGeneration->getAvailableImplements()->toArray(),
            $workoutGeneration->getMandatoryBodyParts()->toArray(),
        );
    }

    private function findWorkoutGeneration(string $id): ?WorkoutGeneration
    {
        return $this->entityManager->getRepository(WorkoutGeneration::class)->find(Uuid::fromString($id));
    }

    private function payload(Request $request): array
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new BadRequestHttpException('Invalid JSON request body.', $exception);
        }

        if (!is_array($payload)) {
            throw new BadRequestHttpException('JSON request body must be an object.');
        }

        return $payload;
    }

    /**
     * @param list<string> $fieldNames
     */
    private function requirePayloadFields(array $payload, array $fieldNames): void
    {
        foreach ($fieldNames as $fieldName) {
            if (!array_key_exists($fieldName, $payload)) {
                throw new UnprocessableEntityHttpException(sprintf('"%s" is required.', $fieldName));
            }
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     */
    private function requiredCatalogEntity(string $className, mixed $identifier): object
    {
        $entity = $this->catalogEntity($className, $identifier);
        if ($entity === null) {
            throw new UnprocessableEntityHttpException(sprintf('Invalid workout generation catalog reference "%s".', $this->catalogReferenceLabel($identifier)));
        }

        return $entity;
    }

    private function catalogReferenceLabel(mixed $identifier): string
    {
        if (is_scalar($identifier) || $identifier instanceof \Stringable) {
            return (string) $identifier;
        }

        return get_debug_type($identifier);
    }

    private function positiveInt(mixed $value, string $fieldName): int
    {
        if (!is_int($value) && !(is_string($value) && preg_match('/^\d+$/', $value) === 1)) {
            throw new UnprocessableEntityHttpException(sprintf('"%s" must be a positive integer.', $fieldName));
        }

        $integer = (int) $value;
        if ($integer < 1) {
            throw new UnprocessableEntityHttpException(sprintf('"%s" must be a positive integer.', $fieldName));
        }

        return $integer;
    }

    private function requiredString(mixed $value, string $fieldName): string
    {
        if (!is_scalar($value) && !$value instanceof \Stringable) {
            throw new UnprocessableEntityHttpException(sprintf('"%s" must be a non-empty string.', $fieldName));
        }

        $value = trim((string) $value);
        if ($value === '') {
            throw new UnprocessableEntityHttpException(sprintf('"%s" must be a non-empty string.', $fieldName));
        }

        return $value;
    }

    private function requiredBool(mixed $value, string $fieldName): bool
    {
        if (!is_bool($value)) {
            throw new UnprocessableEntityHttpException(sprintf('"%s" must be a boolean.', $fieldName));
        }

        return $value;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T|null
     */
    private function catalogEntity(string $className, mixed $identifier): ?object
    {
        if (!is_string($identifier) || $identifier === '') {
            return null;
        }

        $repository = $this->entityManager->getRepository($className);
        if (Uuid::isValid($identifier)) {
            return $repository->find(Uuid::fromString($identifier));
        }

        return $repository->findOneBy(['name' => $identifier]);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return list<T>
     */
    private function catalogEntities(string $className, mixed $identifiers, string $fieldName): array
    {
        if (!is_array($identifiers)) {
            throw new UnprocessableEntityHttpException('Workout generation catalog references must be an array.');
        }
        $seenIdentifiers = [];

        return array_values(array_map(function (mixed $identifier) use ($className, $fieldName, &$seenIdentifiers): object {
            if (is_string($identifier)) {
                $normalizedIdentifier = strtolower($identifier);
                if (isset($seenIdentifiers[$normalizedIdentifier])) {
                    throw new UnprocessableEntityHttpException(sprintf('Duplicate workout generation catalog reference "%s" in %s.', $identifier, $fieldName));
                }
                $seenIdentifiers[$normalizedIdentifier] = true;
            }

            $entity = $this->catalogEntity($className, $identifier);
            if ($entity === null) {
                throw new UnprocessableEntityHttpException(sprintf('Invalid workout generation catalog reference "%s".', $this->catalogReferenceLabel($identifier)));
            }

            return $entity;
        }, $identifiers));
    }

    /**
     * @return list<Movement>
     */
    private function movementEntities(mixed $identifiers, string $fieldName): array
    {
        if (!is_array($identifiers)) {
            throw new UnprocessableEntityHttpException('Movement references must be an array.');
        }

        $repository = $this->entityManager->getRepository(Movement::class);
        $seenIdentifiers = [];

        return array_values(array_map(function (mixed $identifier) use ($repository, $fieldName, &$seenIdentifiers): Movement {
            if (!is_string($identifier) || !Uuid::isValid($identifier)) {
                throw new UnprocessableEntityHttpException(sprintf('Invalid movement reference "%s".', $this->catalogReferenceLabel($identifier)));
            }

            $normalizedIdentifier = strtolower($identifier);
            if (isset($seenIdentifiers[$normalizedIdentifier])) {
                throw new UnprocessableEntityHttpException(sprintf('Duplicate movement reference "%s" in %s.', $identifier, $fieldName));
            }
            $seenIdentifiers[$normalizedIdentifier] = true;

            $movement = $repository->find(Uuid::fromString($identifier));
            if (!$movement instanceof Movement) {
                throw new UnprocessableEntityHttpException(sprintf('Invalid movement reference "%s".', $identifier));
            }

            return $movement;
        }, $identifiers));
    }

    private function nullableString(mixed $value, string $fieldName, ?int $maxLength = null): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_scalar($value) && !$value instanceof \Stringable) {
            throw new UnprocessableEntityHttpException(sprintf('"%s" must be a string or null.', $fieldName));
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return $maxLength === null ? $value : substr($value, 0, $maxLength);
    }

    /**
     * @param class-string<object> $className
     *
     * @return list<array{id: string, name: string}>
     */
    private function catalog(string $className): array
    {
        $entities = $this->entityManager->getRepository($className)->findBy([], ['name' => 'ASC']);
        $seenNames = [];

        return array_values(array_filter(array_map(static function (object $entity) use (&$seenNames): ?array {
            if (isset($seenNames[$entity->getName()])) {
                return null;
            }

            $seenNames[$entity->getName()] = true;

            return [
                'id' => $entity->getId()->toString(),
                'name' => $entity->getName(),
            ];
        }, $entities)));
    }

    private function serializeWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
    {
        return [
            'id' => $workoutGeneration->getId()?->toString(),
            'name' => $workoutGeneration->getName(),
            'stimulus' => $workoutGeneration->getStimulus(),
            'stimulusIntent' => $workoutGeneration->getStimulusIntent(),
            'timeCap' => $workoutGeneration->getTimeCap(),
            'workoutType' => $this->serializeCatalogEntity($workoutGeneration->getWorkoutType()),
            'movementGenerationType' => $this->serializeCatalogEntity($workoutGeneration->getMovementGenerationType()),
            'movementDifficulty' => $this->serializeCatalogEntity($workoutGeneration->getMovementDifficulty()),
            'movementTypes' => array_map($this->serializeCatalogEntity(...), $workoutGeneration->getMovementTypes()->toArray()),
            'availableImplements' => array_map($this->serializeCatalogEntity(...), $workoutGeneration->getAvailableImplements()->toArray()),
            'mandatoryBodyParts' => array_map($this->serializeCatalogEntity(...), $workoutGeneration->getMandatoryBodyParts()->toArray()),
            'bannedMovements' => array_map($this->serializeMovement(...), $workoutGeneration->getBannedMovements()->toArray()),
            'mandatoryMovements' => array_map($this->serializeMovement(...), $workoutGeneration->getMandatoryMovements()->toArray()),
            'numberOfDifferentMovements' => $workoutGeneration->getNumberOfDifferentMovements(),
            'isTeamWorkout' => $workoutGeneration->isTeamWorkout(),
            'intervalsTime' => $workoutGeneration->getIntervalsTime(),
            'intervalsRestTime' => $workoutGeneration->getIntervalsRestTime(),
            'numberOfRounds' => $workoutGeneration->getNumberOfRounds(),
        ];
    }

    private function serializeWorkout(Workout $workout): array
    {
        $payload = [
            'id' => $workout->getId()?->toString(),
            'name' => $workout->getName(),
            'flow' => $workout->getFlow(),
            'timeCap' => $workout->getTimeCap(),
            'numberOfRounds' => $workout->getNumberOfRounds(),
            'workoutType' => $workout->getWorkoutType() ? $this->serializeCatalogEntity($workout->getWorkoutType()) : null,
            'movements' => array_map($this->serializeMovement(...), $workout->getMovements()->toArray()),
            'implements' => array_map($this->serializeCatalogEntity(...), $workout->getImplements()->toArray()),
        ];

        if ($this->isGranted('ROLE_ADMIN')) {
            $payload['generationPrompt'] = $workout->getGenerationPrompt();
            $payload['aiUsage'] = $workout->getAiUsage();
        }

        return $payload;
    }

    private function serializeCatalogEntity(object $entity): array
    {
        return [
            'id' => $entity->getId()->toString(),
            'name' => $entity->getName(),
        ];
    }

    private function serializeMovement(Movement $movement): array
    {
        return [
            'id' => $movement->getId()->toString(),
            'name' => $movement->getName(),
        ];
    }

    private function normalizeMovementName(string $name): string
    {
        return strtolower(trim($name));
    }
}
