<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\DataFixtures\MissingHeroWorkoutCatalog;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

final class Version20260511143000 extends AbstractMigration
{
    private const string SOURCE_NAME = 'monwod_catalog';
    private const string UUID_NAMESPACE = '2bbd37a8-1c25-4d4a-bc43-cce201177ac3';

    public function getDescription(): string
    {
        return 'Import missing Hero WOD catalog entries into production data.';
    }

    public function up(Schema $schema): void
    {
        foreach (MissingHeroWorkoutCatalog::workouts() as $externalId => $workout) {
            $this->addSql(<<<'SQL'
                WITH hero_origin AS (
                    SELECT wo.id
                    FROM workout_origin wo
                    INNER JOIN workout_origin_name won ON wo.name_id = won.id
                    WHERE won.name = 'Hero workout' AND wo.year IS NULL
                    LIMIT 1
                ),
                selected_workout_type AS (
                    SELECT id
                    FROM workout_type
                    WHERE name = :workout_type
                    LIMIT 1
                )
                INSERT INTO workout (
                    id,
                    name,
                    flow,
                    number_of_rounds,
                    time_cap,
                    workout_type_id,
                    workout_origin_id,
                    created_at,
                    source_name,
                    external_id
                )
                SELECT
                    :id,
                    :name,
                    :flow,
                    NULL,
                    NULL,
                    selected_workout_type.id,
                    hero_origin.id,
                    NOW(),
                    :source_name,
                    :external_id
                FROM hero_origin, selected_workout_type
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM workout existing
                    LEFT JOIN workout_origin existing_origin ON existing.workout_origin_id = existing_origin.id
                    LEFT JOIN workout_origin_name existing_origin_name ON existing_origin.name_id = existing_origin_name.id
                    WHERE (
                        existing.source_name = :source_name
                        AND existing.external_id = :external_id
                    )
                    OR (
                        existing_origin_name.name = 'Hero workout'
                        AND lower(regexp_replace(existing.name, '[^a-zA-Z0-9]', '', 'g')) = :normalized_name
                    )
                )
                SQL,
                [
                    'id' => (string) Uuid::v5(Uuid::fromString(self::UUID_NAMESPACE), $externalId),
                    'name' => $workout['name'],
                    'flow' => $workout['flow'],
                    'workout_type' => $workout['workoutType'],
                    'source_name' => self::SOURCE_NAME,
                    'external_id' => $externalId,
                    'normalized_name' => $this->normalizeName($workout['name']),
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(MissingHeroWorkoutCatalog::workouts()) as $externalId) {
            $this->addSql(
                'DELETE FROM workout WHERE source_name = :source_name AND external_id = :external_id',
                [
                    'source_name' => self::SOURCE_NAME,
                    'external_id' => $externalId,
                ]
            );
        }
    }

    private function normalizeName(string $name): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $name));
    }
}
