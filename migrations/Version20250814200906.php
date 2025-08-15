<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250814200906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE movement_difficulty (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN movement_difficulty.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE movement_type (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN movement_type.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE movement ADD difficulty_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE movement ADD movement_type_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE movement DROP difficulty');
        $this->addSql('ALTER TABLE movement DROP movement_type');
        $this->addSql('COMMENT ON COLUMN movement.difficulty_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN movement.movement_type_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F7FCFA9DAE FOREIGN KEY (difficulty_id) REFERENCES movement_difficulty (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F7EA4ED04A FOREIGN KEY (movement_type_id) REFERENCES movement_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_F4DD95F7FCFA9DAE ON movement (difficulty_id)');
        $this->addSql('CREATE INDEX IDX_F4DD95F7EA4ED04A ON movement (movement_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE movement DROP CONSTRAINT FK_F4DD95F7FCFA9DAE');
        $this->addSql('ALTER TABLE movement DROP CONSTRAINT FK_F4DD95F7EA4ED04A');
        $this->addSql('DROP TABLE movement_difficulty');
        $this->addSql('DROP TABLE movement_type');
        $this->addSql('DROP INDEX IDX_F4DD95F7FCFA9DAE');
        $this->addSql('DROP INDEX IDX_F4DD95F7EA4ED04A');
        $this->addSql('ALTER TABLE movement ADD difficulty INT NOT NULL');
        $this->addSql('ALTER TABLE movement ADD movement_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE movement DROP difficulty_id');
        $this->addSql('ALTER TABLE movement DROP movement_type_id');
    }
}
