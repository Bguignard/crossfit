<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528194500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store imported competition participation metadata on workout results.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_result ADD division_source_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE workout_result ADD competition_rank VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE workout_result ADD competition_format VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE workout_result ADD competition_format_slug VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_result DROP division_source_id');
        $this->addSql('ALTER TABLE workout_result DROP competition_rank');
        $this->addSql('ALTER TABLE workout_result DROP competition_format');
        $this->addSql('ALTER TABLE workout_result DROP competition_format_slug');
    }
}
