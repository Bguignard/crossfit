<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add coached clients and client programming ownership';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE coached_client (id UUID NOT NULL, coach_id UUID NOT NULL, display_name VARCHAR(255) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(64) DEFAULT NULL, notes VARCHAR(2048) DEFAULT NULL, performance_snapshot JSON NOT NULL, archived_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_513B1E863C105691 ON coached_client (coach_id)');
        $this->addSql('COMMENT ON COLUMN coached_client.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN coached_client.coach_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN coached_client.archived_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN coached_client.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN coached_client.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE coached_client ADD CONSTRAINT FK_COACHED_CLIENT_COACH FOREIGN KEY (coach_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE programming_generation_request ADD coached_client_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_FF8022AFE6B374A8 ON programming_generation_request (coached_client_id)');
        $this->addSql('COMMENT ON COLUMN programming_generation_request.coached_client_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE programming_generation_request ADD CONSTRAINT FK_PROGRAMMING_GENERATION_COACHED_CLIENT FOREIGN KEY (coached_client_id) REFERENCES coached_client (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE programming_generation_request DROP CONSTRAINT FK_PROGRAMMING_GENERATION_COACHED_CLIENT');
        $this->addSql('ALTER TABLE coached_client DROP CONSTRAINT FK_COACHED_CLIENT_COACH');
        $this->addSql('DROP INDEX IDX_FF8022AFE6B374A8');
        $this->addSql('ALTER TABLE programming_generation_request DROP coached_client_id');
        $this->addSql('DROP TABLE coached_client');
    }
}
