<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250430204644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE block (id UUID NOT NULL, rounds INT NOT NULL, rest_time INT DEFAULT NULL, order_in_workout INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN block.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE block_movement_cluster (block_id UUID NOT NULL, movement_cluster_id UUID NOT NULL, PRIMARY KEY(block_id, movement_cluster_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BB3E3A53E9ED820C ON block_movement_cluster (block_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BB3E3A53B9324ECC ON block_movement_cluster (movement_cluster_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN block_movement_cluster.block_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN block_movement_cluster.movement_cluster_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE body_part (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN body_part.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE implement (id UUID NOT NULL, implement_type_of_adjustable_measure_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B4EDA4154B3CB714 ON implement (implement_type_of_adjustable_measure_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN implement.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN implement.implement_type_of_adjustable_measure_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE implement_type_of_adjustable_measure_unit (id UUID NOT NULL, implement_type_of_measure_enum VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN implement_type_of_adjustable_measure_unit.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE implement_type_of_adjustable_measure_unit_measure_unit (implement_type_of_adjustable_measure_unit_id UUID NOT NULL, measure_unit_id UUID NOT NULL, PRIMARY KEY(implement_type_of_adjustable_measure_unit_id, measure_unit_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F7AFF469581D14F ON implement_type_of_adjustable_measure_unit_measure_unit (implement_type_of_adjustable_measure_unit_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F7AFF46963C6A475 ON implement_type_of_adjustable_measure_unit_measure_unit (measure_unit_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN implement_type_of_adjustable_measure_unit_measure_unit.implement_type_of_adjustable_measure_unit_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN implement_type_of_adjustable_measure_unit_measure_unit.measure_unit_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE measure_unit (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN measure_unit.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE movement (id UUID NOT NULL, name VARCHAR(255) NOT NULL, difficulty INT NOT NULL, movement_type VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE movement_body_part (movement_id UUID NOT NULL, body_part_id UUID NOT NULL, PRIMARY KEY(movement_id, body_part_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2EAC802F229E70A7 ON movement_body_part (movement_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2EAC802FA515F27A ON movement_body_part (body_part_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_body_part.movement_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_body_part.body_part_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE movement_implement (movement_id UUID NOT NULL, implement_id UUID NOT NULL, PRIMARY KEY(movement_id, implement_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_86CAE89E229E70A7 ON movement_implement (movement_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_86CAE89E687C4337 ON movement_implement (implement_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_implement.movement_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_implement.implement_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE movement_movement_execution_time_for_measure_unit (movement_id UUID NOT NULL, movement_execution_time_for_measure_unit_id UUID NOT NULL, PRIMARY KEY(movement_id, movement_execution_time_for_measure_unit_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D0D4113D229E70A7 ON movement_movement_execution_time_for_measure_unit (movement_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D0D4113DEC4A7EC7 ON movement_movement_execution_time_for_measure_unit (movement_execution_time_for_measure_unit_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_movement_execution_time_for_measure_unit.movement_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_movement_execution_time_for_measure_unit.movement_execution_time_for_measure_unit_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE movement_cluster (id UUID NOT NULL, movement_id UUID NOT NULL, repetitions INT NOT NULL, rep_unit VARCHAR(255) NOT NULL, implement_intensity_adjustment_value DOUBLE PRECISION DEFAULT NULL, implement_intensity_unit VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CAC7354D229E70A7 ON movement_cluster (movement_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_cluster.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_cluster.movement_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE movement_cluster_implement (movement_cluster_id UUID NOT NULL, implement_id UUID NOT NULL, PRIMARY KEY(movement_cluster_id, implement_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_EE9D826EB9324ECC ON movement_cluster_implement (movement_cluster_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_EE9D826E687C4337 ON movement_cluster_implement (implement_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_cluster_implement.movement_cluster_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_cluster_implement.implement_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE movement_execution_time_for_measure_unit (id UUID NOT NULL, measure_unit VARCHAR(255) NOT NULL, execution_time_in_milliseconds INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_execution_time_for_measure_unit.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE workout (id UUID NOT NULL, workout_origin_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, number_of_rounds INT DEFAULT NULL, time_cap INT DEFAULT NULL, workout_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_649FFB7274781498 ON workout (workout_origin_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN workout.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN workout.workout_origin_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE workout_block (workout_id UUID NOT NULL, block_id UUID NOT NULL, PRIMARY KEY(workout_id, block_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DAD02436A6CCCFC9 ON workout_block (workout_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DAD02436E9ED820C ON workout_block (block_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN workout_block.workout_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN workout_block.block_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE workout_origin (id UUID NOT NULL, name VARCHAR(255) DEFAULT NULL, year INT DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN workout_origin.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.available_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.delivered_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
                BEGIN
                    PERFORM pg_notify('messenger_messages', NEW.queue_name::text);
                    RETURN NEW;
                END;
            $$ LANGUAGE plpgsql;
        SQL);
        $this->addSql(<<<'SQL'
            DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE block_movement_cluster ADD CONSTRAINT FK_BB3E3A53E9ED820C FOREIGN KEY (block_id) REFERENCES block (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE block_movement_cluster ADD CONSTRAINT FK_BB3E3A53B9324ECC FOREIGN KEY (movement_cluster_id) REFERENCES movement_cluster (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE implement ADD CONSTRAINT FK_B4EDA4154B3CB714 FOREIGN KEY (implement_type_of_adjustable_measure_id) REFERENCES implement_type_of_adjustable_measure_unit (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE implement_type_of_adjustable_measure_unit_measure_unit ADD CONSTRAINT FK_F7AFF469581D14F FOREIGN KEY (implement_type_of_adjustable_measure_unit_id) REFERENCES implement_type_of_adjustable_measure_unit (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE implement_type_of_adjustable_measure_unit_measure_unit ADD CONSTRAINT FK_F7AFF46963C6A475 FOREIGN KEY (measure_unit_id) REFERENCES measure_unit (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_body_part ADD CONSTRAINT FK_2EAC802F229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_body_part ADD CONSTRAINT FK_2EAC802FA515F27A FOREIGN KEY (body_part_id) REFERENCES body_part (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_implement ADD CONSTRAINT FK_86CAE89E229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_implement ADD CONSTRAINT FK_86CAE89E687C4337 FOREIGN KEY (implement_id) REFERENCES implement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_movement_execution_time_for_measure_unit ADD CONSTRAINT FK_D0D4113D229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_movement_execution_time_for_measure_unit ADD CONSTRAINT FK_D0D4113DEC4A7EC7 FOREIGN KEY (movement_execution_time_for_measure_unit_id) REFERENCES movement_execution_time_for_measure_unit (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_cluster ADD CONSTRAINT FK_CAC7354D229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_cluster_implement ADD CONSTRAINT FK_EE9D826EB9324ECC FOREIGN KEY (movement_cluster_id) REFERENCES movement_cluster (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_cluster_implement ADD CONSTRAINT FK_EE9D826E687C4337 FOREIGN KEY (implement_id) REFERENCES implement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workout ADD CONSTRAINT FK_649FFB7274781498 FOREIGN KEY (workout_origin_id) REFERENCES workout_origin (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workout_block ADD CONSTRAINT FK_DAD02436A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workout_block ADD CONSTRAINT FK_DAD02436E9ED820C FOREIGN KEY (block_id) REFERENCES block (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE block_movement_cluster DROP CONSTRAINT FK_BB3E3A53E9ED820C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE block_movement_cluster DROP CONSTRAINT FK_BB3E3A53B9324ECC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE implement DROP CONSTRAINT FK_B4EDA4154B3CB714
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE implement_type_of_adjustable_measure_unit_measure_unit DROP CONSTRAINT FK_F7AFF469581D14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE implement_type_of_adjustable_measure_unit_measure_unit DROP CONSTRAINT FK_F7AFF46963C6A475
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_body_part DROP CONSTRAINT FK_2EAC802F229E70A7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_body_part DROP CONSTRAINT FK_2EAC802FA515F27A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_implement DROP CONSTRAINT FK_86CAE89E229E70A7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_implement DROP CONSTRAINT FK_86CAE89E687C4337
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_movement_execution_time_for_measure_unit DROP CONSTRAINT FK_D0D4113D229E70A7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_movement_execution_time_for_measure_unit DROP CONSTRAINT FK_D0D4113DEC4A7EC7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_cluster DROP CONSTRAINT FK_CAC7354D229E70A7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_cluster_implement DROP CONSTRAINT FK_EE9D826EB9324ECC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_cluster_implement DROP CONSTRAINT FK_EE9D826E687C4337
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workout DROP CONSTRAINT FK_649FFB7274781498
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workout_block DROP CONSTRAINT FK_DAD02436A6CCCFC9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workout_block DROP CONSTRAINT FK_DAD02436E9ED820C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE block
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE block_movement_cluster
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE body_part
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE implement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE implement_type_of_adjustable_measure_unit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE implement_type_of_adjustable_measure_unit_measure_unit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE measure_unit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE movement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE movement_body_part
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE movement_implement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE movement_movement_execution_time_for_measure_unit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE movement_cluster
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE movement_cluster_implement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE movement_execution_time_for_measure_unit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE workout
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE workout_block
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE workout_origin
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
