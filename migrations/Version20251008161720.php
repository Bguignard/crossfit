<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251008161720 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workout_generation ADD generated_workout_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN workout_generation.generated_workout_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE workout_generation ADD CONSTRAINT FK_BF0223B12B763F96 FOREIGN KEY (generated_workout_id) REFERENCES simple_workout (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BF0223B12B763F96 ON workout_generation (generated_workout_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE workout_generation DROP CONSTRAINT FK_BF0223B12B763F96');
        $this->addSql('DROP INDEX UNIQ_BF0223B12B763F96');
        $this->addSql('ALTER TABLE workout_generation DROP generated_workout_id');
    }
}
