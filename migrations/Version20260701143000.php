<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store structured performance breakdowns for athlete competition results.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_result ADD performance_breakdown JSONB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_result DROP performance_breakdown');
    }
}
