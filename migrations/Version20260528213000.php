<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add structured geography fields to competitions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE competition ADD country_name VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD country_code VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD region_name VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD department_name VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD city_name VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD latitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD longitude DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE competition DROP country_name');
        $this->addSql('ALTER TABLE competition DROP country_code');
        $this->addSql('ALTER TABLE competition DROP region_name');
        $this->addSql('ALTER TABLE competition DROP department_name');
        $this->addSql('ALTER TABLE competition DROP city_name');
        $this->addSql('ALTER TABLE competition DROP latitude');
        $this->addSql('ALTER TABLE competition DROP longitude');
    }
}
