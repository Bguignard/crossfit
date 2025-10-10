<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251008162124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE simple_workout ADD workout_generation_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN simple_workout.workout_generation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE simple_workout ADD CONSTRAINT FK_2ED6E589F27A47E5 FOREIGN KEY (workout_generation_id) REFERENCES workout_generation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2ED6E589F27A47E5 ON simple_workout (workout_generation_id)');
        $this->addSql('ALTER TABLE workout_generation DROP CONSTRAINT fk_bf0223b12b763f96');
        $this->addSql('DROP INDEX uniq_bf0223b12b763f96');
        $this->addSql('ALTER TABLE workout_generation DROP generated_workout_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE workout_generation ADD generated_workout_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN workout_generation.generated_workout_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE workout_generation ADD CONSTRAINT fk_bf0223b12b763f96 FOREIGN KEY (generated_workout_id) REFERENCES simple_workout (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_bf0223b12b763f96 ON workout_generation (generated_workout_id)');
        $this->addSql('ALTER TABLE simple_workout DROP CONSTRAINT FK_2ED6E589F27A47E5');
        $this->addSql('DROP INDEX UNIQ_2ED6E589F27A47E5');
        $this->addSql('ALTER TABLE simple_workout DROP workout_generation_id');
    }
}
