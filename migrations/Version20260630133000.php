<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store imported workout canonical fingerprints and event provenances.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout ADD normalized_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE workout ADD normalized_flow TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE workout ADD canonical_fingerprint VARCHAR(128) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_WORKOUT_CANONICAL_FINGERPRINT ON workout (canonical_fingerprint)');
        $this->addSql('ALTER TABLE competition_event ADD provenances JSONB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE competition_event DROP provenances');
        $this->addSql('DROP INDEX IDX_WORKOUT_CANONICAL_FINGERPRINT');
        $this->addSql('ALTER TABLE workout DROP canonical_fingerprint');
        $this->addSql('ALTER TABLE workout DROP normalized_flow');
        $this->addSql('ALTER TABLE workout DROP normalized_name');
    }
}
