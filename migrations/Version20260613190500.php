<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613190500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Throttle current session email deliveries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE programming_session_detail_request ADD session_email_sent_at_by_key JSON DEFAULT NULL');
        $this->addSql('UPDATE programming_session_detail_request SET session_email_sent_at_by_key = \'{}\' WHERE session_email_sent_at_by_key IS NULL');
        $this->addSql('ALTER TABLE programming_session_detail_request ALTER session_email_sent_at_by_key SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE programming_session_detail_request DROP session_email_sent_at_by_key');
    }
}
