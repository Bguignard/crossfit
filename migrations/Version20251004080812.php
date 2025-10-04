<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251004080812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workout_movement_generation_type (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN workout_movement_generation_type.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE workout_generation ADD movement_generation_type_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE workout_generation ADD is_team_workout BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE workout_generation DROP movement_generation_type');
        $this->addSql('COMMENT ON COLUMN workout_generation.movement_generation_type_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE workout_generation ADD CONSTRAINT FK_BF0223B1E38F0F72 FOREIGN KEY (movement_generation_type_id) REFERENCES workout_movement_generation_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_BF0223B1E38F0F72 ON workout_generation (movement_generation_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE workout_generation DROP CONSTRAINT FK_BF0223B1E38F0F72');
        $this->addSql('DROP TABLE workout_movement_generation_type');
        $this->addSql('DROP INDEX IDX_BF0223B1E38F0F72');
        $this->addSql('ALTER TABLE workout_generation ADD movement_generation_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE workout_generation DROP movement_generation_type_id');
        $this->addSql('ALTER TABLE workout_generation DROP is_team_workout');
    }
}
