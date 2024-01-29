<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240128215210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE implement_type_of_adjustable_measure_unit_measure_unit (implement_type_of_adjustable_measure_unit_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', measure_unit_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_F7AFF469581D14F (implement_type_of_adjustable_measure_unit_id), INDEX IDX_F7AFF46963C6A475 (measure_unit_id), PRIMARY KEY(implement_type_of_adjustable_measure_unit_id, measure_unit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE measure_unit (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE implement_type_of_adjustable_measure_unit_measure_unit ADD CONSTRAINT FK_F7AFF469581D14F FOREIGN KEY (implement_type_of_adjustable_measure_unit_id) REFERENCES implement_type_of_adjustable_measure_unit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE implement_type_of_adjustable_measure_unit_measure_unit ADD CONSTRAINT FK_F7AFF46963C6A475 FOREIGN KEY (measure_unit_id) REFERENCES measure_unit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE implement_type_of_adjustable_measure_unit DROP measure_unit_enum');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE implement_type_of_adjustable_measure_unit_measure_unit DROP FOREIGN KEY FK_F7AFF469581D14F');
        $this->addSql('ALTER TABLE implement_type_of_adjustable_measure_unit_measure_unit DROP FOREIGN KEY FK_F7AFF46963C6A475');
        $this->addSql('DROP TABLE implement_type_of_adjustable_measure_unit_measure_unit');
        $this->addSql('DROP TABLE measure_unit');
        $this->addSql('ALTER TABLE implement_type_of_adjustable_measure_unit ADD measure_unit_enum VARCHAR(255) NOT NULL');
    }
}
