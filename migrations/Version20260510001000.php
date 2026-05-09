<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Unify workouts around monolithic text and remove legacy block/simple workout tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout ADD flow TEXT NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE workout ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE workout ADD workout_generation_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE workout ALTER flow DROP DEFAULT');
        $this->addSql('ALTER TABLE workout ALTER created_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN workout.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN workout.workout_generation_id IS \'(DC2Type:uuid)\'');

        $this->addSql('CREATE TABLE workout_implement (workout_id UUID NOT NULL, implement_id UUID NOT NULL, PRIMARY KEY(workout_id, implement_id))');
        $this->addSql('CREATE INDEX IDX_9637C341A6CCCFC9 ON workout_implement (workout_id)');
        $this->addSql('CREATE INDEX IDX_9637C341687C4337 ON workout_implement (implement_id)');
        $this->addSql('COMMENT ON COLUMN workout_implement.workout_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_implement.implement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE workout_implement ADD CONSTRAINT FK_WORKOUT_IMPLEMENT_WORKOUT FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_implement ADD CONSTRAINT FK_WORKOUT_IMPLEMENT_IMPLEMENT FOREIGN KEY (implement_id) REFERENCES implement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE workout_movement (workout_id UUID NOT NULL, movement_id UUID NOT NULL, PRIMARY KEY(workout_id, movement_id))');
        $this->addSql('CREATE INDEX IDX_2CAC7841A6CCCFC9 ON workout_movement (workout_id)');
        $this->addSql('CREATE INDEX IDX_2CAC7841229E70A7 ON workout_movement (movement_id)');
        $this->addSql('COMMENT ON COLUMN workout_movement.workout_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_movement.movement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE workout_movement ADD CONSTRAINT FK_WORKOUT_MOVEMENT_WORKOUT FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_movement ADD CONSTRAINT FK_WORKOUT_MOVEMENT_MOVEMENT FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('INSERT INTO workout (id, workout_origin_id, name, flow, number_of_rounds, time_cap, workout_type_id, created_at, workout_generation_id) SELECT id, workout_origin_id, name, flow, NULL, time_cap, NULL, created_at, workout_generation_id FROM simple_workout');
        $this->addSql('INSERT INTO workout_implement (workout_id, implement_id) SELECT simple_workout_id, implement_id FROM simple_workout_implement');
        $this->addSql('INSERT INTO workout_movement (workout_id, movement_id) SELECT simple_workout_id, movement_id FROM simple_workout_movement');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_649FFB72F27A47E5 ON workout (workout_generation_id)');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_WORKOUT_WORKOUT_GENERATION FOREIGN KEY (workout_generation_id) REFERENCES workout_generation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE simple_workout DROP CONSTRAINT FK_2ED6E589F27A47E5');
        $this->addSql('ALTER TABLE simple_workout DROP CONSTRAINT FK_2ED6E58974781498');
        $this->addSql('ALTER TABLE simple_workout_implement DROP CONSTRAINT FK_CC30DE3DC41C9559');
        $this->addSql('ALTER TABLE simple_workout_implement DROP CONSTRAINT FK_CC30DE3D687C4337');
        $this->addSql('ALTER TABLE simple_workout_movement DROP CONSTRAINT FK_2E6E1FBFC41C9559');
        $this->addSql('ALTER TABLE simple_workout_movement DROP CONSTRAINT FK_2E6E1FBF229E70A7');
        $this->addSql('DROP TABLE simple_workout_implement');
        $this->addSql('DROP TABLE simple_workout_movement');
        $this->addSql('DROP TABLE simple_workout');

        $this->addSql('ALTER TABLE workout_block DROP CONSTRAINT FK_DAD02436A6CCCFC9');
        $this->addSql('ALTER TABLE workout_block DROP CONSTRAINT FK_DAD02436E9ED820C');
        $this->addSql('ALTER TABLE block_movement_cluster DROP CONSTRAINT FK_BB3E3A53E9ED820C');
        $this->addSql('ALTER TABLE block_movement_cluster DROP CONSTRAINT FK_BB3E3A53B9324ECC');
        $this->addSql('ALTER TABLE movement_cluster_implement DROP CONSTRAINT FK_EE9D826EB9324ECC');
        $this->addSql('ALTER TABLE movement_cluster_implement DROP CONSTRAINT FK_EE9D826E687C4337');
        $this->addSql('ALTER TABLE movement_cluster DROP CONSTRAINT FK_CAC7354D229E70A7');
        $this->addSql('DROP TABLE workout_block');
        $this->addSql('DROP TABLE block_movement_cluster');
        $this->addSql('DROP TABLE movement_cluster_implement');
        $this->addSql('DROP TABLE block');
        $this->addSql('DROP TABLE movement_cluster');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE simple_workout (id UUID NOT NULL, workout_origin_id UUID DEFAULT NULL, workout_generation_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, flow TEXT NOT NULL, time_cap INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2ED6E58974781498 ON simple_workout (workout_origin_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2ED6E589F27A47E5 ON simple_workout (workout_generation_id)');
        $this->addSql('COMMENT ON COLUMN simple_workout.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN simple_workout.workout_origin_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN simple_workout.workout_generation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN simple_workout.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE simple_workout ADD CONSTRAINT FK_2ED6E58974781498 FOREIGN KEY (workout_origin_id) REFERENCES workout_origin (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE simple_workout ADD CONSTRAINT FK_2ED6E589F27A47E5 FOREIGN KEY (workout_generation_id) REFERENCES workout_generation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE simple_workout_implement (simple_workout_id UUID NOT NULL, implement_id UUID NOT NULL, PRIMARY KEY(simple_workout_id, implement_id))');
        $this->addSql('CREATE INDEX IDX_CC30DE3DC41C9559 ON simple_workout_implement (simple_workout_id)');
        $this->addSql('CREATE INDEX IDX_CC30DE3D687C4337 ON simple_workout_implement (implement_id)');
        $this->addSql('COMMENT ON COLUMN simple_workout_implement.simple_workout_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN simple_workout_implement.implement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE simple_workout_implement ADD CONSTRAINT FK_CC30DE3DC41C9559 FOREIGN KEY (simple_workout_id) REFERENCES simple_workout (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE simple_workout_implement ADD CONSTRAINT FK_CC30DE3D687C4337 FOREIGN KEY (implement_id) REFERENCES implement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE simple_workout_movement (simple_workout_id UUID NOT NULL, movement_id UUID NOT NULL, PRIMARY KEY(simple_workout_id, movement_id))');
        $this->addSql('CREATE INDEX IDX_2E6E1FBFC41C9559 ON simple_workout_movement (simple_workout_id)');
        $this->addSql('CREATE INDEX IDX_2E6E1FBF229E70A7 ON simple_workout_movement (movement_id)');
        $this->addSql('COMMENT ON COLUMN simple_workout_movement.simple_workout_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN simple_workout_movement.movement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE simple_workout_movement ADD CONSTRAINT FK_2E6E1FBFC41C9559 FOREIGN KEY (simple_workout_id) REFERENCES simple_workout (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE simple_workout_movement ADD CONSTRAINT FK_2E6E1FBF229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('INSERT INTO simple_workout (id, workout_origin_id, workout_generation_id, name, flow, time_cap, created_at) SELECT id, workout_origin_id, workout_generation_id, name, flow, time_cap, created_at FROM workout WHERE workout_generation_id IS NOT NULL');
        $this->addSql('INSERT INTO simple_workout_implement (simple_workout_id, implement_id) SELECT workout_id, implement_id FROM workout_implement WHERE workout_id IN (SELECT id FROM simple_workout)');
        $this->addSql('INSERT INTO simple_workout_movement (simple_workout_id, movement_id) SELECT workout_id, movement_id FROM workout_movement WHERE workout_id IN (SELECT id FROM simple_workout)');

        $this->addSql('CREATE TABLE block (id UUID NOT NULL, rounds INT NOT NULL, rest_time INT DEFAULT NULL, order_in_workout INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN block.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE movement_cluster (id UUID NOT NULL, movement_id UUID NOT NULL, repetitions INT NOT NULL, rep_unit VARCHAR(255) NOT NULL, implement_intensity_adjustment_value DOUBLE PRECISION DEFAULT NULL, implement_intensity_unit VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CAC7354D229E70A7 ON movement_cluster (movement_id)');
        $this->addSql('COMMENT ON COLUMN movement_cluster.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN movement_cluster.movement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE movement_cluster ADD CONSTRAINT FK_CAC7354D229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE block_movement_cluster (block_id UUID NOT NULL, movement_cluster_id UUID NOT NULL, PRIMARY KEY(block_id, movement_cluster_id))');
        $this->addSql('CREATE INDEX IDX_BB3E3A53E9ED820C ON block_movement_cluster (block_id)');
        $this->addSql('CREATE INDEX IDX_BB3E3A53B9324ECC ON block_movement_cluster (movement_cluster_id)');
        $this->addSql('COMMENT ON COLUMN block_movement_cluster.block_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN block_movement_cluster.movement_cluster_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE block_movement_cluster ADD CONSTRAINT FK_BB3E3A53E9ED820C FOREIGN KEY (block_id) REFERENCES block (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE block_movement_cluster ADD CONSTRAINT FK_BB3E3A53B9324ECC FOREIGN KEY (movement_cluster_id) REFERENCES movement_cluster (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE movement_cluster_implement (movement_cluster_id UUID NOT NULL, implement_id UUID NOT NULL, PRIMARY KEY(movement_cluster_id, implement_id))');
        $this->addSql('CREATE INDEX IDX_EE9D826EB9324ECC ON movement_cluster_implement (movement_cluster_id)');
        $this->addSql('CREATE INDEX IDX_EE9D826E687C4337 ON movement_cluster_implement (implement_id)');
        $this->addSql('COMMENT ON COLUMN movement_cluster_implement.movement_cluster_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN movement_cluster_implement.implement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE movement_cluster_implement ADD CONSTRAINT FK_EE9D826EB9324ECC FOREIGN KEY (movement_cluster_id) REFERENCES movement_cluster (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movement_cluster_implement ADD CONSTRAINT FK_EE9D826E687C4337 FOREIGN KEY (implement_id) REFERENCES implement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE workout_block (workout_id UUID NOT NULL, block_id UUID NOT NULL, PRIMARY KEY(workout_id, block_id))');
        $this->addSql('CREATE INDEX IDX_DAD02436A6CCCFC9 ON workout_block (workout_id)');
        $this->addSql('CREATE INDEX IDX_DAD02436E9ED820C ON workout_block (block_id)');
        $this->addSql('COMMENT ON COLUMN workout_block.workout_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_block.block_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE workout_block ADD CONSTRAINT FK_DAD02436A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_block ADD CONSTRAINT FK_DAD02436E9ED820C FOREIGN KEY (block_id) REFERENCES block (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE workout DROP CONSTRAINT FK_WORKOUT_WORKOUT_GENERATION');
        $this->addSql('DROP INDEX UNIQ_649FFB72F27A47E5');
        $this->addSql('ALTER TABLE workout_movement DROP CONSTRAINT FK_WORKOUT_MOVEMENT_WORKOUT');
        $this->addSql('ALTER TABLE workout_movement DROP CONSTRAINT FK_WORKOUT_MOVEMENT_MOVEMENT');
        $this->addSql('ALTER TABLE workout_implement DROP CONSTRAINT FK_WORKOUT_IMPLEMENT_WORKOUT');
        $this->addSql('ALTER TABLE workout_implement DROP CONSTRAINT FK_WORKOUT_IMPLEMENT_IMPLEMENT');
        $this->addSql('DROP TABLE workout_movement');
        $this->addSql('DROP TABLE workout_implement');
        $this->addSql('ALTER TABLE workout DROP workout_generation_id');
        $this->addSql('ALTER TABLE workout DROP created_at');
        $this->addSql('ALTER TABLE workout DROP flow');
    }
}
