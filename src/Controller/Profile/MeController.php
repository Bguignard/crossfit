<?php

namespace App\Controller\Profile;

use App\Entity\Competition\Athlete;
use App\Entity\Product\Enum\AnalysisRequestStatusEnum;
use App\Entity\Product\Enum\PerformanceMetricCategoryEnum;
use App\Entity\Product\Enum\PerformanceMetricKeyEnum;
use App\Entity\Product\Enum\PerformanceMetricValueTypeEnum;
use App\Entity\Product\Enum\PersonalProgrammingFamilyEnum;
use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Entity\Product\UserAthleteProfile;
use App\Entity\Product\UserPerformanceMetric;
use App\Entity\Product\UserPerformanceProfile;
use App\Entity\Security\User;
use App\Services\Profile\PersonalAnalysisCompetitionSnapshotBuilder;
use App\Services\Profile\QueuedAiRequestMessengerDispatcher;
use App\Services\Profile\UserAvatarResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/me')]
#[IsGranted('ROLE_USER')]
class MeController extends AbstractController
{
    private const ANALYSIS_REQUEST_COOLDOWN = 'P1D';
    private const PROGRAMMING_DURATION_WEEKS = ['min' => 4, 'max' => 8, 'default' => 8];
    private const PROGRAMMING_SESSIONS_PER_WEEK = ['min' => 1, 'max' => 6, 'default' => 5];
    private const PROGRAMMING_SESSION_DURATION_MINUTES = ['min' => 30, 'max' => 180, 'default' => 60];

    private const VALID_LINK_TYPES = [
        UserAthleteProfile::LINK_SELF,
        UserAthleteProfile::LINK_COACHED,
        UserAthleteProfile::LINK_FOLLOWED,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserAvatarResolver $userAvatarResolver,
        private readonly PersonalAnalysisCompetitionSnapshotBuilder $competitionSnapshotBuilder,
        private readonly QueuedAiRequestMessengerDispatcher $queuedAiRequestDispatcher,
    ) {
    }

    #[Route('', name: 'api_me_dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        return $this->json($this->buildDashboardPayload($this->currentUser()));
    }

    #[Route('/athlete-profiles', name: 'api_me_link_athlete_profile', methods: ['POST'])]
    public function linkAthlete(Request $request): JsonResponse
    {
        $payload = $this->jsonPayload($request);
        $athleteId = $payload['athleteId'] ?? null;
        if (!is_string($athleteId) || $athleteId === '') {
            return $this->json(['error' => 'athleteId is required.'], Response::HTTP_BAD_REQUEST);
        }

        $linkType = $payload['linkType'] ?? UserAthleteProfile::LINK_SELF;
        if (!is_string($linkType) || !in_array($linkType, self::VALID_LINK_TYPES, true)) {
            return $this->json(['error' => 'Invalid linkType.'], Response::HTTP_BAD_REQUEST);
        }

        /** @var Athlete|null $athlete */
        $athlete = $this->entityManager->getRepository(Athlete::class)->find($athleteId);
        if ($athlete === null) {
            return $this->json(['error' => 'Athlete not found.'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->currentUser();
        /** @var UserAthleteProfile|null $profile */
        $profile = $this->entityManager->getRepository(UserAthleteProfile::class)->findOneBy([
            'user' => $user,
            'athlete' => $athlete,
        ]);

        $created = false;
        if ($profile === null) {
            $profile = new UserAthleteProfile($user, $athlete, $linkType);
            $this->entityManager->persist($profile);
            $created = true;
        } else {
            $profile->setLinkType($linkType);
        }

        if (array_key_exists('primaryProfile', $payload)) {
            $profile->setPrimaryProfile((bool) $payload['primaryProfile']);
        }

        $this->entityManager->flush();

        return $this->json(
            ['athleteProfile' => $this->serializeAthleteProfile($profile)],
            $created ? Response::HTTP_CREATED : Response::HTTP_OK
        );
    }

    #[Route('/athlete-profiles/{id}', name: 'api_me_unlink_athlete_profile', methods: ['DELETE'])]
    public function unlinkAthlete(string $id): JsonResponse
    {
        /** @var UserAthleteProfile|null $profile */
        $profile = $this->entityManager->getRepository(UserAthleteProfile::class)->find($id);
        if ($profile === null || $profile->getUser() !== $this->currentUser()) {
            return $this->json(['error' => 'Athlete profile not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($profile);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/performance-profile', name: 'api_me_update_performance_profile', methods: ['PUT'])]
    public function updatePerformanceProfile(Request $request): JsonResponse
    {
        $payload = $this->jsonPayload($request);
        $metricsPayload = $payload['metrics'] ?? null;
        if (!is_array($metricsPayload)) {
            return $this->json(['error' => 'metrics must be an array.'], Response::HTTP_BAD_REQUEST);
        }

        $profile = $this->getOrCreatePerformanceProfile($this->currentUser());
        foreach ($metricsPayload as $metricPayload) {
            if (!is_array($metricPayload)) {
                return $this->json(['error' => 'Each metric must be an object.'], Response::HTTP_BAD_REQUEST);
            }

            $error = $this->upsertMetric($profile, $metricPayload);
            if ($error !== null) {
                return $this->json(['error' => $error], Response::HTTP_BAD_REQUEST);
            }
        }

        if (($payload['completed'] ?? false) === true) {
            $profile->markCompleted();
        }

        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        return $this->json(['performanceProfile' => $this->serializePerformanceProfile($profile)]);
    }

    #[Route('/requests', name: 'api_me_requests', methods: ['GET'])]
    public function requests(): JsonResponse
    {
        $user = $this->currentUser();
        $this->queuedAiRequestDispatcher->enqueueQueuedRequestsForUser($user);

        return $this->json([
            'analysisRequests' => array_map(
                fn (PerformanceAnalysisRequest $request): array => $this->serializeAnalysisRequest($request),
                $this->entityManager->getRepository(PerformanceAnalysisRequest::class)->findBy(
                    ['user' => $user],
                    ['createdAt' => 'DESC'],
                    20
                )
            ),
            'programmingRequests' => array_map(
                fn (ProgrammingGenerationRequest $request): array => $this->serializeProgrammingRequest($request),
                $this->entityManager->getRepository(ProgrammingGenerationRequest::class)->findBy(
                    ['user' => $user],
                    ['createdAt' => 'DESC'],
                    20
                )
            ),
            'programmingSessionDetailRequests' => array_map(
                fn (ProgrammingSessionDetailRequest $request): array => $this->serializeProgrammingSessionDetailRequest($request),
                $this->entityManager->getRepository(ProgrammingSessionDetailRequest::class)->findBy(
                    ['user' => $user],
                    ['createdAt' => 'DESC'],
                    20
                )
            ),
        ]);
    }

    #[Route('/performance-analysis-requests', name: 'api_me_create_performance_analysis_request', methods: ['POST'])]
    public function createPerformanceAnalysisRequest(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        $latestRequest = $this->latestAnalysisRequest($user);
        $nextAvailableAt = $latestRequest?->getCreatedAt()->add(new \DateInterval(self::ANALYSIS_REQUEST_COOLDOWN));
        if ($nextAvailableAt !== null && $nextAvailableAt > new \DateTimeImmutable()) {
            return $this->json([
                'error' => 'A performance analysis request can only be created once every 24 hours.',
                'latestAnalysisRequest' => $this->serializeAnalysisRequest($latestRequest),
                'nextAvailableAt' => $this->date($nextAvailableAt),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $payload = $this->jsonPayload($request);
        $athleteProfiles = $this->personalAnalysisAthleteProfiles($user);
        $profile = $this->getLatestPerformanceProfile($user);
        if ($profile === null) {
            if ($athleteProfiles === []) {
                return $this->json(['error' => 'Performance metrics or a linked competition profile are required.'], Response::HTTP_BAD_REQUEST);
            }

            $profile = new UserPerformanceProfile($user);
            $this->entityManager->persist($profile);
        } elseif (!$this->hasAnyProvidedMetric($profile) && $athleteProfiles === []) {
            return $this->json(['error' => 'Performance metrics or a linked competition profile are required.'], Response::HTTP_BAD_REQUEST);
        }

        $primaryAthleteProfile = $this->primaryAnalysisAthleteProfile($athleteProfiles);
        $parameters = $this->arrayPayload($payload['parameters'] ?? []);
        $analysisRequest = (new PerformanceAnalysisRequest(
            $user,
            $profile,
            $primaryAthleteProfile,
            $parameters,
            $this->buildAnalysisSnapshot($profile, $athleteProfiles)
        ))->markQueued();

        $this->entityManager->persist($analysisRequest);
        $this->entityManager->flush();
        $this->queuedAiRequestDispatcher->enqueuePerformanceAnalysisRequest($analysisRequest, force: true);

        return $this->json(
            ['analysisRequest' => $this->serializeAnalysisRequest($analysisRequest)],
            Response::HTTP_CREATED
        );
    }

    #[Route('/programming-generation-requests', name: 'api_me_create_programming_generation_request', methods: ['POST'])]
    public function createProgrammingGenerationRequest(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        $payload = $this->jsonPayload($request);
        $typeValue = $payload['type'] ?? null;
        if (!is_string($typeValue)) {
            return $this->json(['error' => 'type is required.'], Response::HTTP_BAD_REQUEST);
        }

        $type = ProgrammingGenerationTypeEnum::tryFrom($typeValue);
        if ($type === null) {
            return $this->json(['error' => 'Invalid programming generation type.'], Response::HTTP_BAD_REQUEST);
        }
        if ($type !== ProgrammingGenerationTypeEnum::INDIVIDUAL) {
            return $this->json([
                'error' => 'Only individual programming generation is available for now.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $profile = $this->getLatestPerformanceProfile($user);
        $constraints = $this->arrayPayload($payload['constraints'] ?? []);
        try {
            $constraints = $this->normaliseProgrammingConstraints($constraints);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $sourceAnalysisRequest = $this->sourceAnalysisRequest($user, $constraints);
        if ($sourceAnalysisRequest === null) {
            return $this->json([
                'error' => 'A completed performance analysis is required before programming generation.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $programmingRequest = (new ProgrammingGenerationRequest(
            $user,
            $type,
            $constraints,
            $this->buildProgrammingSnapshot($profile, $sourceAnalysisRequest)
        ))
            ->setPerformanceProfile($profile)
            ->markQueued();

        $this->entityManager->persist($programmingRequest);
        $this->entityManager->flush();
        $this->queuedAiRequestDispatcher->enqueueProgrammingGenerationRequest($programmingRequest, force: true);

        return $this->json(
            ['programmingRequest' => $this->serializeProgrammingRequest($programmingRequest)],
            Response::HTTP_CREATED
        );
    }

    #[Route('/programming-generation-requests/{id}/session-detail-requests', name: 'api_me_create_programming_session_detail_request', methods: ['POST'])]
    public function createProgrammingSessionDetailRequest(string $id): JsonResponse
    {
        $user = $this->currentUser();
        /** @var ProgrammingGenerationRequest|null $programmingRequest */
        $programmingRequest = $this->entityManager->getRepository(ProgrammingGenerationRequest::class)->find($id);
        if ($programmingRequest === null || $programmingRequest->getUser() !== $user) {
            return $this->json(['error' => 'Programming request not found.'], Response::HTTP_NOT_FOUND);
        }

        if (
            $programmingRequest->getStatus() !== ProgrammingGenerationRequestStatusEnum::COMPLETED
            || $programmingRequest->getGeneratedProgramming() === null
        ) {
            return $this->json([
                'error' => 'A completed programming generation is required before detailing sessions.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $existingRequest = $this->latestProgrammingSessionDetailRequest($user, $programmingRequest);
        if (
            $existingRequest !== null
            && $existingRequest->getStatus() !== ProgrammingGenerationRequestStatusEnum::FAILED
        ) {
            $this->queuedAiRequestDispatcher->enqueueProgrammingSessionDetailRequest($existingRequest);

            return $this->json(['programmingSessionDetailRequest' => $this->serializeProgrammingSessionDetailRequest($existingRequest)]);
        }

        $detailRequest = (new ProgrammingSessionDetailRequest(
            $user,
            $programmingRequest,
            $this->buildProgrammingDetailSnapshot($programmingRequest)
        ))->markQueued();

        $this->entityManager->persist($detailRequest);
        $this->entityManager->flush();
        $this->queuedAiRequestDispatcher->enqueueProgrammingSessionDetailRequest($detailRequest, force: true);

        return $this->json(
            ['programmingSessionDetailRequest' => $this->serializeProgrammingSessionDetailRequest($detailRequest)],
            Response::HTTP_CREATED
        );
    }

    #[Route('/programming-session-detail-requests/{id}/current-session', name: 'api_me_update_programming_session_detail_current_session', methods: ['PATCH'])]
    public function updateProgrammingSessionDetailCurrentSession(string $id, Request $request): JsonResponse
    {
        $detailRequest = $this->programmingSessionDetailRequestForCurrentUser($id);
        if ($detailRequest === null) {
            return $this->json(['error' => 'Programming session detail request not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($detailRequest->getStatus() !== ProgrammingGenerationRequestStatusEnum::COMPLETED) {
            return $this->json(['error' => 'Completed session details are required.'], Response::HTTP_BAD_REQUEST);
        }

        $sessions = $this->programmingDetailSessions($detailRequest);
        if ($sessions === []) {
            return $this->json(['error' => 'No detailed sessions are available.'], Response::HTTP_BAD_REQUEST);
        }

        $payload = $this->jsonPayload($request);
        $sessionIndex = $payload['sessionIndex'] ?? null;
        if (!is_int($sessionIndex)) {
            return $this->json(['error' => 'sessionIndex must be an integer.'], Response::HTTP_BAD_REQUEST);
        }

        $detailRequest->setCurrentSessionIndex(min(max(0, $sessionIndex), count($sessions) - 1));
        $this->entityManager->flush();

        return $this->json(['programmingSessionDetailRequest' => $this->serializeProgrammingSessionDetailRequest($detailRequest)]);
    }

    #[Route('/programming-session-detail-requests/{id}/complete-current-session', name: 'api_me_complete_programming_session_detail_current_session', methods: ['POST'])]
    public function completeProgrammingSessionDetailCurrentSession(string $id): JsonResponse
    {
        $detailRequest = $this->programmingSessionDetailRequestForCurrentUser($id);
        if ($detailRequest === null) {
            return $this->json(['error' => 'Programming session detail request not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($detailRequest->getStatus() !== ProgrammingGenerationRequestStatusEnum::COMPLETED) {
            return $this->json(['error' => 'Completed session details are required.'], Response::HTTP_BAD_REQUEST);
        }

        $sessions = $this->programmingDetailSessions($detailRequest);
        if ($sessions === []) {
            return $this->json(['error' => 'No detailed sessions are available.'], Response::HTTP_BAD_REQUEST);
        }

        $currentIndex = min($detailRequest->getCurrentSessionIndex(), count($sessions) - 1);
        $completedSessionKeys = $detailRequest->getCompletedSessionKeys();
        $currentSessionKey = $this->programmingSessionKey($sessions[$currentIndex], $currentIndex);
        if (!in_array($currentSessionKey, $completedSessionKeys, true)) {
            $completedSessionKeys[] = $currentSessionKey;
        }

        $detailRequest
            ->setCompletedSessionKeys($completedSessionKeys)
            ->setCurrentSessionIndex(min($currentIndex + 1, count($sessions) - 1));

        $this->entityManager->flush();

        return $this->json(['programmingSessionDetailRequest' => $this->serializeProgrammingSessionDetailRequest($detailRequest)]);
    }

    #[Route('/performance-profile/metrics/{key}', name: 'api_me_delete_performance_metric', methods: ['DELETE'])]
    public function deletePerformanceMetric(string $key): JsonResponse
    {
        $metricKey = PerformanceMetricKeyEnum::tryFrom($key);
        if ($metricKey === null) {
            return $this->json(['error' => sprintf('Unknown metric key "%s".', $key)], Response::HTTP_BAD_REQUEST);
        }

        $profile = $this->getLatestPerformanceProfile($this->currentUser());
        if ($profile === null) {
            return $this->json(['performanceProfile' => null]);
        }

        $metric = $profile->getMetric($metricKey);
        if ($metric !== null) {
            $profile->removeMetric($metric);
            $this->entityManager->remove($metric);
            $this->entityManager->flush();
        }

        return $this->json(['performanceProfile' => $this->serializePerformanceProfile($profile)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboardPayload(User $user): array
    {
        $profile = $this->getLatestPerformanceProfile($user);

        return [
            'user' => [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'displayName' => $user->getDisplayName(),
                'avatarUrl' => $this->userAvatarResolver->avatarUrl($user),
                'emailVerified' => $user->isEmailVerified(),
            ],
            'athleteProfiles' => array_map(
                fn (UserAthleteProfile $athleteProfile): array => $this->serializeAthleteProfile($athleteProfile),
                $user->getAthleteProfiles()->toArray()
            ),
            'performanceProfile' => $profile !== null ? $this->serializePerformanceProfile($profile) : null,
            'performanceMetricCatalog' => $this->buildMetricCatalog($profile),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAthleteProfile(UserAthleteProfile $profile): array
    {
        $athlete = $profile->getAthlete();

        return [
            'id' => (string) $profile->getId(),
            'linkType' => $profile->getLinkType(),
            'primaryProfile' => $profile->isPrimaryProfile(),
            'verifiedAt' => $this->date($profile->getVerifiedAt()),
            'athlete' => [
                'id' => (string) $athlete->getId(),
                'displayName' => $athlete->getDisplayName(),
                'firstName' => $athlete->getFirstName(),
                'lastName' => $athlete->getLastName(),
                'gender' => $athlete->getGender(),
                'country' => $athlete->getCountry(),
                'sourceName' => $athlete->getSourceName(),
                'externalId' => $athlete->getExternalId(),
                'sourceUrl' => $athlete->getSourceUrl(),
                'avatarUrl' => $athlete->getAvatarUrl(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePerformanceProfile(UserPerformanceProfile $profile): array
    {
        $providedMetrics = [];
        foreach ($profile->getMetrics() as $metric) {
            $providedMetrics[$metric->getMetricKey()->value] = $this->serializeMetric($metric);
        }

        return [
            'id' => (string) $profile->getId(),
            'createdAt' => $this->date($profile->getCreatedAt()),
            'updatedAt' => $this->date($profile->getUpdatedAt()),
            'completedAt' => $this->date($profile->getCompletedAt()),
            'eligibleForPerformanceAnalysis' => $profile->isEligibleForPerformanceAnalysis(),
            'analysisDataQuality' => $profile->analysisDataQuality(),
            'missingRequiredMetrics' => $this->missingRequiredMetrics($profile),
            'availableGymnasticsCapacityMetrics' => array_map(
                static fn (PerformanceMetricKeyEnum $metricKey): string => $metricKey->value,
                $profile->availableGymnasticsCapacityMetrics()
            ),
            'metrics' => array_values($providedMetrics),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMetric(UserPerformanceMetric $metric): array
    {
        return [
            'key' => $metric->getMetricKey()->value,
            'label' => $this->label($metric->getMetricKey()->value),
            'category' => $metric->getCategory()->value,
            'valueType' => $metric->getValueType()->value,
            'numericValue' => $metric->getNumericValue(),
            'booleanValue' => $metric->getBooleanValue(),
            'unit' => $metric->getUnit(),
            'notes' => $metric->getNotes(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAnalysisRequest(PerformanceAnalysisRequest $request): array
    {
        return [
            'id' => (string) $request->getId(),
            'status' => $request->getStatus()->value,
            'eligibleAtCreation' => $request->wasEligibleAtCreation(),
            'parameters' => $request->getParameters(),
            'inputSnapshot' => $request->getInputSnapshot(),
            'result' => $request->getResult(),
            'errorMessage' => $this->publicAiErrorMessage(
                $request->getStatus()->value,
                'analysis'
            ),
            'createdAt' => $this->date($request->getCreatedAt()),
            'updatedAt' => $this->date($request->getUpdatedAt()),
            'queuedAt' => $this->date($request->getQueuedAt()),
            'messengerEnqueuedAt' => $this->date($request->getMessengerEnqueuedAt()),
            'startedAt' => $this->date($request->getStartedAt()),
            'completedAt' => $this->date($request->getCompletedAt()),
            'athleteProfile' => $request->getAthleteProfile() !== null
                ? $this->serializeAthleteProfile($request->getAthleteProfile())
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProgrammingRequest(ProgrammingGenerationRequest $request): array
    {
        return [
            'id' => (string) $request->getId(),
            'type' => $request->getType()->value,
            'status' => $request->getStatus()->value,
            'constraints' => $request->getConstraints(),
            'inputSnapshot' => $request->getInputSnapshot(),
            'generatedProgramming' => $request->getGeneratedProgramming(),
            'errorMessage' => $this->publicAiErrorMessage(
                $request->getStatus()->value,
                'programming'
            ),
            'createdAt' => $this->date($request->getCreatedAt()),
            'updatedAt' => $this->date($request->getUpdatedAt()),
            'queuedAt' => $this->date($request->getQueuedAt()),
            'messengerEnqueuedAt' => $this->date($request->getMessengerEnqueuedAt()),
            'startedAt' => $this->date($request->getStartedAt()),
            'completedAt' => $this->date($request->getCompletedAt()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProgrammingSessionDetailRequest(ProgrammingSessionDetailRequest $request): array
    {
        $sessions = $this->programmingDetailSessions($request);
        $currentSessionIndex = $sessions !== []
            ? min($request->getCurrentSessionIndex(), count($sessions) - 1)
            : $request->getCurrentSessionIndex();

        return [
            'id' => (string) $request->getId(),
            'programmingRequestId' => (string) $request->getProgrammingRequest()->getId(),
            'status' => $request->getStatus()->value,
            'inputSnapshot' => $request->getInputSnapshot(),
            'detailedProgramming' => $request->getDetailedProgramming(),
            'currentSessionIndex' => $currentSessionIndex,
            'currentSession' => $sessions[$currentSessionIndex] ?? null,
            'sessionCount' => count($sessions),
            'completedSessionKeys' => $request->getCompletedSessionKeys(),
            'errorMessage' => $this->publicAiErrorMessage(
                $request->getStatus()->value,
                'session_details'
            ),
            'createdAt' => $this->date($request->getCreatedAt()),
            'updatedAt' => $this->date($request->getUpdatedAt()),
            'queuedAt' => $this->date($request->getQueuedAt()),
            'messengerEnqueuedAt' => $this->date($request->getMessengerEnqueuedAt()),
            'startedAt' => $this->date($request->getStartedAt()),
            'completedAt' => $this->date($request->getCompletedAt()),
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function buildMetricCatalog(?UserPerformanceProfile $profile): array
    {
        $catalog = [];
        foreach (PerformanceMetricCategoryEnum::cases() as $category) {
            $catalog[$category->value] = [];
        }

        foreach (PerformanceMetricKeyEnum::cases() as $metricKey) {
            $requiredSkill = $metricKey->requiredSkill();
            $catalog[$metricKey->category()->value][] = [
                'key' => $metricKey->value,
                'label' => $this->label($metricKey->value),
                'category' => $metricKey->category()->value,
                'valueType' => $metricKey->valueType()->value,
                'defaultUnit' => $metricKey->defaultUnit(),
                'requiredSkill' => $requiredSkill?->value,
                'priority' => $metricKey->profilePriority(),
                'available' => $requiredSkill === null || ($profile?->hasPositiveSkill($requiredSkill) ?? false),
            ];
        }

        return $catalog;
    }

    /**
     * @param array<string, mixed> $metricPayload
     */
    private function upsertMetric(UserPerformanceProfile $profile, array $metricPayload): ?string
    {
        $metricKeyValue = $metricPayload['key'] ?? null;
        if (!is_string($metricKeyValue)) {
            return 'Metric key is required.';
        }

        $metricKey = PerformanceMetricKeyEnum::tryFrom($metricKeyValue);
        if ($metricKey === null) {
            return sprintf('Unknown metric key "%s".', $metricKeyValue);
        }

        $metric = $profile->getMetric($metricKey);
        if ($metric === null) {
            $metric = new UserPerformanceMetric($profile, $metricKey);
            $this->entityManager->persist($metric);
        }

        if ($metricKey->valueType() === PerformanceMetricValueTypeEnum::BOOLEAN) {
            if (!array_key_exists('booleanValue', $metricPayload) || !is_bool($metricPayload['booleanValue'])) {
                return sprintf('Metric "%s" expects a booleanValue.', $metricKey->value);
            }
            $metric->setBooleanValue($metricPayload['booleanValue']);
        } else {
            if (!array_key_exists('numericValue', $metricPayload) || !is_numeric($metricPayload['numericValue'])) {
                return sprintf('Metric "%s" expects a numericValue.', $metricKey->value);
            }
            $unit = $metricPayload['unit'] ?? null;
            if ($unit !== null && !is_string($unit)) {
                return sprintf('Metric "%s" unit must be a string.', $metricKey->value);
            }
            $metric->setNumericValue((float) $metricPayload['numericValue'], $unit);
        }

        $notes = $metricPayload['notes'] ?? null;
        if ($notes !== null && !is_string($notes)) {
            return sprintf('Metric "%s" notes must be a string.', $metricKey->value);
        }
        $metric->setNotes($notes);

        return null;
    }

    private function getOrCreatePerformanceProfile(User $user): UserPerformanceProfile
    {
        $profile = $this->getLatestPerformanceProfile($user);
        if ($profile !== null) {
            return $profile;
        }

        $profile = new UserPerformanceProfile($user);
        $this->entityManager->persist($profile);

        return $profile;
    }

    private function getLatestPerformanceProfile(User $user): ?UserPerformanceProfile
    {
        /** @var UserPerformanceProfile|null $profile */
        $profile = $this->entityManager->getRepository(UserPerformanceProfile::class)->findOneBy(
            ['user' => $user],
            ['updatedAt' => 'DESC']
        );

        return $profile;
    }

    private function latestAnalysisRequest(User $user): ?PerformanceAnalysisRequest
    {
        /** @var PerformanceAnalysisRequest|null $request */
        $request = $this->entityManager->getRepository(PerformanceAnalysisRequest::class)->findOneBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $request;
    }

    private function latestCompletedAnalysisRequest(User $user): ?PerformanceAnalysisRequest
    {
        /** @var PerformanceAnalysisRequest|null $request */
        $request = $this->entityManager->getRepository(PerformanceAnalysisRequest::class)->findOneBy(
            ['user' => $user, 'status' => AnalysisRequestStatusEnum::COMPLETED],
            ['completedAt' => 'DESC', 'createdAt' => 'DESC']
        );

        return $request;
    }

    /**
     * @param array<string, mixed> $constraints
     */
    private function sourceAnalysisRequest(User $user, array $constraints): ?PerformanceAnalysisRequest
    {
        $sourceAnalysisRequestId = $constraints['sourceAnalysisRequestId'] ?? null;
        if (!is_string($sourceAnalysisRequestId) || trim($sourceAnalysisRequestId) === '') {
            return $this->latestCompletedAnalysisRequest($user);
        }

        /** @var PerformanceAnalysisRequest|null $request */
        $request = $this->entityManager->getRepository(PerformanceAnalysisRequest::class)->find($sourceAnalysisRequestId);
        if (
            $request === null
            || $request->getUser() !== $user
            || $request->getStatus() !== AnalysisRequestStatusEnum::COMPLETED
            || $request->getResult() === null
        ) {
            return null;
        }

        return $request;
    }

    private function latestProgrammingSessionDetailRequest(
        User $user,
        ProgrammingGenerationRequest $programmingRequest,
    ): ?ProgrammingSessionDetailRequest {
        /** @var ProgrammingSessionDetailRequest|null $request */
        $request = $this->entityManager->getRepository(ProgrammingSessionDetailRequest::class)->findOneBy(
            ['user' => $user, 'programmingRequest' => $programmingRequest],
            ['createdAt' => 'DESC']
        );

        return $request;
    }

    private function publicAiErrorMessage(string $status, string $requestType): ?string
    {
        if ($status !== ProgrammingGenerationRequestStatusEnum::FAILED->value) {
            return null;
        }

        return match ($requestType) {
            'analysis' => 'L’analyse IA a échoué. Tu peux réessayer dans quelques instants.',
            'programming' => 'La génération de programmation a échoué. Tu peux relancer une demande.',
            'session_details' => 'La génération détaillée des séances a échoué. Tu peux relancer la demande.',
            default => 'La demande IA a échoué. Tu peux réessayer dans quelques instants.',
        };
    }

    private function programmingSessionDetailRequestForCurrentUser(string $id): ?ProgrammingSessionDetailRequest
    {
        /** @var ProgrammingSessionDetailRequest|null $request */
        $request = $this->entityManager->getRepository(ProgrammingSessionDetailRequest::class)->find($id);
        if ($request === null || $request->getUser() !== $this->currentUser()) {
            return null;
        }

        return $request;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function programmingDetailSessions(ProgrammingSessionDetailRequest $request): array
    {
        $detailedProgramming = $request->getDetailedProgramming();
        if (!is_array($detailedProgramming)) {
            return [];
        }

        $weeks = $detailedProgramming['weeks'] ?? null;
        if (!is_array($weeks)) {
            return [];
        }

        $sessions = [];
        foreach ($weeks as $weekIndex => $week) {
            if (!is_array($week)) {
                continue;
            }

            $weekSessions = $week['sessions'] ?? null;
            if (!is_array($weekSessions)) {
                continue;
            }

            foreach ($weekSessions as $sessionIndex => $session) {
                if (!is_array($session)) {
                    continue;
                }

                $sessions[] = [
                    'week' => $session['week'] ?? $week['week'] ?? $weekIndex + 1,
                    'session' => $session['session'] ?? $sessionIndex + 1,
                    ...$session,
                ];
            }
        }

        return $sessions;
    }

    /**
     * @param array<string, mixed> $session
     */
    private function programmingSessionKey(array $session, int $fallbackIndex): string
    {
        $sessionKey = $session['session_key'] ?? null;
        if (is_string($sessionKey) && trim($sessionKey) !== '') {
            return $sessionKey;
        }

        $week = $session['week'] ?? null;
        $sessionNumber = $session['session'] ?? null;
        if ((is_int($week) || is_string($week)) && (is_int($sessionNumber) || is_string($sessionNumber))) {
            return sprintf('week-%s-session-%s', $week, $sessionNumber);
        }

        return sprintf('session-%d', $fallbackIndex + 1);
    }

    /**
     * @param list<UserAthleteProfile> $athleteProfiles
     *
     * @return array<string, mixed>
     */
    private function buildAnalysisSnapshot(UserPerformanceProfile $profile, array $athleteProfiles): array
    {
        $primaryAthleteProfile = $this->primaryAnalysisAthleteProfile($athleteProfiles);

        return [
            'performance_metrics' => $this->performanceMetricSnapshot($profile),
            'performance_data_quality' => $profile->analysisDataQuality(),
            'athlete_profile' => $primaryAthleteProfile !== null ? $this->athleteProfileSnapshot($primaryAthleteProfile) : null,
            'athlete_profiles' => array_map(
                fn (UserAthleteProfile $athleteProfile): array => $this->athleteProfileSnapshot($athleteProfile),
                $athleteProfiles
            ),
            ...$this->competitionSnapshotBuilder->buildMany($athleteProfiles),
        ];
    }

    /**
     * @return list<UserAthleteProfile>
     */
    private function personalAnalysisAthleteProfiles(User $user): array
    {
        /** @var list<UserAthleteProfile> $userAthleteProfiles */
        $userAthleteProfiles = $this->entityManager->getRepository(UserAthleteProfile::class)->findBy(['user' => $user]);
        $profiles = array_values(array_filter(
            $userAthleteProfiles,
            static fn (UserAthleteProfile $athleteProfile): bool => $athleteProfile->getLinkType() === UserAthleteProfile::LINK_SELF
                && in_array($athleteProfile->getAthlete()->getSourceName(), ['crossfit_games', 'competition_corner'], true)
        ));

        usort(
            $profiles,
            static fn (UserAthleteProfile $left, UserAthleteProfile $right): int => ($right->isPrimaryProfile() <=> $left->isPrimaryProfile())
                ?: strcmp($left->getAthlete()->getSourceName(), $right->getAthlete()->getSourceName())
        );

        return $profiles;
    }

    /**
     * @param list<UserAthleteProfile> $athleteProfiles
     */
    private function primaryAnalysisAthleteProfile(array $athleteProfiles): ?UserAthleteProfile
    {
        return $athleteProfiles[0] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function athleteProfileSnapshot(UserAthleteProfile $athleteProfile): array
    {
        return [
            'id' => (string) $athleteProfile->getId(),
            'athlete_id' => (string) $athleteProfile->getAthlete()->getId(),
            'display_name' => $athleteProfile->getAthlete()->getDisplayName(),
            'source_name' => $athleteProfile->getAthlete()->getSourceName(),
            'external_id' => $athleteProfile->getAthlete()->getExternalId(),
            'primary_profile' => $athleteProfile->isPrimaryProfile(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProgrammingSnapshot(?UserPerformanceProfile $profile, PerformanceAnalysisRequest $sourceAnalysisRequest): array
    {
        return [
            'performance_profile_id' => $profile?->getId() !== null ? (string) $profile->getId() : null,
            'performance_metrics' => $profile !== null ? $this->performanceMetricSnapshot($profile) : [],
            'source_analysis_request' => [
                'id' => (string) $sourceAnalysisRequest->getId(),
                'parameters' => $sourceAnalysisRequest->getParameters(),
                'input_snapshot' => $sourceAnalysisRequest->getInputSnapshot(),
                'result' => $sourceAnalysisRequest->getResult(),
                'created_at' => $this->date($sourceAnalysisRequest->getCreatedAt()),
                'completed_at' => $this->date($sourceAnalysisRequest->getCompletedAt()),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProgrammingDetailSnapshot(ProgrammingGenerationRequest $programmingRequest): array
    {
        return [
            'source_programming_request' => [
                'id' => (string) $programmingRequest->getId(),
                'type' => $programmingRequest->getType()->value,
                'constraints' => $programmingRequest->getConstraints(),
                'input_snapshot' => $programmingRequest->getInputSnapshot(),
                'global_programming' => $programmingRequest->getGeneratedProgramming(),
                'created_at' => $this->date($programmingRequest->getCreatedAt()),
                'completed_at' => $this->date($programmingRequest->getCompletedAt()),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function performanceMetricSnapshot(UserPerformanceProfile $profile): array
    {
        $metrics = [];
        foreach ($profile->getMetrics() as $metric) {
            $metrics[$metric->getMetricKey()->value] = $metric->getValueType() === PerformanceMetricValueTypeEnum::BOOLEAN
                ? $metric->getBooleanValue()
                : $metric->getNumericValue();
        }

        return $metrics;
    }

    private function hasAnyProvidedMetric(UserPerformanceProfile $profile): bool
    {
        foreach ($profile->getMetrics() as $metric) {
            if ($metric->hasValue()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function missingRequiredMetrics(UserPerformanceProfile $profile): array
    {
        $requiredMetrics = [
            ...PerformanceMetricKeyEnum::requiredStrengthMetrics(),
            ...PerformanceMetricKeyEnum::requiredWeightliftingMetrics(),
            ...PerformanceMetricKeyEnum::gymnasticsSkillMetrics(),
        ];

        $missingMetrics = [];
        foreach ($requiredMetrics as $metricKey) {
            if (!$profile->hasProvidedMetric($metricKey)) {
                $missingMetrics[] = $metricKey->value;
            }
        }

        $providedCardio = 0;
        foreach (PerformanceMetricKeyEnum::cardioMetrics() as $metricKey) {
            if ($profile->hasProvidedMetric($metricKey)) {
                ++$providedCardio;
            }
        }
        if ($providedCardio < 3) {
            $missingMetrics[] = 'cardio_metrics_min_3';
        }

        return $missingMetrics;
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonPayload(Request $request): array
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($payload) ? $payload : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayPayload(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $constraints
     *
     * @return array<string, mixed>
     */
    private function normaliseProgrammingConstraints(array $constraints): array
    {
        $constraints['programmingFamily'] = $this->normaliseProgrammingFamily($constraints);
        $constraints['durationWeeks'] = $this->boundedProgrammingInteger(
            $constraints['durationWeeks'] ?? null,
            'durationWeeks',
            self::PROGRAMMING_DURATION_WEEKS
        );
        $constraints['sessionsPerWeek'] = $this->boundedProgrammingInteger(
            $constraints['sessionsPerWeek'] ?? null,
            'sessionsPerWeek',
            self::PROGRAMMING_SESSIONS_PER_WEEK
        );
        $constraints['sessionDurationMinutes'] = $this->boundedProgrammingInteger(
            $constraints['sessionDurationMinutes'] ?? null,
            'sessionDurationMinutes',
            self::PROGRAMMING_SESSION_DURATION_MINUTES
        );

        return $constraints;
    }

    /**
     * @param array<string, mixed> $constraints
     */
    private function normaliseProgrammingFamily(array $constraints): string
    {
        $familyValue = $constraints['programmingFamily'] ?? null;
        if ($familyValue !== null && $familyValue !== '') {
            if (!is_string($familyValue)) {
                throw new \InvalidArgumentException('programmingFamily must be a string.');
            }

            $family = PersonalProgrammingFamilyEnum::tryFrom($familyValue);
            if ($family === null) {
                throw new \InvalidArgumentException(sprintf('programmingFamily must be one of: %s.', implode(', ', PersonalProgrammingFamilyEnum::values())));
            }

            return $family->value;
        }

        $legacyPurpose = $constraints['programmingPurpose'] ?? null;

        return PersonalProgrammingFamilyEnum::fromLegacyPurpose(is_string($legacyPurpose) ? $legacyPurpose : null)->value;
    }

    /**
     * @param array{min: int, max: int, default: int} $limit
     */
    private function boundedProgrammingInteger(mixed $value, string $field, array $limit): int
    {
        if ($value === null || $value === '') {
            return $limit['default'];
        }

        if (is_int($value)) {
            $integer = $value;
        } elseif (is_float($value) && floor($value) === $value) {
            $integer = (int) $value;
        } elseif (is_string($value) && preg_match('/^\d+$/', trim($value)) === 1) {
            $integer = (int) trim($value);
        } else {
            throw new \InvalidArgumentException(sprintf('%s must be an integer.', $field));
        }

        if ($integer < $limit['min'] || $integer > $limit['max']) {
            throw new \InvalidArgumentException(sprintf('%s must be between %d and %d.', $field, $limit['min'], $limit['max']));
        }

        return $integer;
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function label(string $value): string
    {
        if ($value === PerformanceMetricKeyEnum::MAX_WALLBALLS_UNBROKEN->value) {
            return 'Max wallballs unbroken (6kgs/9kgs)';
        }

        return ucwords(str_replace('_', ' ', $value));
    }

    private function date(?\DateTimeInterface $date): ?string
    {
        return $date?->format(\DateTimeInterface::ATOM);
    }
}
