<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add imported competition data model.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE athlete (id UUID NOT NULL, display_name VARCHAR(255) NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, gender VARCHAR(16) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, source_name VARCHAR(64) NOT NULL, external_id VARCHAR(255) NOT NULL, source_url VARCHAR(2048) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ATHLETE_SOURCE_EXTERNAL ON athlete (source_name, external_id)');
        $this->addSql('COMMENT ON COLUMN athlete.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN athlete.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN athlete.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE competition (id UUID NOT NULL, name VARCHAR(255) NOT NULL, season INT DEFAULT NULL, source_name VARCHAR(64) NOT NULL, external_id VARCHAR(255) NOT NULL, source_url VARCHAR(2048) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_COMPETITION_SOURCE_EXTERNAL ON competition (source_name, external_id)');
        $this->addSql('COMMENT ON COLUMN competition.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN competition.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE competition_event (id UUID NOT NULL, competition_id UUID NOT NULL, workout_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, event_order INT DEFAULT NULL, source_name VARCHAR(64) NOT NULL, external_id VARCHAR(255) NOT NULL, source_url VARCHAR(2048) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_COMPETITION_EVENT_SOURCE_EXTERNAL ON competition_event (source_name, external_id)');
        $this->addSql('CREATE INDEX IDX_C81938717B39D312 ON competition_event (competition_id)');
        $this->addSql('CREATE INDEX IDX_C8193871A6CCCFC9 ON competition_event (workout_id)');
        $this->addSql('COMMENT ON COLUMN competition_event.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition_event.competition_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition_event.workout_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition_event.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN competition_event.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE score (id UUID NOT NULL, type VARCHAR(32) NOT NULL, raw_value VARCHAR(255) NOT NULL, display_value VARCHAR(255) DEFAULT NULL, numeric_value DOUBLE PRECISION DEFAULT NULL, time_in_seconds INT DEFAULT NULL, unit VARCHAR(32) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN score.id IS \'(DC2Type:uuid)\'');

        $this->addSql('CREATE TABLE workout_result (id UUID NOT NULL, athlete_id UUID NOT NULL, event_id UUID NOT NULL, score_id UUID NOT NULL, rank INT DEFAULT NULL, division VARCHAR(255) DEFAULT NULL, points INT DEFAULT NULL, source_name VARCHAR(64) NOT NULL, external_id VARCHAR(255) NOT NULL, source_url VARCHAR(2048) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_WORKOUT_RESULT_SOURCE_EXTERNAL ON workout_result (source_name, external_id)');
        $this->addSql('CREATE INDEX IDX_9E9DEDDFE6BCB8B ON workout_result (athlete_id)');
        $this->addSql('CREATE INDEX IDX_9E9DEDD71F7E88B ON workout_result (event_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9E9DEDD12EB0A51 ON workout_result (score_id)');
        $this->addSql('COMMENT ON COLUMN workout_result.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_result.athlete_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_result.event_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_result.score_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_result.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN workout_result.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('ALTER TABLE competition_event ADD CONSTRAINT FK_COMPETITION_EVENT_COMPETITION FOREIGN KEY (competition_id) REFERENCES competition (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE competition_event ADD CONSTRAINT FK_COMPETITION_EVENT_WORKOUT FOREIGN KEY (workout_id) REFERENCES workout (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_result ADD CONSTRAINT FK_WORKOUT_RESULT_ATHLETE FOREIGN KEY (athlete_id) REFERENCES athlete (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_result ADD CONSTRAINT FK_WORKOUT_RESULT_EVENT FOREIGN KEY (event_id) REFERENCES competition_event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_result ADD CONSTRAINT FK_WORKOUT_RESULT_SCORE FOREIGN KEY (score_id) REFERENCES score (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_result DROP CONSTRAINT FK_WORKOUT_RESULT_ATHLETE');
        $this->addSql('ALTER TABLE workout_result DROP CONSTRAINT FK_WORKOUT_RESULT_EVENT');
        $this->addSql('ALTER TABLE workout_result DROP CONSTRAINT FK_WORKOUT_RESULT_SCORE');
        $this->addSql('ALTER TABLE competition_event DROP CONSTRAINT FK_COMPETITION_EVENT_COMPETITION');
        $this->addSql('ALTER TABLE competition_event DROP CONSTRAINT FK_COMPETITION_EVENT_WORKOUT');
        $this->addSql('DROP TABLE workout_result');
        $this->addSql('DROP TABLE score');
        $this->addSql('DROP TABLE competition_event');
        $this->addSql('DROP TABLE competition');
        $this->addSql('DROP TABLE athlete');
    }
}
