<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260518110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed workout generator catalog values required by the public flow.';
    }

    public function up(Schema $schema): void
    {
        $this->insertMissingNames('workout_type', [
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0001', 'AMRAP'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0002', 'For time'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0003', 'For weight'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0004', 'Intervals'],
        ]);

        $this->insertMissingNames('workout_movement_generation_type', [
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0011', 'body parts'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0012', 'selected movements'],
        ]);

        $this->insertMissingNames('movement_difficulty', [
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0021', 'Beginner'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0022', 'Intermediate'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0023', 'RX'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0024', 'Elite'],
        ]);

        $this->insertMissingNames('movement_type', [
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0031', 'Gymnastic'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0032', 'Weightlifting'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0033', 'Cardio'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0034', 'Strongman'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0035', 'Bodybuilding'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0036', 'Plyometric'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0037', 'Warm-up'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0038', 'Stretching'],
        ]);

        $this->insertMissingNames('body_part', [
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0041', 'legs'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0042', 'lower back'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0043', 'upper back'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0044', 'shoulders'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0045', 'arms'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0046', 'forearms'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0047', 'abs'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0048', 'chest'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0049', 'glutes'],
        ]);

        foreach ([
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0051', 'barbell'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0052', 'dumbbell'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0053', 'kettlebell'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0054', 'assault bike'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0055', 'ski erg'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0056', 'bike erg'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0057', 'rower'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0058', 'pull up bar'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0059', 'medicine ball'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0060', 'box'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0061', 'jump rope'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0062', 'bench'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0063', 'rope'],
            ['e6f52912-21f3-4e4c-87c3-0b6ca7fd0064', 'rings'],
        ] as [$id, $name]) {
            $this->addSql(sprintf(
                "INSERT INTO implement (id, name, implement_type_of_adjustable_measure_id) SELECT '%s', '%s', NULL WHERE NOT EXISTS (SELECT 1 FROM implement WHERE name = '%s')",
                $id,
                str_replace("'", "''", $name),
                str_replace("'", "''", $name),
            ));
        }
    }

    public function down(Schema $schema): void
    {
        foreach ([
            'workout_type',
            'workout_movement_generation_type',
            'movement_difficulty',
            'movement_type',
            'body_part',
            'implement',
        ] as $table) {
            $this->addSql(sprintf("DELETE FROM %s WHERE id::text LIKE 'e6f52912-21f3-4e4c-87c3-0b6ca7fd%%'", $table));
        }
    }

    /**
     * @param list<array{0: string, 1: string}> $rows
     */
    private function insertMissingNames(string $table, array $rows): void
    {
        foreach ($rows as [$id, $name]) {
            $this->addSql(sprintf(
                "INSERT INTO %s (id, name) SELECT '%s', '%s' WHERE NOT EXISTS (SELECT 1 FROM %s WHERE name = '%s')",
                $table,
                $id,
                str_replace("'", "''", $name),
                $table,
                str_replace("'", "''", $name),
            ));
        }
    }
}
