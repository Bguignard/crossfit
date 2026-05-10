<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user performance profile and metrics.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_performance_profile (id UUID NOT NULL, user_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8482D27A76ED395 ON user_performance_profile (user_id)');
        $this->addSql('COMMENT ON COLUMN user_performance_profile.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_performance_profile.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_performance_profile.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_performance_profile.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_performance_profile.completed_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE user_performance_metric (id UUID NOT NULL, profile_id UUID NOT NULL, metric_key VARCHAR(96) NOT NULL, category VARCHAR(32) NOT NULL, value_type VARCHAR(32) NOT NULL, numeric_value DOUBLE PRECISION DEFAULT NULL, boolean_value BOOLEAN DEFAULT NULL, unit VARCHAR(32) DEFAULT NULL, notes VARCHAR(1024) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4B7AE67ACCFA12B8 ON user_performance_metric (profile_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_PERFORMANCE_METRIC_PROFILE_KEY ON user_performance_metric (profile_id, metric_key)');
        $this->addSql('COMMENT ON COLUMN user_performance_metric.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_performance_metric.profile_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_performance_metric.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_performance_metric.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('ALTER TABLE user_performance_profile ADD CONSTRAINT FK_USER_PERFORMANCE_PROFILE_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_performance_metric ADD CONSTRAINT FK_USER_PERFORMANCE_METRIC_PROFILE FOREIGN KEY (profile_id) REFERENCES user_performance_profile (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_performance_metric DROP CONSTRAINT FK_USER_PERFORMANCE_METRIC_PROFILE');
        $this->addSql('ALTER TABLE user_performance_profile DROP CONSTRAINT FK_USER_PERFORMANCE_PROFILE_USER');
        $this->addSql('DROP TABLE user_performance_metric');
        $this->addSql('DROP TABLE user_performance_profile');
    }
}
