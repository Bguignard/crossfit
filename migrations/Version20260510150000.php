<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add source identity to imported workouts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout ADD source_name VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE workout ADD external_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE workout ADD source_url VARCHAR(2048) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_WORKOUT_SOURCE_EXTERNAL ON workout (source_name, external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_WORKOUT_SOURCE_EXTERNAL');
        $this->addSql('ALTER TABLE workout DROP source_name');
        $this->addSql('ALTER TABLE workout DROP external_id');
        $this->addSql('ALTER TABLE workout DROP source_url');
    }
}
