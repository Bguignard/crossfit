<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231127202125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE movement_detail (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', movement_intensity DOUBLE PRECISION DEFAULT NULL, movement_intensity_unit VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE movement_cluster ADD movement_detail_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', DROP movement_intensity');
        $this->addSql('ALTER TABLE movement_cluster ADD CONSTRAINT FK_CAC7354DEAF74F30 FOREIGN KEY (movement_detail_id) REFERENCES movement_detail (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CAC7354DEAF74F30 ON movement_cluster (movement_detail_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE movement_cluster DROP FOREIGN KEY FK_CAC7354DEAF74F30');
        $this->addSql('DROP TABLE movement_detail');
        $this->addSql('DROP INDEX UNIQ_CAC7354DEAF74F30 ON movement_cluster');
        $this->addSql('ALTER TABLE movement_cluster ADD movement_intensity DOUBLE PRECISION DEFAULT NULL, DROP movement_detail_id');
    }
}
