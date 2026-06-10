<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add competition geocoding cache';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE competition_geocoding_cache (id UUID NOT NULL, raw_location_hash VARCHAR(64) NOT NULL, raw_location VARCHAR(2048) NOT NULL, provider VARCHAR(64) NOT NULL, status VARCHAR(32) NOT NULL, country_name VARCHAR(128) DEFAULT NULL, country_code VARCHAR(2) DEFAULT NULL, region_name VARCHAR(128) DEFAULT NULL, department_name VARCHAR(128) DEFAULT NULL, city_name VARCHAR(128) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, confidence DOUBLE PRECISION DEFAULT NULL, error_message VARCHAR(512) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_COMPETITION_GEOCODING_CACHE_HASH ON competition_geocoding_cache (raw_location_hash)');
        $this->addSql('COMMENT ON COLUMN competition_geocoding_cache.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN competition_geocoding_cache.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN competition_geocoding_cache.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN competition_geocoding_cache.last_used_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE competition_geocoding_cache');
    }
}
