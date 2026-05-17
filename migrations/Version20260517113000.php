<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add discovery metadata to competitions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE competition ADD status VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD starts_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD ends_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD registration_url VARCHAR(2048) DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD location_label VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD is_online BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD competition_type VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD participation_type VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD cover_image_url VARCHAR(2048) DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD price_label VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD metadata JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE competition ADD last_discovered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN competition.starts_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN competition.ends_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN competition.last_discovered_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE competition DROP status');
        $this->addSql('ALTER TABLE competition DROP starts_at');
        $this->addSql('ALTER TABLE competition DROP ends_at');
        $this->addSql('ALTER TABLE competition DROP registration_url');
        $this->addSql('ALTER TABLE competition DROP location_label');
        $this->addSql('ALTER TABLE competition DROP is_online');
        $this->addSql('ALTER TABLE competition DROP competition_type');
        $this->addSql('ALTER TABLE competition DROP participation_type');
        $this->addSql('ALTER TABLE competition DROP cover_image_url');
        $this->addSql('ALTER TABLE competition DROP price_label');
        $this->addSql('ALTER TABLE competition DROP metadata');
        $this->addSql('ALTER TABLE competition DROP last_discovered_at');
    }
}
