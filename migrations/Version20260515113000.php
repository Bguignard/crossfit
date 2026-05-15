<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260515113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add latest elite Games ranking fields to athletes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE athlete ADD elite_games_rank INT DEFAULT NULL');
        $this->addSql('ALTER TABLE athlete ADD elite_games_season INT DEFAULT NULL');
        $this->addSql('ALTER TABLE athlete ADD elite_games_sort_score INT DEFAULT 2147483647 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE athlete DROP elite_games_rank');
        $this->addSql('ALTER TABLE athlete DROP elite_games_season');
        $this->addSql('ALTER TABLE athlete DROP elite_games_sort_score');
    }
}
