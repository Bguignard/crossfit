<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613201000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store OpenAI usage metadata for generated workouts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout ADD ai_usage JSONB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout DROP ai_usage');
    }
}
