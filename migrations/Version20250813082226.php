<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250813082226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workout_type (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN workout_type.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE workout ADD workout_type_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE workout DROP workout_type');
        $this->addSql('COMMENT ON COLUMN workout.workout_type_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_649FFB72B98AE03B FOREIGN KEY (workout_type_id) REFERENCES workout_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_649FFB72B98AE03B ON workout (workout_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE workout DROP CONSTRAINT FK_649FFB72B98AE03B');
        $this->addSql('DROP TABLE workout_type');
        $this->addSql('DROP INDEX IDX_649FFB72B98AE03B');
        $this->addSql('ALTER TABLE workout ADD workout_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE workout DROP workout_type_id');
    }
}
