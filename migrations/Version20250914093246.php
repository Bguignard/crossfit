<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250914093246 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workout_generation (id UUID NOT NULL, workout_type_id UUID DEFAULT NULL, movement_difficulty_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, time_cap INT NOT NULL, number_of_different_movements INT NOT NULL, movement_generation_type VARCHAR(255) DEFAULT NULL, intervals_time INT DEFAULT NULL, intervals_rest_time INT DEFAULT NULL, number_of_rounds INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BF0223B1B98AE03B ON workout_generation (workout_type_id)');
        $this->addSql('CREATE INDEX IDX_BF0223B1AD303BD5 ON workout_generation (movement_difficulty_id)');
        $this->addSql('COMMENT ON COLUMN workout_generation.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_generation.workout_type_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_generation.movement_difficulty_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE workout_generation_movement_type (workout_generation_id UUID NOT NULL, movement_type_id UUID NOT NULL, PRIMARY KEY(workout_generation_id, movement_type_id))');
        $this->addSql('CREATE INDEX IDX_60F601DCF27A47E5 ON workout_generation_movement_type (workout_generation_id)');
        $this->addSql('CREATE INDEX IDX_60F601DCEA4ED04A ON workout_generation_movement_type (movement_type_id)');
        $this->addSql('COMMENT ON COLUMN workout_generation_movement_type.workout_generation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_generation_movement_type.movement_type_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE workout_generation_banned_movements (workout_generation_id UUID NOT NULL, movement_id UUID NOT NULL, PRIMARY KEY(workout_generation_id, movement_id))');
        $this->addSql('CREATE INDEX IDX_995C8C33F27A47E5 ON workout_generation_banned_movements (workout_generation_id)');
        $this->addSql('CREATE INDEX IDX_995C8C33229E70A7 ON workout_generation_banned_movements (movement_id)');
        $this->addSql('COMMENT ON COLUMN workout_generation_banned_movements.workout_generation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_generation_banned_movements.movement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE workout_generation_implement (workout_generation_id UUID NOT NULL, implement_id UUID NOT NULL, PRIMARY KEY(workout_generation_id, implement_id))');
        $this->addSql('CREATE INDEX IDX_B721635DF27A47E5 ON workout_generation_implement (workout_generation_id)');
        $this->addSql('CREATE INDEX IDX_B721635D687C4337 ON workout_generation_implement (implement_id)');
        $this->addSql('COMMENT ON COLUMN workout_generation_implement.workout_generation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_generation_implement.implement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE workout_generation_body_part (workout_generation_id UUID NOT NULL, body_part_id UUID NOT NULL, PRIMARY KEY(workout_generation_id, body_part_id))');
        $this->addSql('CREATE INDEX IDX_1F470BECF27A47E5 ON workout_generation_body_part (workout_generation_id)');
        $this->addSql('CREATE INDEX IDX_1F470BECA515F27A ON workout_generation_body_part (body_part_id)');
        $this->addSql('COMMENT ON COLUMN workout_generation_body_part.workout_generation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_generation_body_part.body_part_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE workout_generation_mandatory_movements (workout_generation_id UUID NOT NULL, movement_id UUID NOT NULL, PRIMARY KEY(workout_generation_id, movement_id))');
        $this->addSql('CREATE INDEX IDX_E603D01CF27A47E5 ON workout_generation_mandatory_movements (workout_generation_id)');
        $this->addSql('CREATE INDEX IDX_E603D01C229E70A7 ON workout_generation_mandatory_movements (movement_id)');
        $this->addSql('COMMENT ON COLUMN workout_generation_mandatory_movements.workout_generation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_generation_mandatory_movements.movement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE workout_generation ADD CONSTRAINT FK_BF0223B1B98AE03B FOREIGN KEY (workout_type_id) REFERENCES workout_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_generation ADD CONSTRAINT FK_BF0223B1AD303BD5 FOREIGN KEY (movement_difficulty_id) REFERENCES movement_difficulty (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_generation_movement_type ADD CONSTRAINT FK_60F601DCF27A47E5 FOREIGN KEY (workout_generation_id) REFERENCES workout_generation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_generation_movement_type ADD CONSTRAINT FK_60F601DCEA4ED04A FOREIGN KEY (movement_type_id) REFERENCES movement_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_generation_banned_movements ADD CONSTRAINT FK_995C8C33F27A47E5 FOREIGN KEY (workout_generation_id) REFERENCES workout_generation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_generation_banned_movements ADD CONSTRAINT FK_995C8C33229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_generation_implement ADD CONSTRAINT FK_B721635DF27A47E5 FOREIGN KEY (workout_generation_id) REFERENCES workout_generation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_generation_implement ADD CONSTRAINT FK_B721635D687C4337 FOREIGN KEY (implement_id) REFERENCES implement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_generation_body_part ADD CONSTRAINT FK_1F470BECF27A47E5 FOREIGN KEY (workout_generation_id) REFERENCES workout_generation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_generation_body_part ADD CONSTRAINT FK_1F470BECA515F27A FOREIGN KEY (body_part_id) REFERENCES body_part (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_generation_mandatory_movements ADD CONSTRAINT FK_E603D01CF27A47E5 FOREIGN KEY (workout_generation_id) REFERENCES workout_generation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_generation_mandatory_movements ADD CONSTRAINT FK_E603D01C229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE workout_generation DROP CONSTRAINT FK_BF0223B1B98AE03B');
        $this->addSql('ALTER TABLE workout_generation DROP CONSTRAINT FK_BF0223B1AD303BD5');
        $this->addSql('ALTER TABLE workout_generation_movement_type DROP CONSTRAINT FK_60F601DCF27A47E5');
        $this->addSql('ALTER TABLE workout_generation_movement_type DROP CONSTRAINT FK_60F601DCEA4ED04A');
        $this->addSql('ALTER TABLE workout_generation_banned_movements DROP CONSTRAINT FK_995C8C33F27A47E5');
        $this->addSql('ALTER TABLE workout_generation_banned_movements DROP CONSTRAINT FK_995C8C33229E70A7');
        $this->addSql('ALTER TABLE workout_generation_implement DROP CONSTRAINT FK_B721635DF27A47E5');
        $this->addSql('ALTER TABLE workout_generation_implement DROP CONSTRAINT FK_B721635D687C4337');
        $this->addSql('ALTER TABLE workout_generation_body_part DROP CONSTRAINT FK_1F470BECF27A47E5');
        $this->addSql('ALTER TABLE workout_generation_body_part DROP CONSTRAINT FK_1F470BECA515F27A');
        $this->addSql('ALTER TABLE workout_generation_mandatory_movements DROP CONSTRAINT FK_E603D01CF27A47E5');
        $this->addSql('ALTER TABLE workout_generation_mandatory_movements DROP CONSTRAINT FK_E603D01C229E70A7');
        $this->addSql('DROP TABLE workout_generation');
        $this->addSql('DROP TABLE workout_generation_movement_type');
        $this->addSql('DROP TABLE workout_generation_banned_movements');
        $this->addSql('DROP TABLE workout_generation_implement');
        $this->addSql('DROP TABLE workout_generation_body_part');
        $this->addSql('DROP TABLE workout_generation_mandatory_movements');
    }
}
