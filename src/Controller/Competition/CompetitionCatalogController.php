<?php

declare(strict_types=1);

namespace App\Controller\Competition;

use App\Entity\Competition\Competition;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CompetitionCatalogController extends AbstractController
{
    private const PAGE_SIZE = 24;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/competition-catalog', name: 'competition_catalog', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $page = $this->positiveInt($request->query->get('page'), 1);
        $queryBuilder = $this->filteredQueryBuilder($request);
        $totalItems = (int) (clone $queryBuilder)
            ->select('COUNT(competition.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        /** @var list<Competition> $competitions */
        $competitions = $this->orderedQueryBuilder($queryBuilder)
            ->setFirstResult(($page - 1) * self::PAGE_SIZE)
            ->setMaxResults(self::PAGE_SIZE)
            ->getQuery()
            ->getResult();

        return $this->json([
            'totalItems' => $totalItems,
            'member' => array_map($this->serializeCompetition(...), $competitions),
            'countries' => $this->countries(),
            'regions' => $this->regions($request),
            'view' => [
                'next' => $page * self::PAGE_SIZE < $totalItems ? sprintf('/competitions?page=%d', $page + 1) : null,
            ],
        ]);
    }

    private function filteredQueryBuilder(Request $request): QueryBuilder
    {
        $queryBuilder = $this->entityManager->getRepository(Competition::class)->createQueryBuilder('competition');
        $search = $this->normalizedString($request->query->get('q'));
        if ($search !== null) {
            $queryBuilder
                ->andWhere('LOWER(competition.name) LIKE :search OR LOWER(competition.locationLabel) LIKE :search OR LOWER(competition.cityName) LIKE :search OR LOWER(competition.departmentName) LIKE :search OR LOWER(competition.regionName) LIKE :search OR LOWER(competition.countryName) LIKE :search OR LOWER(competition.competitionType) LIKE :search OR LOWER(competition.sourceName) LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        $country = $this->normalizedString($request->query->get('country'));
        if ($country !== null) {
            $queryBuilder
                ->andWhere('LOWER(competition.countryName) = :country OR LOWER(competition.countryCode) = :country OR LOWER(competition.locationLabel) LIKE :countryLike')
                ->setParameter('country', $country)
                ->setParameter('countryLike', '%'.$country.'%');
        }

        $location = $this->normalizedString($request->query->get('location'));
        if ($location !== null) {
            $queryBuilder
                ->andWhere('LOWER(competition.locationLabel) LIKE :location OR LOWER(competition.cityName) LIKE :location OR LOWER(competition.departmentName) LIKE :location OR LOWER(competition.regionName) LIKE :location OR LOWER(competition.countryName) LIKE :location')
                ->setParameter('location', '%'.$location.'%');
        }

        $region = $this->normalizedString($request->query->get('region'));
        if ($region !== null) {
            $queryBuilder
                ->andWhere('LOWER(competition.regionName) = :region')
                ->setParameter('region', $region);
        }

        $rawSources = $request->query->all()['source'] ?? [];
        $rawSources = is_array($rawSources) ? $rawSources : [$rawSources];
        $sources = array_values(array_filter($rawSources, static fn (mixed $source): bool => is_string($source) && trim($source) !== '' && $source !== 'none'));
        if ($request->query->get('source') === 'none' || in_array('none', $rawSources, true)) {
            $queryBuilder->andWhere('1 = 0');
        } elseif (count($sources) > 0) {
            $queryBuilder
                ->andWhere('competition.sourceName IN (:sources)')
                ->setParameter('sources', $sources);
        }

        $participation = $this->normalizedString($request->query->get('participation'));
        if ($participation !== null) {
            $queryBuilder
                ->andWhere('competition.participationType = :participation OR competition.participationType = :both')
                ->setParameter('participation', $participation)
                ->setParameter('both', 'both');
        }

        $this->applyStatusFilter($queryBuilder, $this->normalizedString($request->query->get('status')));

        return $queryBuilder;
    }

    private function applyStatusFilter(QueryBuilder $queryBuilder, ?string $status): void
    {
        $now = new \DateTimeImmutable();
        $currentYear = (int) $now->format('Y');

        match ($status) {
            'upcoming' => $queryBuilder
                ->andWhere('competition.startsAt > :now OR (competition.startsAt IS NULL AND competition.endsAt IS NULL AND competition.status = :upcoming)')
                ->setParameter('now', $now)
                ->setParameter('upcoming', 'upcoming'),
            'ongoing' => $queryBuilder
                ->andWhere('(competition.startsAt <= :now AND competition.endsAt >= :now) OR (competition.startsAt IS NULL AND competition.endsAt IS NULL AND competition.status = :ongoing)')
                ->setParameter('now', $now)
                ->setParameter('ongoing', 'ongoing'),
            'past' => $queryBuilder
                ->andWhere('competition.endsAt < :now OR (competition.startsAt IS NULL AND competition.endsAt IS NULL AND competition.status = :past) OR (competition.startsAt IS NULL AND competition.endsAt IS NULL AND competition.season < :currentYear)')
                ->setParameter('now', $now)
                ->setParameter('past', 'past')
                ->setParameter('currentYear', $currentYear),
            default => null,
        };
    }

    private function orderedQueryBuilder(QueryBuilder $queryBuilder): QueryBuilder
    {
        $now = new \DateTimeImmutable();
        $currentYear = (int) $now->format('Y');

        return $queryBuilder
            ->addSelect(
                'CASE
                    WHEN ((competition.startsAt <= :orderNow AND competition.endsAt >= :orderNow) OR (competition.startsAt IS NULL AND competition.endsAt IS NULL AND competition.status = :orderOngoing)) THEN 0
                    WHEN (competition.startsAt > :orderNow OR (competition.startsAt IS NULL AND competition.endsAt IS NULL AND competition.status = :orderUpcoming)) THEN 1
                    WHEN (competition.endsAt < :orderNow OR (competition.startsAt IS NULL AND competition.endsAt IS NULL AND competition.status = :orderPast) OR (competition.startsAt IS NULL AND competition.endsAt IS NULL AND competition.season < :orderCurrentYear)) THEN 2
                    ELSE 3
                END AS HIDDEN statusWeight'
            )
            ->addSelect('COALESCE(competition.startsAt, competition.endsAt) AS HIDDEN competitionDate')
            ->setParameter('orderNow', $now)
            ->setParameter('orderOngoing', 'ongoing')
            ->setParameter('orderUpcoming', 'upcoming')
            ->setParameter('orderPast', 'past')
            ->setParameter('orderCurrentYear', $currentYear)
            ->orderBy('statusWeight', 'ASC')
            ->addOrderBy('competitionDate', 'ASC')
            ->addOrderBy('competition.name', 'ASC');
    }

    /**
     * @return list<string>
     */
    private function countries(): array
    {
        $rows = $this->entityManager->getRepository(Competition::class)->createQueryBuilder('competition')
            ->select('DISTINCT competition.countryName AS countryName, competition.locationLabel AS locationLabel')
            ->where('competition.countryName IS NOT NULL OR competition.locationLabel IS NOT NULL')
            ->getQuery()
            ->getArrayResult();

        $countries = [];
        foreach ($rows as $row) {
            $country = $this->titleOrNull($row['countryName'] ?? null)
                ?? $this->countryFromLocation((string) ($row['locationLabel'] ?? ''));
            if ($country !== null) {
                $countries[$country] = true;
            }
        }

        $countries = array_keys($countries);
        sort($countries, SORT_STRING);

        return $countries;
    }

    /**
     * @return list<string>
     */
    private function regions(Request $request): array
    {
        $queryBuilder = $this->entityManager->getRepository(Competition::class)->createQueryBuilder('competition')
            ->select('DISTINCT competition.regionName AS regionName')
            ->where('competition.regionName IS NOT NULL');

        $country = $this->normalizedString($request->query->get('country'));
        if ($country !== null) {
            $queryBuilder
                ->andWhere('LOWER(competition.countryName) = :regionCountry OR LOWER(competition.countryCode) = :regionCountry')
                ->setParameter('regionCountry', $country);
        }

        $search = $this->normalizedString($request->query->get('q'));
        if ($search !== null) {
            $queryBuilder
                ->andWhere('LOWER(competition.name) LIKE :regionSearch OR LOWER(competition.locationLabel) LIKE :regionSearch OR LOWER(competition.cityName) LIKE :regionSearch OR LOWER(competition.departmentName) LIKE :regionSearch OR LOWER(competition.regionName) LIKE :regionSearch OR LOWER(competition.countryName) LIKE :regionSearch OR LOWER(competition.competitionType) LIKE :regionSearch OR LOWER(competition.sourceName) LIKE :regionSearch')
                ->setParameter('regionSearch', '%'.$search.'%');
        }

        $rawSources = $request->query->all()['source'] ?? [];
        $rawSources = is_array($rawSources) ? $rawSources : [$rawSources];
        $sources = array_values(array_filter($rawSources, static fn (mixed $source): bool => is_string($source) && trim($source) !== '' && $source !== 'none'));
        if ($request->query->get('source') === 'none' || in_array('none', $rawSources, true)) {
            return [];
        }
        if (count($sources) > 0) {
            $queryBuilder
                ->andWhere('competition.sourceName IN (:regionSources)')
                ->setParameter('regionSources', $sources);
        }

        $participation = $this->normalizedString($request->query->get('participation'));
        if ($participation !== null) {
            $queryBuilder
                ->andWhere('competition.participationType = :regionParticipation OR competition.participationType = :regionBoth')
                ->setParameter('regionParticipation', $participation)
                ->setParameter('regionBoth', 'both');
        }

        $this->applyStatusFilter($queryBuilder, $this->normalizedString($request->query->get('status')));

        $regions = [];
        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            $region = $this->titleOrNull($row['regionName'] ?? null);
            if ($region !== null) {
                $regions[$region] = true;
            }
        }

        $regions = array_keys($regions);
        sort($regions, SORT_STRING);

        return $regions;
    }

    private function countryFromLocation(string $location): ?string
    {
        $value = trim($location);
        if ($value === '' || mb_strtolower($value) === 'en ligne') {
            return null;
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $value))));
        $country = $parts[count($parts) - 1] ?? $value;

        return mb_convert_case(mb_strtolower($country), MB_CASE_TITLE, 'UTF-8');
    }

    private function titleOrNull(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    private function serializeCompetition(Competition $competition): array
    {
        return [
            '@id' => '/api/competitions/'.$competition->getId(),
            'id' => (string) $competition->getId(),
            'name' => $competition->getName(),
            'season' => $competition->getSeason(),
            'sourceName' => $competition->getSourceName(),
            'externalId' => $competition->getExternalId(),
            'sourceUrl' => $competition->getSourceUrl(),
            'logoUrl' => $competition->getLogoUrl(),
            'status' => $competition->getStatus(),
            'startsAt' => $competition->getStartsAt()?->format(DATE_ATOM),
            'endsAt' => $competition->getEndsAt()?->format(DATE_ATOM),
            'registrationUrl' => $competition->getRegistrationUrl(),
            'locationLabel' => $competition->getLocationLabel(),
            'countryName' => $competition->getCountryName(),
            'countryCode' => $competition->getCountryCode(),
            'regionName' => $competition->getRegionName(),
            'departmentName' => $competition->getDepartmentName(),
            'cityName' => $competition->getCityName(),
            'latitude' => $competition->getLatitude(),
            'longitude' => $competition->getLongitude(),
            'isOnline' => $competition->isOnline(),
            'competitionType' => $competition->getCompetitionType(),
            'participationType' => $competition->getParticipationType(),
            'coverImageUrl' => $competition->getCoverImageUrl(),
            'priceLabel' => $competition->getPriceLabel(),
        ];
    }

    private function positiveInt(mixed $value, int $default): int
    {
        if (!is_scalar($value) || (string) $value === '') {
            return $default;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : $default;
    }

    private function normalizedString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = mb_strtolower(trim((string) $value));

        return $value === '' ? null : $value;
    }
}
