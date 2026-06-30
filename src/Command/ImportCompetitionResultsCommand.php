<?php

namespace App\Command;

use App\Entity\Competition\Athlete;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionDivision;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\CompetitionParticipation;
use App\Entity\Competition\Enum\ScoreTypeEnum;
use App\Entity\Competition\Score;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Entity\Workout\WorkoutType;
use App\Services\Competition\AthleteNameNormalizer;
use App\Services\Competition\CompetitionGeoNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:competition-results',
    description: 'Import normalized competition results JSON produced by the Python service.',
)]
class ImportCompetitionResultsCommand extends Command
{
    private const CONTRACT_VERSION = 'competition-results.v1';

    /**
     * @var array<string, array{created: int, updated: int, skipped: int, failed: int}>
     */
    private array $summary = [];

    /**
     * @var list<string>
     */
    private array $errors = [];

    /**
     * @var array<string, CompetitionDivision>
     */
    private array $competitionDivisions = [];

    /**
     * @var array<string, CompetitionParticipation>
     */
    private array $competitionParticipations = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AthleteNameNormalizer $athleteNameNormalizer,
        private readonly CompetitionGeoNormalizer $competitionGeoNormalizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Path to a competition-results.v1 JSON file.');
        $this->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Flush batch size for large result imports.', 500);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) $input->getArgument('file');

        if (!is_file($file) || !is_readable($file)) {
            $io->error(sprintf('Import file "%s" does not exist or is not readable.', $file));

            return Command::FAILURE;
        }

        try {
            $payload = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $io->error(sprintf('Invalid JSON: %s', $exception->getMessage()));

            return Command::FAILURE;
        }

        if (!is_array($payload)) {
            $io->error('Import payload must be a JSON object.');

            return Command::FAILURE;
        }

        $batchSize = max(1, (int) $input->getOption('batch-size'));
        try {
            $report = $this->importPayload($payload, $batchSize);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->section('Import summary');
        $io->table(
            ['section', 'created', 'updated', 'skipped', 'failed'],
            array_map(
                static fn (string $section, array $counts): array => [
                    $section,
                    $counts['created'],
                    $counts['updated'],
                    $counts['skipped'],
                    $counts['failed'],
                ],
                array_keys($report['summary']),
                $report['summary'],
            ),
        );

        if ($report['errors'] !== []) {
            $io->warning('Some rows were not imported.');
            foreach ($report['errors'] as $error) {
                $io->writeln(sprintf('- %s', $error));
            }
        }

        return $report['hasFailures'] ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{
     *     summary: array<string, array{created: int, updated: int, skipped: int, failed: int}>,
     *     errors: list<string>,
     *     hasFailures: bool
     * }
     */
    public function importPayload(array $payload, int $batchSize = 500): array
    {
        if (($payload['contractVersion'] ?? null) !== self::CONTRACT_VERSION) {
            throw new \InvalidArgumentException(sprintf('Unsupported contract version "%s".', (string) ($payload['contractVersion'] ?? '')));
        }

        $sourceName = $this->stringOrNull($payload['source']['name'] ?? null);
        $batchSize = max(1, $batchSize);
        $this->summary = [];
        $this->errors = [];
        $this->competitionDivisions = [];
        $this->competitionParticipations = [];

        $this->importRows('workouts', $payload['workouts'] ?? [], fn (array $row): string => $this->importWorkout($row, $sourceName));
        $this->entityManager->flush();
        $this->importRows('athletes', $payload['athletes'] ?? [], fn (array $row): string => $this->importAthlete($row, $sourceName));
        $this->entityManager->flush();
        $this->importRows('competitions', $payload['competitions'] ?? [], fn (array $row): string => $this->importCompetition($row, $sourceName));
        $this->entityManager->flush();
        $this->importRows('participations', $payload['participations'] ?? [], fn (array $row): string => $this->importParticipation($row, $sourceName));
        $this->entityManager->flush();
        $this->importRows('events', $payload['events'] ?? [], fn (array $row): string => $this->importEvent($row, $sourceName));
        $this->entityManager->flush();
        $this->importRows(
            'results',
            $payload['results'] ?? [],
            fn (array $row): string => $this->importResult($row, $sourceName),
            $batchSize,
        );
        $this->entityManager->flush();

        return [
            'summary' => $this->summary,
            'errors' => $this->errors,
            'hasFailures' => $this->hasFailures(),
        ];
    }

    /**
     * @param callable(array<string, mixed>): string $importer
     */
    private function importRows(string $section, mixed $rows, callable $importer, ?int $flushEvery = null): void
    {
        $this->summary[$section] = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        if (!is_array($rows)) {
            ++$this->summary[$section]['failed'];
            $this->errors[] = sprintf('%s must be an array.', $section);

            return;
        }

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                ++$this->summary[$section]['failed'];
                $this->errors[] = sprintf('%s[%d] must be an object.', $section, $index);

                continue;
            }

            try {
                $status = $importer($row);
                ++$this->summary[$section][$status];
            } catch (\InvalidArgumentException $exception) {
                ++$this->summary[$section]['failed'];
                $this->errors[] = sprintf('%s[%d]: %s', $section, $index, $exception->getMessage());
            }

            if ($flushEvery !== null && ($index + 1) % $flushEvery === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $this->competitionDivisions = [];
                $this->competitionParticipations = [];
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function importWorkout(array $row, ?string $fallbackSourceName): string
    {
        [$sourceName, $externalId, $sourceUrl] = $this->sourceIdentity($row, $fallbackSourceName);
        $name = $this->requiredString($row, 'name');
        $flow = $this->stringOrNull($row['flow'] ?? null);
        if ($flow === null) {
            throw new \InvalidArgumentException('flow is required.');
        }
        if ($this->isPlaceholderWorkoutFlow($flow)) {
            return 'skipped';
        }

        /** @var Workout|null $workout */
        $workout = $this->entityManager->getRepository(Workout::class)->findOneBy([
            'sourceName' => $sourceName,
            'externalId' => $externalId,
        ]);
        $status = $workout === null ? 'created' : 'updated';

        $origin = $this->findOrCreateWorkoutOrigin(
            $this->stringOrNull($row['originName'] ?? null),
            $this->intOrNull($row['originYear'] ?? null),
        );
        $workoutType = $this->findWorkoutType($this->stringOrNull($row['workoutType'] ?? null));

        if ($workout === null) {
            $workout = new Workout(
                $name,
                $flow,
                null,
                $this->intOrNull($row['timeCap'] ?? null),
                $workoutType,
                $origin,
            );
            $workout->setSourceName($sourceName)->setExternalId($externalId);
            $this->entityManager->persist($workout);
        }

        $workout
            ->setName($name)
            ->setFlow($flow)
            ->setNormalizedName($this->stringOrNull($row['normalizedName'] ?? null))
            ->setNormalizedFlow($this->stringOrNull($row['normalizedFlow'] ?? null))
            ->setCanonicalFingerprint($this->stringOrNull($row['canonicalFingerprint'] ?? null))
            ->setTimeCap($this->intOrNull($row['timeCap'] ?? null))
            ->setWorkoutType($workoutType)
            ->setWorkoutOrigin($origin)
            ->setSourceUrl($sourceUrl);

        $this->replaceMovements($workout, $this->strings($row['movementNames'] ?? []));
        $this->replaceImplements($workout, $this->strings($row['implementNames'] ?? []));

        return $status;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function importAthlete(array $row, ?string $fallbackSourceName): string
    {
        [$sourceName, $externalId, $sourceUrl] = $this->sourceIdentity($row, $fallbackSourceName);
        $displayName = $this->requiredString($row, 'displayName');

        /** @var Athlete|null $athlete */
        $athlete = $this->entityManager->getRepository(Athlete::class)->findOneBy([
            'sourceName' => $sourceName,
            'externalId' => $externalId,
        ]);
        $status = $athlete === null ? 'created' : 'updated';

        if ($athlete === null) {
            $athlete = new Athlete($displayName, $sourceName, $externalId);
            $this->entityManager->persist($athlete);
        }

        $athlete
            ->setDisplayName($displayName)
            ->setNormalizedName($this->athleteNameNormalizer->normalize($displayName))
            ->setFirstName($this->stringOrNull($row['firstName'] ?? null))
            ->setLastName($this->stringOrNull($row['lastName'] ?? null))
            ->setGender($this->stringOrNull($row['gender'] ?? null))
            ->setCountry($this->stringOrNull($row['country'] ?? null))
            ->setSourceUrl($sourceUrl)
            ->setAvatarUrl($this->stringOrNull($row['avatarUrl'] ?? null))
            ->setEliteGamesRank($this->intOrNull($row['eliteGamesRank'] ?? null))
            ->setEliteGamesSeason($this->intOrNull($row['eliteGamesSeason'] ?? null));

        return $status;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function importCompetition(array $row, ?string $fallbackSourceName): string
    {
        [$sourceName, $externalId, $sourceUrl] = $this->sourceIdentity($row, $fallbackSourceName);
        $name = $this->requiredString($row, 'name');

        /** @var Competition|null $competition */
        $competition = $this->entityManager->getRepository(Competition::class)->findOneBy([
            'sourceName' => $sourceName,
            'externalId' => $externalId,
        ]);
        $status = $competition === null ? 'created' : 'updated';

        if ($competition === null) {
            $competition = new Competition($name, $sourceName, $externalId);
            $this->entityManager->persist($competition);
        }

        $geo = $this->competitionGeoNormalizer->fromImportRow($row);

        $competition
            ->setName($name)
            ->setSeason($this->intOrNull($row['season'] ?? null))
            ->setSourceUrl($sourceUrl)
            ->setLogoUrl($this->stringOrNull($row['logoUrl'] ?? null))
            ->setStatus($this->stringOrNull($row['status'] ?? null))
            ->setStartsAt($this->dateTimeOrNull($row['startsAt'] ?? null))
            ->setEndsAt($this->dateTimeOrNull($row['endsAt'] ?? null))
            ->setRegistrationUrl($this->stringOrNull($row['registrationUrl'] ?? null))
            ->setLocationLabel($this->stringOrNull($row['locationLabel'] ?? null))
            ->setCountryName($geo['countryName'])
            ->setCountryCode($geo['countryCode'])
            ->setRegionName($geo['regionName'])
            ->setDepartmentName($geo['departmentName'])
            ->setCityName($geo['cityName'])
            ->setLatitude($geo['latitude'])
            ->setLongitude($geo['longitude'])
            ->setIsOnline($this->boolOrNull($row['isOnline'] ?? null))
            ->setCompetitionType($this->stringOrNull($row['competitionType'] ?? null))
            ->setParticipationType($this->stringOrNull($row['participationType'] ?? null))
            ->setCoverImageUrl($this->stringOrNull($row['coverImageUrl'] ?? null))
            ->setPriceLabel($this->stringOrNull($row['priceLabel'] ?? null))
            ->setMetadata($this->arrayOrNull($row['metadata'] ?? null))
            ->setLastDiscoveredAt($this->dateTimeOrNull($row['lastDiscoveredAt'] ?? null));

        return $status;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function importEvent(array $row, ?string $fallbackSourceName): string
    {
        [$sourceName, $externalId, $sourceUrl] = $this->sourceIdentity($row, $fallbackSourceName);
        $name = $this->requiredString($row, 'name');
        $competition = $this->findImported(Competition::class, $sourceName, $this->requiredString($row, 'competitionSourceId'));
        $workoutSourceId = $this->stringOrNull($row['workoutSourceId'] ?? null);
        $workout = $workoutSourceId === null ? null : $this->findImported(Workout::class, $sourceName, $workoutSourceId);

        /** @var CompetitionEvent|null $event */
        $event = $this->entityManager->getRepository(CompetitionEvent::class)->findOneBy([
            'sourceName' => $sourceName,
            'externalId' => $externalId,
        ]);
        $status = $event === null ? 'created' : 'updated';

        if ($event === null) {
            $event = new CompetitionEvent($competition, $name, $sourceName, $externalId);
            $this->entityManager->persist($event);
        }

        $event
            ->setName($name)
            ->setEventOrder($this->intOrNull($row['eventOrder'] ?? null))
            ->setWorkout($workout)
            ->setProvenances($this->arrayListOrNull($row['provenances'] ?? null))
            ->setSourceUrl($sourceUrl);

        return $status;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function importParticipation(array $row, ?string $fallbackSourceName): string
    {
        [$sourceName, $externalId, $sourceUrl] = $this->sourceIdentity($row, $fallbackSourceName);
        $athlete = $this->findImported(Athlete::class, $sourceName, $this->requiredString($row, 'athleteSourceId'));
        $competition = $this->findImported(Competition::class, $sourceName, $this->requiredString($row, 'competitionSourceId'));

        return $this->upsertCompetitionParticipation(
            athlete: $athlete,
            competition: $competition,
            sourceName: $sourceName,
            externalId: $externalId,
            sourceUrl: $sourceUrl,
            rank: $this->stringOrNull($row['rank'] ?? null),
            division: $this->stringOrNull($row['division'] ?? null),
            divisionSourceId: $this->stringOrNull($row['divisionSourceId'] ?? null),
            format: $this->stringOrNull($row['format'] ?? null),
            formatSlug: $this->stringOrNull($row['formatSlug'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function importResult(array $row, ?string $fallbackSourceName): string
    {
        [$sourceName, $externalId, $sourceUrl] = $this->sourceIdentity($row, $fallbackSourceName);
        $athlete = $this->findImported(Athlete::class, $sourceName, $this->requiredString($row, 'athleteSourceId'));
        $event = $this->findImported(CompetitionEvent::class, $sourceName, $this->requiredString($row, 'eventSourceId'));
        $divisionName = $this->stringOrNull($row['division'] ?? null);
        $competitionDivision = $divisionName === null ? null : $this->findOrCreateCompetitionDivision(
            $event->getCompetition(),
            $divisionName,
            $sourceName,
            $this->stringOrNull($row['divisionSourceId'] ?? null),
            $sourceUrl,
        );
        /** @var WorkoutResult|null $result */
        $result = $this->entityManager->getRepository(WorkoutResult::class)->findOneBy([
            'sourceName' => $sourceName,
            'externalId' => $externalId,
        ]);
        $status = $result === null ? 'created' : 'updated';
        $score = $this->buildScore($row['score'] ?? null, $result?->getScore());

        if ($result === null) {
            $result = new WorkoutResult($athlete, $event, $score, $sourceName, $externalId);
            $this->entityManager->persist($result);
        }

        $result
            ->setScore($score)
            ->setRank($this->intOrNull($row['rank'] ?? null))
            ->setFieldSize($this->intOrNull($row['fieldSize'] ?? null))
            ->setDivision($divisionName)
            ->setDivisionSourceId($this->stringOrNull($row['divisionSourceId'] ?? null))
            ->setCompetitionRank($this->stringOrNull($row['competitionRank'] ?? null))
            ->setCompetitionFormat($this->stringOrNull($row['competitionFormat'] ?? null))
            ->setCompetitionFormatSlug($this->stringOrNull($row['competitionFormatSlug'] ?? null))
            ->setCompetitionDivision($competitionDivision)
            ->setPoints($this->intOrNull($row['points'] ?? null))
            ->setSourceUrl($sourceUrl);

        $participationExternalId = $this->participationExternalId(
            $this->requiredString($row, 'athleteSourceId'),
            $event->getCompetition()->getExternalId(),
        );
        $this->upsertCompetitionParticipation(
            athlete: $athlete,
            competition: $event->getCompetition(),
            sourceName: $sourceName,
            externalId: $participationExternalId,
            sourceUrl: $sourceUrl,
            rank: $this->stringOrNull($row['competitionRank'] ?? null),
            division: $divisionName,
            divisionSourceId: $this->stringOrNull($row['divisionSourceId'] ?? null),
            format: $this->stringOrNull($row['competitionFormat'] ?? null),
            formatSlug: $this->stringOrNull($row['competitionFormatSlug'] ?? null),
        );

        return $status;
    }

    private function upsertCompetitionParticipation(
        Athlete $athlete,
        Competition $competition,
        string $sourceName,
        string $externalId,
        ?string $sourceUrl,
        ?string $rank,
        ?string $division,
        ?string $divisionSourceId,
        ?string $format,
        ?string $formatSlug,
    ): string {
        $cacheKey = $sourceName.'|'.$externalId;
        if (isset($this->competitionParticipations[$cacheKey])) {
            $participation = $this->competitionParticipations[$cacheKey];
            $status = 'updated';
        } else {
            /** @var CompetitionParticipation|null $participation */
            $participation = $this->entityManager->getRepository(CompetitionParticipation::class)->findOneBy([
                'sourceName' => $sourceName,
                'externalId' => $externalId,
            ]);
            $status = $participation === null ? 'created' : 'updated';

            if ($participation === null) {
                $participation = new CompetitionParticipation($athlete, $competition, $sourceName, $externalId);
                $this->entityManager->persist($participation);
            }
            $this->competitionParticipations[$cacheKey] = $participation;
        }

        $participation
            ->setSourceUrl($sourceUrl)
            ->setRank($rank)
            ->setDivision($division)
            ->setDivisionSourceId($divisionSourceId)
            ->setFormat($format)
            ->setFormatSlug($formatSlug);

        return $status;
    }

    private function participationExternalId(string $athleteSourceId, string $competitionSourceId): string
    {
        return sprintf('%s:%s', $competitionSourceId, $athleteSourceId);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{0: string, 1: string, 2: string|null}
     */
    private function sourceIdentity(array $row, ?string $fallbackSourceName): array
    {
        if (!isset($row['source']) || !is_array($row['source'])) {
            throw new \InvalidArgumentException('source object is required.');
        }

        $sourceName = $this->stringOrNull($row['source']['name'] ?? null) ?? $fallbackSourceName;
        $externalId = $this->stringOrNull($row['source']['externalId'] ?? null);

        if ($sourceName === null) {
            throw new \InvalidArgumentException('source.name is required.');
        }

        if ($externalId === null) {
            throw new \InvalidArgumentException('source.externalId is required.');
        }

        return [$sourceName, $externalId, $this->stringOrNull($row['source']['url'] ?? null)];
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function arrayListOrNull(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $items = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function requiredString(array $row, string $field): string
    {
        $value = $this->stringOrNull($row[$field] ?? null);

        if ($value === null) {
            throw new \InvalidArgumentException(sprintf('%s is required.', $field));
        }

        return $value;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     */
    private function findImported(string $className, string $sourceName, string $externalId): object
    {
        $entity = $this->entityManager->getRepository($className)->findOneBy([
            'sourceName' => $sourceName,
            'externalId' => $externalId,
        ]);

        if ($entity === null) {
            throw new \InvalidArgumentException(sprintf('Unable to resolve %s "%s".', $className, $externalId));
        }

        return $entity;
    }

    private function buildScore(mixed $data, ?Score $score = null): Score
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('score object is required.');
        }

        $typeValue = $this->requiredString($data, 'type');
        $type = ScoreTypeEnum::tryFrom($typeValue);

        if ($type === null) {
            throw new \InvalidArgumentException(sprintf('Unsupported score type "%s".', $typeValue));
        }

        $score ??= new Score($type, $this->requiredString($data, 'rawValue'));

        return $score
            ->setType($type)
            ->setRawValue($this->requiredString($data, 'rawValue'))
            ->setDisplayValue($this->stringOrNull($data['displayValue'] ?? null))
            ->setNumericValue($this->floatOrNull($data['numericValue'] ?? null))
            ->setTimeInSeconds($this->intOrNull($data['timeInSeconds'] ?? null))
            ->setUnit($this->stringOrNull($data['unit'] ?? null));
    }

    private function findWorkoutType(?string $name): ?WorkoutType
    {
        if ($name === null) {
            return null;
        }

        $enum = WorkoutTypeEnum::tryFrom($name);
        if ($enum === null) {
            $enum = $this->matchEnumByNormalizedValue(WorkoutTypeEnum::cases(), $name);
        }

        if ($enum === null) {
            return null;
        }

        /** @var WorkoutType|null $type */
        $type = $this->entityManager->getRepository(WorkoutType::class)->findOneBy(['name' => $enum->value]);

        if ($type === null) {
            $type = new WorkoutType($enum);
            $this->entityManager->persist($type);
        }

        return $type;
    }

    private function findOrCreateWorkoutOrigin(?string $originName, ?int $year): WorkoutOrigin
    {
        $enum = $originName === null ? WorkoutOriginNameEnum::OTHER : WorkoutOriginNameEnum::tryFrom($originName);
        if ($enum === null) {
            $enum = $this->matchEnumByNormalizedValue(WorkoutOriginNameEnum::cases(), $originName);
        }
        $enum ??= WorkoutOriginNameEnum::OTHER;

        /** @var WorkoutOriginName|null $name */
        $name = $this->entityManager->getRepository(WorkoutOriginName::class)->findOneBy(['name' => $enum->value]);
        if ($name === null) {
            $name = new WorkoutOriginName($enum);
            $this->entityManager->persist($name);
        }

        /** @var WorkoutOrigin|null $origin */
        $origin = $this->entityManager->getRepository(WorkoutOrigin::class)->findOneBy([
            'name' => $name,
            'year' => $year,
        ]);

        if ($origin === null) {
            $origin = new WorkoutOrigin($name, $year);
            $this->entityManager->persist($origin);
        }

        return $origin;
    }

    private function findOrCreateCompetitionDivision(
        Competition $competition,
        string $name,
        string $sourceName,
        ?string $externalId,
        ?string $sourceUrl,
    ): CompetitionDivision {
        $externalId ??= $this->derivedDivisionExternalId($competition, $name);
        $cacheKey = $sourceName.'|'.$externalId;
        if (isset($this->competitionDivisions[$cacheKey])) {
            return $this->competitionDivisions[$cacheKey]
                ->setName($name)
                ->setSourceUrl($sourceUrl);
        }

        /** @var CompetitionDivision|null $division */
        $division = $this->entityManager->getRepository(CompetitionDivision::class)->findOneBy([
            'sourceName' => $sourceName,
            'externalId' => $externalId,
        ]);

        if ($division === null) {
            $division = new CompetitionDivision($competition, $name, $sourceName, $externalId);
            $this->entityManager->persist($division);
        }

        $this->competitionDivisions[$cacheKey] = $division;

        return $division
            ->setName($name)
            ->setSourceUrl($sourceUrl);
    }

    private function derivedDivisionExternalId(Competition $competition, string $divisionName): string
    {
        $normalizedName = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $divisionName));
        $normalizedName = trim($normalizedName, '-') ?: substr(sha1($divisionName), 0, 12);
        $externalId = sprintf('%s:division:%s', $competition->getExternalId(), $normalizedName);

        if (strlen($externalId) <= 255) {
            return $externalId;
        }

        return sprintf('%s:division:%s', substr($competition->getExternalId(), 0, 225), substr(sha1($divisionName), 0, 16));
    }

    /**
     * @param list<string> $names
     */
    private function replaceMovements(Workout $workout, array $names): void
    {
        foreach ($workout->getMovements()->toArray() as $movement) {
            $workout->removeMovement($movement);
        }

        foreach ($this->matchEntitiesByName(Movement::class, $names) as $movement) {
            $workout->addMovement($movement);
        }
    }

    /**
     * @param list<string> $names
     */
    private function replaceImplements(Workout $workout, array $names): void
    {
        foreach ($workout->getImplements()->toArray() as $implement) {
            $workout->removeImplement($implement);
        }

        foreach ($this->matchEntitiesByName(Implement::class, $names) as $implement) {
            $workout->addImplement($implement);
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     * @param list<string>    $names
     *
     * @return list<T>
     */
    private function matchEntitiesByName(string $className, array $names): array
    {
        $entities = $this->entityManager->getRepository($className)->findAll();
        $byName = [];

        foreach ($entities as $entity) {
            if (method_exists($entity, 'getName')) {
                $byName[$this->normalizeName((string) $entity->getName())] = $entity;
            }
        }

        $matches = [];
        foreach ($names as $name) {
            $normalized = $this->normalizeName($name);
            if (isset($byName[$normalized])) {
                $matches[] = $byName[$normalized];
            }
        }

        return $matches;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param list<T> $cases
     *
     * @return T|null
     */
    private function matchEnumByNormalizedValue(array $cases, string $value): ?\BackedEnum
    {
        $normalized = $this->normalizeName($value);

        foreach ($cases as $case) {
            if ($this->normalizeName((string) $case->value) === $normalized) {
                return $case;
            }
        }

        return null;
    }

    private function normalizeName(string $name): string
    {
        $normalized = strtolower($name);
        $normalized = str_replace(['-', '_'], ' ', $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized;
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);

        return rtrim($normalized, 's');
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map($this->stringOrNull(...), $value)));
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function isPlaceholderWorkoutFlow(string $value): bool
    {
        return in_array(trim($value), ['*', '-', '–', '—'], true);
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function boolOrNull(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    private function dateTimeOrNull(mixed $value): ?\DateTimeImmutable
    {
        $value = $this->stringOrNull($value);
        if ($value === null) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function arrayOrNull(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        return $value;
    }

    private function hasFailures(): bool
    {
        foreach ($this->summary as $counts) {
            if ($counts['failed'] > 0) {
                return true;
            }
        }

        return false;
    }
}
