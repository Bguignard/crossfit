<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260515094500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add field size to imported workout results.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_result ADD field_size INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout_result DROP field_size');
    }
}
