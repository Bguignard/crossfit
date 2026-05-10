<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add programming generation requests.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE programming_generation_request (id UUID NOT NULL, user_id UUID NOT NULL, performance_profile_id UUID DEFAULT NULL, box_id UUID DEFAULT NULL, type VARCHAR(32) NOT NULL, status VARCHAR(32) NOT NULL, constraints JSON NOT NULL, input_snapshot JSON NOT NULL, generated_programming JSON DEFAULT NULL, error_message TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, queued_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FF8022AFA76ED395 ON programming_generation_request (user_id)');
        $this->addSql('CREATE INDEX IDX_FF8022AFB4135E66 ON programming_generation_request (performance_profile_id)');
        $this->addSql('CREATE INDEX IDX_FF8022AFD8177B3F ON programming_generation_request (box_id)');
        $this->addSql('COMMENT ON COLUMN programming_generation_request.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN programming_generation_request.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN programming_generation_request.performance_profile_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN programming_generation_request.box_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN programming_generation_request.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN programming_generation_request.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN programming_generation_request.queued_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN programming_generation_request.started_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN programming_generation_request.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE programming_generation_request ADD CONSTRAINT FK_PROGRAMMING_GENERATION_REQUEST_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE programming_generation_request ADD CONSTRAINT FK_PROGRAMMING_GENERATION_REQUEST_PROFILE FOREIGN KEY (performance_profile_id) REFERENCES user_performance_profile (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE programming_generation_request ADD CONSTRAINT FK_PROGRAMMING_GENERATION_REQUEST_BOX FOREIGN KEY (box_id) REFERENCES box (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE programming_generation_request DROP CONSTRAINT FK_PROGRAMMING_GENERATION_REQUEST_USER');
        $this->addSql('ALTER TABLE programming_generation_request DROP CONSTRAINT FK_PROGRAMMING_GENERATION_REQUEST_PROFILE');
        $this->addSql('ALTER TABLE programming_generation_request DROP CONSTRAINT FK_PROGRAMMING_GENERATION_REQUEST_BOX');
        $this->addSql('DROP TABLE programming_generation_request');
    }
}
