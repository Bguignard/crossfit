<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store imported competition participations independently from workout results';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE competition_participation (id UUID NOT NULL, athlete_id UUID NOT NULL, competition_id UUID NOT NULL, source_name VARCHAR(64) NOT NULL, external_id VARCHAR(255) NOT NULL, source_url VARCHAR(2048) DEFAULT NULL, rank VARCHAR(64) DEFAULT NULL, division VARCHAR(255) DEFAULT NULL, division_source_id VARCHAR(64) DEFAULT NULL, format VARCHAR(64) DEFAULT NULL, format_slug VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_COMPETITION_PARTICIPATION_SOURCE_EXTERNAL ON competition_participation (source_name, external_id)');
        $this->addSql('CREATE INDEX IDX_5C8020EFFE6BCB8B ON competition_participation (athlete_id)');
        $this->addSql('CREATE INDEX IDX_5C8020EF7B39D312 ON competition_participation (competition_id)');
        $this->addSql('COMMENT ON COLUMN competition_participation.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition_participation.athlete_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition_participation.competition_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition_participation.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN competition_participation.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE competition_participation ADD CONSTRAINT FK_COMPETITION_PARTICIPATION_ATHLETE FOREIGN KEY (athlete_id) REFERENCES athlete (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE competition_participation ADD CONSTRAINT FK_COMPETITION_PARTICIPATION_COMPETITION FOREIGN KEY (competition_id) REFERENCES competition (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE competition_participation');
    }
}
