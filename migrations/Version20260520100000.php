<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add workout prescription standards for common CrossFit and HYROX loads.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE workout_prescription_standard (
                id UUID NOT NULL,
                source_name VARCHAR(64) NOT NULL,
                sport VARCHAR(32) NOT NULL,
                level_name VARCHAR(64) DEFAULT NULL,
                division VARCHAR(32) NOT NULL,
                movement_name VARCHAR(255) DEFAULT NULL,
                implement_name VARCHAR(255) DEFAULT NULL,
                quantity NUMERIC(8, 2) NOT NULL,
                unit VARCHAR(32) NOT NULL,
                quantity_multiplier INT NOT NULL,
                context_label VARCHAR(255) DEFAULT NULL,
                notes VARCHAR(255) DEFAULT NULL,
                priority INT NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql("COMMENT ON COLUMN workout_prescription_standard.id IS '(DC2Type:uuid)'");
        $this->addSql('CREATE INDEX idx_workout_prescription_standard_scope ON workout_prescription_standard (sport, level_name, division)');
        $this->addSql('CREATE INDEX idx_workout_prescription_standard_movement ON workout_prescription_standard (movement_name)');
        $this->addSql('CREATE INDEX idx_workout_prescription_standard_implement ON workout_prescription_standard (implement_name)');

        foreach ($this->standards() as $standard) {
            $this->insertStandard($standard);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE workout_prescription_standard');
    }

    /**
     * @return list<array{
     *     id: string,
     *     source_name: string,
     *     sport: string,
     *     level_name: string|null,
     *     division: string,
     *     movement_name: string|null,
     *     implement_name: string|null,
     *     quantity: float,
     *     unit: string,
     *     quantity_multiplier: int,
     *     context_label: string|null,
     *     notes: string|null,
     *     priority: int
     * }>
     */
    private function standards(): array
    {
        return [
            $this->row('a1000000-0000-4000-8000-000000000001', 'hyrox_official_25_26', 'hyrox', 'RX', 'women', 'Sled Push', 'sled', 102, 'kg', 1, 'Open women', 'Includes sled', 10),
            $this->row('a1000000-0000-4000-8000-000000000002', 'hyrox_official_25_26', 'hyrox', 'RX', 'men', 'Sled Push', 'sled', 152, 'kg', 1, 'Open men / mixed', 'Includes sled', 10),
            $this->row('a1000000-0000-4000-8000-000000000003', 'hyrox_official_25_26', 'hyrox', 'Elite', 'women', 'Sled Push', 'sled', 152, 'kg', 1, 'Pro women', 'Includes sled', 10),
            $this->row('a1000000-0000-4000-8000-000000000004', 'hyrox_official_25_26', 'hyrox', 'Elite', 'men', 'Sled Push', 'sled', 202, 'kg', 1, 'Pro men', 'Includes sled', 10),
            $this->row('a1000000-0000-4000-8000-000000000005', 'hyrox_official_25_26', 'hyrox', 'RX', 'women', 'Sled Pull', 'sled', 78, 'kg', 1, 'Open women', 'Includes sled', 10),
            $this->row('a1000000-0000-4000-8000-000000000006', 'hyrox_official_25_26', 'hyrox', 'RX', 'men', 'Sled Pull', 'sled', 103, 'kg', 1, 'Open men / mixed', 'Includes sled', 10),
            $this->row('a1000000-0000-4000-8000-000000000007', 'hyrox_official_25_26', 'hyrox', 'Elite', 'women', 'Sled Pull', 'sled', 103, 'kg', 1, 'Pro women', 'Includes sled', 10),
            $this->row('a1000000-0000-4000-8000-000000000008', 'hyrox_official_25_26', 'hyrox', 'Elite', 'men', 'Sled Pull', 'sled', 153, 'kg', 1, 'Pro men', 'Includes sled', 10),
            $this->row('a1000000-0000-4000-8000-000000000009', 'hyrox_official_25_26', 'hyrox', 'RX', 'women', 'Farmer Carry', 'kettlebell', 16, 'kg', 2, 'Open women', null, 10),
            $this->row('a1000000-0000-4000-8000-000000000010', 'hyrox_official_25_26', 'hyrox', 'RX', 'men', 'Farmer Carry', 'kettlebell', 24, 'kg', 2, 'Open men / mixed', null, 10),
            $this->row('a1000000-0000-4000-8000-000000000011', 'hyrox_official_25_26', 'hyrox', 'Elite', 'women', 'Farmer Carry', 'kettlebell', 24, 'kg', 2, 'Pro women', null, 10),
            $this->row('a1000000-0000-4000-8000-000000000012', 'hyrox_official_25_26', 'hyrox', 'Elite', 'men', 'Farmer Carry', 'kettlebell', 32, 'kg', 2, 'Pro men', null, 10),
            $this->row('a1000000-0000-4000-8000-000000000013', 'hyrox_official_25_26', 'hyrox', 'RX', 'women', 'Walking Lunge', 'sand bag', 10, 'kg', 1, 'Open women sandbag lunges', null, 10),
            $this->row('a1000000-0000-4000-8000-000000000014', 'hyrox_official_25_26', 'hyrox', 'RX', 'men', 'Walking Lunge', 'sand bag', 20, 'kg', 1, 'Open men / mixed sandbag lunges', null, 10),
            $this->row('a1000000-0000-4000-8000-000000000015', 'hyrox_official_25_26', 'hyrox', 'Elite', 'women', 'Walking Lunge', 'sand bag', 20, 'kg', 1, 'Pro women sandbag lunges', null, 10),
            $this->row('a1000000-0000-4000-8000-000000000016', 'hyrox_official_25_26', 'hyrox', 'Elite', 'men', 'Walking Lunge', 'sand bag', 30, 'kg', 1, 'Pro men sandbag lunges', null, 10),
            $this->row('a1000000-0000-4000-8000-000000000017', 'hyrox_official_25_26', 'hyrox', 'RX', 'women', 'Wall Ball Shot', 'medicine ball', 4, 'kg', 1, 'Open women', '100 reps', 10),
            $this->row('a1000000-0000-4000-8000-000000000018', 'hyrox_official_25_26', 'hyrox', 'RX', 'men', 'Wall Ball Shot', 'medicine ball', 6, 'kg', 1, 'Open men / mixed', '100 reps', 10),
            $this->row('a1000000-0000-4000-8000-000000000019', 'hyrox_official_25_26', 'hyrox', 'Elite', 'women', 'Wall Ball Shot', 'medicine ball', 6, 'kg', 1, 'Pro women', '100 reps', 10),
            $this->row('a1000000-0000-4000-8000-000000000020', 'hyrox_official_25_26', 'hyrox', 'Elite', 'men', 'Wall Ball Shot', 'medicine ball', 9, 'kg', 1, 'Pro men', '100 reps', 10),
            $this->row('a1000000-0000-4000-8000-000000000021', 'crossfit_common', 'crossfit', 'RX', 'men', null, 'dumbbell', 22.5, 'kg', 1, 'Common RX single dumbbell', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000022', 'crossfit_common', 'crossfit', 'RX', 'women', null, 'dumbbell', 15, 'kg', 1, 'Common RX single dumbbell', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000023', 'crossfit_common', 'crossfit', 'Elite', 'men', null, 'dumbbell', 32.5, 'kg', 1, 'Common Elite single dumbbell', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000024', 'crossfit_common', 'crossfit', 'Elite', 'women', null, 'dumbbell', 22.5, 'kg', 1, 'Common Elite single dumbbell', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000025', 'crossfit_common', 'crossfit', 'RX', 'men', null, 'kettlebell', 24, 'kg', 1, 'Common RX kettlebell', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000026', 'crossfit_common', 'crossfit', 'RX', 'women', null, 'kettlebell', 16, 'kg', 1, 'Common RX kettlebell', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000027', 'crossfit_common', 'crossfit', 'Elite', 'men', null, 'kettlebell', 32, 'kg', 1, 'Common Elite kettlebell', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000028', 'crossfit_common', 'crossfit', 'Elite', 'women', null, 'kettlebell', 24, 'kg', 1, 'Common Elite kettlebell', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000029', 'crossfit_common', 'crossfit', 'RX', 'men', 'Wall Ball Shot', 'medicine ball', 9, 'kg', 1, 'Common RX wall ball', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000030', 'crossfit_common', 'crossfit', 'RX', 'women', 'Wall Ball Shot', 'medicine ball', 6, 'kg', 1, 'Common RX wall ball', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000031', 'crossfit_common', 'crossfit', 'Elite', 'men', 'Wall Ball Shot', 'medicine ball', 12, 'kg', 1, 'Common Elite wall ball', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000032', 'crossfit_common', 'crossfit', 'Elite', 'women', 'Wall Ball Shot', 'medicine ball', 9, 'kg', 1, 'Common Elite wall ball', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000033', 'crossfit_common', 'crossfit', 'RX', 'men', 'Thruster', 'barbell', 42.5, 'kg', 1, '95 lb-style RX thruster', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000034', 'crossfit_common', 'crossfit', 'RX', 'women', 'Thruster', 'barbell', 30, 'kg', 1, '65 lb-style RX thruster', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000035', 'crossfit_common', 'crossfit', 'Elite', 'men', 'Thruster', 'barbell', 61, 'kg', 1, '135 lb-style Elite thruster', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000036', 'crossfit_common', 'crossfit', 'Elite', 'women', 'Thruster', 'barbell', 43, 'kg', 1, '95 lb-style Elite thruster', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000037', 'crossfit_common', 'crossfit', 'RX', 'men', 'Deadlift', 'barbell', 102, 'kg', 1, '225 lb-style RX deadlift', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000038', 'crossfit_common', 'crossfit', 'RX', 'women', 'Deadlift', 'barbell', 70, 'kg', 1, '155 lb-style RX deadlift', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000039', 'crossfit_common', 'crossfit', 'Elite', 'men', 'Deadlift', 'barbell', 143, 'kg', 1, '315 lb-style Elite deadlift', null, 30),
            $this->row('a1000000-0000-4000-8000-000000000040', 'crossfit_common', 'crossfit', 'Elite', 'women', 'Deadlift', 'barbell', 102, 'kg', 1, '225 lb-style Elite deadlift', null, 30),
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     source_name: string,
     *     sport: string,
     *     level_name: string|null,
     *     division: string,
     *     movement_name: string|null,
     *     implement_name: string|null,
     *     quantity: float,
     *     unit: string,
     *     quantity_multiplier: int,
     *     context_label: string|null,
     *     notes: string|null,
     *     priority: int
     * }
     */
    private function row(
        string $id,
        string $sourceName,
        string $sport,
        ?string $levelName,
        string $division,
        ?string $movementName,
        ?string $implementName,
        float $quantity,
        string $unit,
        int $quantityMultiplier,
        ?string $contextLabel,
        ?string $notes,
        int $priority,
    ): array {
        return [
            'id' => $id,
            'source_name' => $sourceName,
            'sport' => $sport,
            'level_name' => $levelName,
            'division' => $division,
            'movement_name' => $movementName,
            'implement_name' => $implementName,
            'quantity' => $quantity,
            'unit' => $unit,
            'quantity_multiplier' => $quantityMultiplier,
            'context_label' => $contextLabel,
            'notes' => $notes,
            'priority' => $priority,
        ];
    }

    /**
     * @param array<string, mixed> $standard
     */
    private function insertStandard(array $standard): void
    {
        $columns = array_keys($standard);
        $values = array_map(fn (mixed $value): string => $this->sqlValue($value), array_values($standard));

        $this->addSql(sprintf(
            'INSERT INTO workout_prescription_standard (%s) VALUES (%s)',
            implode(', ', $columns),
            implode(', ', $values),
        ));
    }

    private function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'".str_replace("'", "''", (string) $value)."'";
    }
}
