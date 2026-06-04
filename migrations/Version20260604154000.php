<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604154000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track personal AI request Messenger enqueue attempts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE performance_analysis_request ADD messenger_enqueued_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE programming_generation_request ADD messenger_enqueued_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN performance_analysis_request.messenger_enqueued_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN programming_generation_request.messenger_enqueued_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE performance_analysis_request DROP messenger_enqueued_at');
        $this->addSql('ALTER TABLE programming_generation_request DROP messenger_enqueued_at');
    }
}
