<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store workout generation stimulus metadata.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_generation ADD stimulus VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE workout_generation ADD stimulus_intent TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_generation DROP stimulus');
        $this->addSql('ALTER TABLE workout_generation DROP stimulus_intent');
    }
}
