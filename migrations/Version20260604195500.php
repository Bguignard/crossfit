<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604195500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add detailed programming session generation requests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE programming_session_detail_request (id UUID NOT NULL, user_id UUID NOT NULL, programming_request_id UUID NOT NULL, status VARCHAR(32) NOT NULL, input_snapshot JSON NOT NULL, detailed_programming JSON DEFAULT NULL, current_session_index INT NOT NULL, completed_session_keys JSON NOT NULL, error_message TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, queued_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, messenger_enqueued_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7D103921A76ED395 ON programming_session_detail_request (user_id)');
        $this->addSql('CREATE INDEX IDX_7D1039218B0DE1D0 ON programming_session_detail_request (programming_request_id)');
        $this->addSql('COMMENT ON COLUMN programming_session_detail_request.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN programming_session_detail_request.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN programming_session_detail_request.programming_request_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN programming_session_detail_request.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN programming_session_detail_request.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN programming_session_detail_request.queued_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN programming_session_detail_request.started_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN programming_session_detail_request.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN programming_session_detail_request.messenger_enqueued_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE programming_session_detail_request ADD CONSTRAINT FK_PROGRAMMING_SESSION_DETAIL_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE programming_session_detail_request ADD CONSTRAINT FK_PROGRAMMING_SESSION_DETAIL_PROGRAMMING FOREIGN KEY (programming_request_id) REFERENCES programming_generation_request (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE programming_session_detail_request');
    }
}
