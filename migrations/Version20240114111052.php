<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240114111052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE movement_implement (movement_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', implement_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_86CAE89E229E70A7 (movement_id), INDEX IDX_86CAE89E687C4337 (implement_id), PRIMARY KEY(movement_id, implement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE movement_implement ADD CONSTRAINT FK_86CAE89E229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement_implement ADD CONSTRAINT FK_86CAE89E687C4337 FOREIGN KEY (implement_id) REFERENCES implement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE implement_type_of_adjustable_measure_unit CHANGE implement_type_of_measure_enum implement_type_of_measure_enum VARCHAR(255) NOT NULL, CHANGE measure_unit_enum measure_unit_enum VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE movement_implement DROP FOREIGN KEY FK_86CAE89E229E70A7');
        $this->addSql('ALTER TABLE movement_implement DROP FOREIGN KEY FK_86CAE89E687C4337');
        $this->addSql('DROP TABLE movement_implement');
        $this->addSql('ALTER TABLE implement_type_of_adjustable_measure_unit CHANGE implement_type_of_measure_enum implement_type_of_measure_enum VARCHAR(255) DEFAULT NULL, CHANGE measure_unit_enum measure_unit_enum VARCHAR(255) DEFAULT NULL');
    }
}
