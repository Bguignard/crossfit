<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240113154220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE movement_cluster DROP FOREIGN KEY FK_CAC7354DEAF74F30');
        $this->addSql('CREATE TABLE implement_type_of_adjustable_measure_unit (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', implement_type_of_measure_enum VARCHAR(255) DEFAULT NULL, measure_unit_enum VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE movement_detail');
        $this->addSql('ALTER TABLE implement ADD implement_type_of_adjustable_measure VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_CAC7354DEAF74F30 ON movement_cluster');
        $this->addSql('ALTER TABLE movement_cluster ADD implement_intensity_adjustment_value DOUBLE PRECISION DEFAULT NULL, CHANGE movement_detail_id movement_detail_intensity_unit_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE movement_cluster ADD CONSTRAINT FK_CAC7354D7FD682F6 FOREIGN KEY (movement_detail_intensity_unit_id) REFERENCES implement_type_of_adjustable_measure_unit (id)');
        $this->addSql('CREATE INDEX IDX_CAC7354D7FD682F6 ON movement_cluster (movement_detail_intensity_unit_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE movement_cluster DROP FOREIGN KEY FK_CAC7354D7FD682F6');
        $this->addSql('CREATE TABLE movement_detail (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', movement_intensity DOUBLE PRECISION DEFAULT NULL, movement_intensity_unit VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE implement_type_of_adjustable_measure_unit');
        $this->addSql('ALTER TABLE implement DROP implement_type_of_adjustable_measure');
        $this->addSql('DROP INDEX IDX_CAC7354D7FD682F6 ON movement_cluster');
        $this->addSql('ALTER TABLE movement_cluster DROP implement_intensity_adjustment_value, CHANGE movement_detail_intensity_unit_id movement_detail_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE movement_cluster ADD CONSTRAINT FK_CAC7354DEAF74F30 FOREIGN KEY (movement_detail_id) REFERENCES movement_detail (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CAC7354DEAF74F30 ON movement_cluster (movement_detail_id)');
    }
}
