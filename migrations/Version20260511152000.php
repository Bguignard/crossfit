<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511152000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add structured competition divisions for imported results.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE competition_division (id UUID NOT NULL, competition_id UUID NOT NULL, name VARCHAR(255) NOT NULL, source_name VARCHAR(64) NOT NULL, external_id VARCHAR(255) NOT NULL, source_url VARCHAR(2048) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_COMPETITION_DIVISION_SOURCE_EXTERNAL ON competition_division (source_name, external_id)');
        $this->addSql('CREATE INDEX IDX_21667C3B7B39D312 ON competition_division (competition_id)');
        $this->addSql('COMMENT ON COLUMN competition_division.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition_division.competition_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition_division.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN competition_division.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE competition_division ADD CONSTRAINT FK_COMPETITION_DIVISION_COMPETITION FOREIGN KEY (competition_id) REFERENCES competition (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE workout_result ADD competition_division_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_9E9DEDD69243DF7 ON workout_result (competition_division_id)');
        $this->addSql('COMMENT ON COLUMN workout_result.competition_division_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE workout_result ADD CONSTRAINT FK_WORKOUT_RESULT_COMPETITION_DIVISION FOREIGN KEY (competition_division_id) REFERENCES competition_division (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_result DROP CONSTRAINT FK_WORKOUT_RESULT_COMPETITION_DIVISION');
        $this->addSql('ALTER TABLE competition_division DROP CONSTRAINT FK_COMPETITION_DIVISION_COMPETITION');
        $this->addSql('DROP INDEX IDX_9E9DEDD69243DF7');
        $this->addSql('ALTER TABLE workout_result DROP competition_division_id');
        $this->addSql('DROP TABLE competition_division');
    }
}
