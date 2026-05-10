<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop legacy duplicate muscle movement join table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE muscle_movement DROP CONSTRAINT FK_EC94860354FDBB4');
        $this->addSql('ALTER TABLE muscle_movement DROP CONSTRAINT FK_EC94860229E70A7');
        $this->addSql('DROP TABLE muscle_movement');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE muscle_movement (muscle_id UUID NOT NULL, movement_id UUID NOT NULL, PRIMARY KEY(muscle_id, movement_id))');
        $this->addSql('CREATE INDEX IDX_EC94860354FDBB4 ON muscle_movement (muscle_id)');
        $this->addSql('CREATE INDEX IDX_EC94860229E70A7 ON muscle_movement (movement_id)');
        $this->addSql('COMMENT ON COLUMN muscle_movement.muscle_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN muscle_movement.movement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE muscle_movement ADD CONSTRAINT FK_EC94860354FDBB4 FOREIGN KEY (muscle_id) REFERENCES muscle (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE muscle_movement ADD CONSTRAINT FK_EC94860229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
