<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track workout AI generation usage and quota consumption.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workout_ai_generation_usage (id UUID NOT NULL, user_id UUID DEFAULT NULL, actor_type VARCHAR(32) NOT NULL, visitor_hash VARCHAR(128) DEFAULT NULL, endpoint VARCHAR(64) NOT NULL, generation_type VARCHAR(64) NOT NULL, model VARCHAR(128) DEFAULT NULL, prompt_tokens INT DEFAULT NULL, completion_tokens INT DEFAULT NULL, total_tokens INT DEFAULT NULL, duration_ms INT DEFAULT NULL, status VARCHAR(32) NOT NULL, failure_reason TEXT DEFAULT NULL, estimated_cost_usd NUMERIC(12, 6) DEFAULT NULL, quota_counted BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_WORKOUT_AI_USAGE_USER_CREATED ON workout_ai_generation_usage (user_id, created_at)');
        $this->addSql('CREATE INDEX IDX_WORKOUT_AI_USAGE_VISITOR_CREATED ON workout_ai_generation_usage (visitor_hash, created_at)');
        $this->addSql('COMMENT ON COLUMN workout_ai_generation_usage.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_ai_generation_usage.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_ai_generation_usage.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE workout_ai_generation_usage ADD CONSTRAINT FK_WORKOUT_AI_USAGE_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_ai_generation_usage DROP CONSTRAINT FK_WORKOUT_AI_USAGE_USER');
        $this->addSql('DROP TABLE workout_ai_generation_usage');
    }
}
