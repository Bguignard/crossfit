<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251001210145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE simple_workout (id UUID NOT NULL, workout_origin_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, flow TEXT NOT NULL, time_cap INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2ED6E58974781498 ON simple_workout (workout_origin_id)');
        $this->addSql('COMMENT ON COLUMN simple_workout.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN simple_workout.workout_origin_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN simple_workout.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE simple_workout_implement (simple_workout_id UUID NOT NULL, implement_id UUID NOT NULL, PRIMARY KEY(simple_workout_id, implement_id))');
        $this->addSql('CREATE INDEX IDX_CC30DE3DC41C9559 ON simple_workout_implement (simple_workout_id)');
        $this->addSql('CREATE INDEX IDX_CC30DE3D687C4337 ON simple_workout_implement (implement_id)');
        $this->addSql('COMMENT ON COLUMN simple_workout_implement.simple_workout_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN simple_workout_implement.implement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE simple_workout_movement (simple_workout_id UUID NOT NULL, movement_id UUID NOT NULL, PRIMARY KEY(simple_workout_id, movement_id))');
        $this->addSql('CREATE INDEX IDX_2E6E1FBFC41C9559 ON simple_workout_movement (simple_workout_id)');
        $this->addSql('CREATE INDEX IDX_2E6E1FBF229E70A7 ON simple_workout_movement (movement_id)');
        $this->addSql('COMMENT ON COLUMN simple_workout_movement.simple_workout_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN simple_workout_movement.movement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE simple_workout ADD CONSTRAINT FK_2ED6E58974781498 FOREIGN KEY (workout_origin_id) REFERENCES workout_origin (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE simple_workout_implement ADD CONSTRAINT FK_CC30DE3DC41C9559 FOREIGN KEY (simple_workout_id) REFERENCES simple_workout (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE simple_workout_implement ADD CONSTRAINT FK_CC30DE3D687C4337 FOREIGN KEY (implement_id) REFERENCES implement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE simple_workout_movement ADD CONSTRAINT FK_2E6E1FBFC41C9559 FOREIGN KEY (simple_workout_id) REFERENCES simple_workout (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE simple_workout_movement ADD CONSTRAINT FK_2E6E1FBF229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE simple_workout DROP CONSTRAINT FK_2ED6E58974781498');
        $this->addSql('ALTER TABLE simple_workout_implement DROP CONSTRAINT FK_CC30DE3DC41C9559');
        $this->addSql('ALTER TABLE simple_workout_implement DROP CONSTRAINT FK_CC30DE3D687C4337');
        $this->addSql('ALTER TABLE simple_workout_movement DROP CONSTRAINT FK_2E6E1FBFC41C9559');
        $this->addSql('ALTER TABLE simple_workout_movement DROP CONSTRAINT FK_2E6E1FBF229E70A7');
        $this->addSql('DROP TABLE simple_workout');
        $this->addSql('DROP TABLE simple_workout_implement');
        $this->addSql('DROP TABLE simple_workout_movement');
    }
}
