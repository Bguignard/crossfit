<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260514193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stored public athlete analyses.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE athlete_public_analysis (id UUID NOT NULL, athlete_id UUID NOT NULL, kind VARCHAR(64) NOT NULL, prompt_hash VARCHAR(64) NOT NULL, analysis JSON NOT NULL, generated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ATHLETE_PUBLIC_ANALYSIS_KIND ON athlete_public_analysis (athlete_id, kind)');
        $this->addSql('CREATE INDEX IDX_477897E8FE6BCB8B ON athlete_public_analysis (athlete_id)');
        $this->addSql('COMMENT ON COLUMN athlete_public_analysis.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN athlete_public_analysis.athlete_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN athlete_public_analysis.generated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN athlete_public_analysis.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN athlete_public_analysis.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE athlete_public_analysis ADD CONSTRAINT FK_ATHLETE_PUBLIC_ANALYSIS_ATHLETE FOREIGN KEY (athlete_id) REFERENCES athlete (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE athlete_public_analysis DROP CONSTRAINT FK_ATHLETE_PUBLIC_ANALYSIS_ATHLETE');
        $this->addSql('DROP TABLE athlete_public_analysis');
    }
}
