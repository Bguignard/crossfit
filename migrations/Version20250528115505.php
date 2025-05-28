<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250528115505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE movement_muscle (movement_id UUID NOT NULL, muscle_id UUID NOT NULL, PRIMARY KEY(movement_id, muscle_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9E3147D3229E70A7 ON movement_muscle (movement_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9E3147D3354FDBB4 ON movement_muscle (muscle_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_muscle.movement_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_muscle.muscle_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE muscle_movement (muscle_id UUID NOT NULL, movement_id UUID NOT NULL, PRIMARY KEY(muscle_id, movement_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_EC94860354FDBB4 ON muscle_movement (muscle_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_EC94860229E70A7 ON muscle_movement (movement_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN muscle_movement.muscle_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN muscle_movement.movement_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_muscle ADD CONSTRAINT FK_9E3147D3229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_muscle ADD CONSTRAINT FK_9E3147D3354FDBB4 FOREIGN KEY (muscle_id) REFERENCES muscle (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE muscle_movement ADD CONSTRAINT FK_EC94860354FDBB4 FOREIGN KEY (muscle_id) REFERENCES muscle (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE muscle_movement ADD CONSTRAINT FK_EC94860229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_body_part DROP CONSTRAINT fk_2eac802f229e70a7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_body_part DROP CONSTRAINT fk_2eac802fa515f27a
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE movement_body_part
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE movement_body_part (movement_id UUID NOT NULL, body_part_id UUID NOT NULL, PRIMARY KEY(movement_id, body_part_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_2eac802fa515f27a ON movement_body_part (body_part_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_2eac802f229e70a7 ON movement_body_part (movement_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_body_part.movement_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN movement_body_part.body_part_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_body_part ADD CONSTRAINT fk_2eac802f229e70a7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_body_part ADD CONSTRAINT fk_2eac802fa515f27a FOREIGN KEY (body_part_id) REFERENCES body_part (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_muscle DROP CONSTRAINT FK_9E3147D3229E70A7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE movement_muscle DROP CONSTRAINT FK_9E3147D3354FDBB4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE muscle_movement DROP CONSTRAINT FK_EC94860354FDBB4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE muscle_movement DROP CONSTRAINT FK_EC94860229E70A7
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE movement_muscle
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE muscle_movement
        SQL);
    }
}
