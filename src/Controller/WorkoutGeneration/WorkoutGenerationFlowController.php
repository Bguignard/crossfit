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
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Repository\Workout\MovementRepository;
use App\Services\Workout\MovementDifficultyService;
use App\Services\Workout\WorkoutCreatorServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
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
    public function generateWorkout(string $id): JsonResponse
    {
        $workoutGeneration = $this->findWorkoutGeneration($id);
        if ($workoutGeneration === null) {
            return $this->json(['error' => 'Workout generation not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $workout = $this->workoutCreator->createWorkout($workoutGeneration);
            $workout = $this->upsertGeneratedWorkout($workoutGeneration, $workout);
            $this->entityManager->persist($workout);
            $this->entityManager->flush();
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        } catch (\Throwable $exception) {
            return $this->json([
                'error' => sprintf('Workout generation failed: %s: %s', $exception::class, $exception->getMessage()),
            ], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json($this->serializeWorkout($workout), Response::HTTP_CREATED);
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
            ->setGenerationPrompt($generatedWorkout->getGenerationPrompt());

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
            $workoutGeneration->setStimulus($this->nullableString($payload['stimulus'] ?? null, 120));
        }
        if (array_key_exists('stimulusIntent', $payload)) {
            $workoutGeneration->setStimulusIntent($this->nullableString($payload['stimulusIntent'] ?? null));
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
            $workoutGeneration->setMovementTypes($this->catalogEntities(MovementType::class, $payload['movementTypes']));
        }
        if (array_key_exists('availableImplements', $payload)) {
            $workoutGeneration->setAvailableImplements($this->catalogEntities(Implement::class, $payload['availableImplements']));
        }
        if (array_key_exists('mandatoryBodyParts', $payload)) {
            $workoutGeneration->setMandatoryBodyParts($this->catalogEntities(BodyPart::class, $payload['mandatoryBodyParts']));
        }
        if (array_key_exists('bannedMovements', $payload)) {
            $workoutGeneration->setBannedMovements($this->movementEntities($payload['bannedMovements']));
        }
        if (array_key_exists('mandatoryMovements', $payload)) {
            $workoutGeneration->setMandatoryMovements($this->movementEntities($payload['mandatoryMovements']));
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

        return is_array($payload) ? $payload : [];
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
    private function catalogEntities(string $className, mixed $identifiers): array
    {
        if (!is_array($identifiers)) {
            throw new UnprocessableEntityHttpException('Workout generation catalog references must be an array.');
        }

        return array_values(array_map(function (mixed $identifier) use ($className): object {
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
    private function movementEntities(mixed $identifiers): array
    {
        if (!is_array($identifiers)) {
            throw new UnprocessableEntityHttpException('Movement references must be an array.');
        }

        $repository = $this->entityManager->getRepository(Movement::class);

        return array_values(array_map(function (mixed $identifier) use ($repository): Movement {
            if (!is_string($identifier) || !Uuid::isValid($identifier)) {
                throw new UnprocessableEntityHttpException(sprintf('Invalid movement reference "%s".', $this->catalogReferenceLabel($identifier)));
            }

            $movement = $repository->find(Uuid::fromString($identifier));
            if (!$movement instanceof Movement) {
                throw new UnprocessableEntityHttpException(sprintf('Invalid movement reference "%s".', $identifier));
            }

            return $movement;
        }, $identifiers));
    }

    private function nullableString(mixed $value, ?int $maxLength = null): ?string
    {
        if ($value === null) {
            return null;
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
}
