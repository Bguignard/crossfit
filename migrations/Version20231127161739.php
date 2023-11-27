<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231127161739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE body_part CHANGE name name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE movement_cluster CHANGE movement_intensity movement_intensity DOUBLE PRECISION DEFAULT NULL, CHANGE rep_unit rep_unit VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE workout CHANGE number_of_rounds number_of_rounds INT DEFAULT NULL, CHANGE time_cap time_cap INT DEFAULT NULL, CHANGE workout_type workout_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE workout_origin ADD year INT DEFAULT NULL, CHANGE name name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE movement_cluster CHANGE movement_intensity movement_intensity DOUBLE PRECISION NOT NULL, CHANGE rep_unit rep_unit VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE workout CHANGE number_of_rounds number_of_rounds INT NOT NULL, CHANGE time_cap time_cap INT NOT NULL, CHANGE workout_type workout_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE body_part CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE workout_origin DROP year, CHANGE name name VARCHAR(255) NOT NULL');
    }
}
