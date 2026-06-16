<?php

namespace App\Services\Competition;

use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionOfficialQualification;
use Doctrine\ORM\EntityManagerInterface;

final class CompetitionOfficialQualificationPresenter
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return list<array{
     *     circuit: string,
     *     stage: string,
     *     divisionPattern: string,
     *     season: int|null,
     *     label: string
     * }>
     */
    public function confirmedPayload(Competition $competition): array
    {
        /** @var list<CompetitionOfficialQualification> $qualifications */
        $qualifications = $this->entityManager->getRepository(CompetitionOfficialQualification::class)
            ->createQueryBuilder('qualification')
            ->andWhere('qualification.competition = :competition')
            ->andWhere('qualification.status = :status')
            ->setParameter('competition', $competition)
            ->setParameter('status', CompetitionOfficialQualification::STATUS_CONFIRMED)
            ->orderBy('qualification.season', 'DESC')
            ->addOrderBy('qualification.circuit', 'ASC')
            ->addOrderBy('qualification.stage', 'ASC')
            ->addOrderBy('qualification.divisionPattern', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn (CompetitionOfficialQualification $qualification): array => [
            'circuit' => $qualification->getCircuit(),
            'stage' => $qualification->getStage(),
            'divisionPattern' => $qualification->getDivisionPattern(),
            'season' => $qualification->getSeason(),
            'label' => $this->label($qualification),
        ], $qualifications);
    }

    private function label(CompetitionOfficialQualification $qualification): string
    {
        $parts = [
            $this->circuitLabel($qualification->getCircuit()),
            $this->stageLabel($qualification->getStage()),
            $qualification->getSeason(),
        ];

        return trim(implode(' ', array_filter(array_map(
            static fn (mixed $part): ?string => $part === null || $part === '' ? null : (string) $part,
            $parts,
        ))));
    }

    private function circuitLabel(string $circuit): string
    {
        return match ($circuit) {
            CompetitionOfficialQualificationSuggester::CIRCUIT_CROSSFIT_GAMES => 'CrossFit Games',
            default => $this->humanize($circuit),
        };
    }

    private function stageLabel(string $stage): string
    {
        return match ($stage) {
            CompetitionOfficialQualificationSuggester::STAGE_SEMIFINALS => 'Semifinal',
            default => $this->humanize($stage),
        };
    }

    private function humanize(string $value): string
    {
        return mb_convert_case(str_replace('_', ' ', $value), MB_CASE_TITLE, 'UTF-8');
    }
}
