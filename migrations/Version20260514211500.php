<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260514211500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar URL to imported athletes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE athlete ADD avatar_url VARCHAR(2048) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE athlete DROP avatar_url');
    }
}
