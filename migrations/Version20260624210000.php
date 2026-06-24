<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link programming generation requests to their source performance analysis request.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE programming_generation_request ADD source_analysis_request_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_FF8022AF93BD0EA6 ON programming_generation_request (source_analysis_request_id)');
        $this->addSql('COMMENT ON COLUMN programming_generation_request.source_analysis_request_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE programming_generation_request ADD CONSTRAINT FK_PROGRAMMING_GENERATION_SOURCE_ANALYSIS FOREIGN KEY (source_analysis_request_id) REFERENCES performance_analysis_request (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE programming_generation_request DROP CONSTRAINT FK_PROGRAMMING_GENERATION_SOURCE_ANALYSIS');
        $this->addSql('DROP INDEX IDX_FF8022AF93BD0EA6');
        $this->addSql('ALTER TABLE programming_generation_request DROP source_analysis_request_id');
    }
}
