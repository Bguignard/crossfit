<?php

namespace App\Controller\Profile;

use App\Entity\Competition\Athlete;
use App\Entity\Product\Enum\PerformanceMetricCategoryEnum;
use App\Entity\Product\Enum\PerformanceMetricKeyEnum;
use App\Entity\Product\Enum\PerformanceMetricValueTypeEnum;
use App\Entity\Product\UserAthleteProfile;
use App\Entity\Product\UserPerformanceMetric;
use App\Entity\Product\UserPerformanceProfile;
use App\Entity\Security\User;
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
    private const VALID_LINK_TYPES = [
        UserAthleteProfile::LINK_SELF,
        UserAthleteProfile::LINK_COACHED,
        UserAthleteProfile::LINK_FOLLOWED,
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
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
        return ucwords(str_replace('_', ' ', $value));
    }

    private function date(?\DateTimeInterface $date): ?string
    {
        return $date?->format(\DateTimeInterface::ATOM);
    }
}
