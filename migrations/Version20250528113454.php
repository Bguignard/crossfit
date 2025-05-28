<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250528113454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE muscle (id UUID NOT NULL, body_part_id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F31119EFA515F27A ON muscle (body_part_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN muscle.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN muscle.body_part_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE workout_origin_name (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN workout_origin_name.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE muscle ADD CONSTRAINT FK_F31119EFA515F27A FOREIGN KEY (body_part_id) REFERENCES body_part (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workout_origin ADD name_id UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workout_origin DROP name
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN workout_origin.name_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workout_origin ADD CONSTRAINT FK_C47249D071179CD6 FOREIGN KEY (name_id) REFERENCES workout_origin_name (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C47249D071179CD6 ON workout_origin (name_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workout_origin DROP CONSTRAINT FK_C47249D071179CD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE muscle DROP CONSTRAINT FK_F31119EFA515F27A
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE muscle
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE workout_origin_name
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_C47249D071179CD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workout_origin ADD name VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workout_origin DROP name_id
        SQL);
    }
}
