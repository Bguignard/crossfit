<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240129072350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE implement ADD implement_type_of_adjustable_measure_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', DROP implement_type_of_adjustable_measure');
        $this->addSql('ALTER TABLE implement ADD CONSTRAINT FK_B4EDA4154B3CB714 FOREIGN KEY (implement_type_of_adjustable_measure_id) REFERENCES implement_type_of_adjustable_measure_unit (id)');
        $this->addSql('CREATE INDEX IDX_B4EDA4154B3CB714 ON implement (implement_type_of_adjustable_measure_id)');
        $this->addSql('ALTER TABLE movement_cluster DROP FOREIGN KEY FK_CAC7354D7FD682F6');
        $this->addSql('DROP INDEX IDX_CAC7354D7FD682F6 ON movement_cluster');
        $this->addSql('ALTER TABLE movement_cluster ADD movement_detail_intensity_unit VARCHAR(255) DEFAULT NULL, DROP movement_detail_intensity_unit_id, CHANGE rep_unit rep_unit VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE implement DROP FOREIGN KEY FK_B4EDA4154B3CB714');
        $this->addSql('DROP INDEX IDX_B4EDA4154B3CB714 ON implement');
        $this->addSql('ALTER TABLE implement ADD implement_type_of_adjustable_measure VARCHAR(255) DEFAULT NULL, DROP implement_type_of_adjustable_measure_id');
        $this->addSql('ALTER TABLE movement_cluster ADD movement_detail_intensity_unit_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', DROP movement_detail_intensity_unit, CHANGE rep_unit rep_unit VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE movement_cluster ADD CONSTRAINT FK_CAC7354D7FD682F6 FOREIGN KEY (movement_detail_intensity_unit_id) REFERENCES implement_type_of_adjustable_measure_unit (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_CAC7354D7FD682F6 ON movement_cluster (movement_detail_intensity_unit_id)');
    }
}
