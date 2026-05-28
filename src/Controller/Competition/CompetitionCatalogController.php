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
                ->andWhere('LOWER(competition.name) LIKE :search OR LOWER(competition.locationLabel) LIKE :search OR LOWER(competition.competitionType) LIKE :search OR LOWER(competition.sourceName) LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        $country = $this->normalizedString($request->query->get('country'));
        if ($country !== null) {
            $queryBuilder
                ->andWhere('LOWER(competition.locationLabel) LIKE :country')
                ->setParameter('country', '%'.$country.'%');
        }

        $location = $this->normalizedString($request->query->get('location'));
        if ($location !== null) {
            $queryBuilder
                ->andWhere('LOWER(competition.locationLabel) LIKE :location')
                ->setParameter('location', '%'.$location.'%');
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
                ->andWhere('competition.startsAt > :now OR competition.status = :upcoming')
                ->setParameter('now', $now)
                ->setParameter('upcoming', 'upcoming'),
            'ongoing' => $queryBuilder
                ->andWhere('(competition.startsAt <= :now AND competition.endsAt >= :now) OR competition.status = :ongoing')
                ->setParameter('now', $now)
                ->setParameter('ongoing', 'ongoing'),
            'past' => $queryBuilder
                ->andWhere('competition.endsAt < :now OR competition.status = :past OR competition.season < :currentYear')
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
                    WHEN ((competition.startsAt <= :orderNow AND competition.endsAt >= :orderNow) OR competition.status = :orderOngoing) THEN 0
                    WHEN (competition.startsAt > :orderNow OR competition.status = :orderUpcoming) THEN 1
                    WHEN (competition.endsAt < :orderNow OR competition.status = :orderPast OR competition.season < :orderCurrentYear) THEN 2
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
            ->select('DISTINCT competition.locationLabel AS locationLabel')
            ->where('competition.locationLabel IS NOT NULL')
            ->getQuery()
            ->getArrayResult();

        $countries = [];
        foreach ($rows as $row) {
            $country = $this->countryFromLocation((string) ($row['locationLabel'] ?? ''));
            if ($country !== null) {
                $countries[$country] = true;
            }
        }

        $countries = array_keys($countries);
        sort($countries, SORT_STRING);

        return $countries;
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
