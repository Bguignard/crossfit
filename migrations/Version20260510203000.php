<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performance analysis requests.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE performance_analysis_request (id UUID NOT NULL, user_id UUID NOT NULL, performance_profile_id UUID NOT NULL, athlete_profile_id UUID DEFAULT NULL, status VARCHAR(32) NOT NULL, eligible_at_creation BOOLEAN NOT NULL, parameters JSON NOT NULL, input_snapshot JSON NOT NULL, result JSON DEFAULT NULL, error_message TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, queued_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5E416D4A76ED395 ON performance_analysis_request (user_id)');
        $this->addSql('CREATE INDEX IDX_5E416D4B4135E66 ON performance_analysis_request (performance_profile_id)');
        $this->addSql('CREATE INDEX IDX_5E416D4335319ED ON performance_analysis_request (athlete_profile_id)');
        $this->addSql('COMMENT ON COLUMN performance_analysis_request.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN performance_analysis_request.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN performance_analysis_request.performance_profile_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN performance_analysis_request.athlete_profile_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN performance_analysis_request.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN performance_analysis_request.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN performance_analysis_request.queued_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN performance_analysis_request.started_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN performance_analysis_request.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE performance_analysis_request ADD CONSTRAINT FK_PERFORMANCE_ANALYSIS_REQUEST_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE performance_analysis_request ADD CONSTRAINT FK_PERFORMANCE_ANALYSIS_REQUEST_PROFILE FOREIGN KEY (performance_profile_id) REFERENCES user_performance_profile (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE performance_analysis_request ADD CONSTRAINT FK_PERFORMANCE_ANALYSIS_REQUEST_ATHLETE_PROFILE FOREIGN KEY (athlete_profile_id) REFERENCES user_athlete_profile (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE performance_analysis_request DROP CONSTRAINT FK_PERFORMANCE_ANALYSIS_REQUEST_USER');
        $this->addSql('ALTER TABLE performance_analysis_request DROP CONSTRAINT FK_PERFORMANCE_ANALYSIS_REQUEST_PROFILE');
        $this->addSql('ALTER TABLE performance_analysis_request DROP CONSTRAINT FK_PERFORMANCE_ANALYSIS_REQUEST_ATHLETE_PROFILE');
        $this->addSql('DROP TABLE performance_analysis_request');
    }
}
