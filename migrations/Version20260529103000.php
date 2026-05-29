<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add official competition qualification suggestions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE competition_official_qualification (id UUID NOT NULL, competition_id UUID NOT NULL, confirmed_by_id UUID DEFAULT NULL, season INT DEFAULT NULL, circuit VARCHAR(64) NOT NULL, stage VARCHAR(64) NOT NULL, division_pattern VARCHAR(255) NOT NULL, status VARCHAR(32) NOT NULL, source VARCHAR(32) NOT NULL, notes VARCHAR(512) DEFAULT NULL, confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, dismissed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_COMPETITION_OFFICIAL_QUALIFICATION_SCOPE ON competition_official_qualification (competition_id, circuit, stage, division_pattern)');
        $this->addSql('CREATE INDEX IDX_D6D027507B39D312 ON competition_official_qualification (competition_id)');
        $this->addSql('CREATE INDEX IDX_D6D027506F45385D ON competition_official_qualification (confirmed_by_id)');
        $this->addSql('COMMENT ON COLUMN competition_official_qualification.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition_official_qualification.competition_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition_official_qualification.confirmed_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition_official_qualification.confirmed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN competition_official_qualification.dismissed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN competition_official_qualification.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN competition_official_qualification.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE competition_official_qualification ADD CONSTRAINT FK_COMPETITION_OFFICIAL_QUALIFICATION_COMPETITION FOREIGN KEY (competition_id) REFERENCES competition (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE competition_official_qualification ADD CONSTRAINT FK_COMPETITION_OFFICIAL_QUALIFICATION_CONFIRMED_BY FOREIGN KEY (confirmed_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE competition_official_qualification');
    }
}
