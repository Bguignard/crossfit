<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509234500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product identity tables for users, boxes, and box memberships.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_user (id UUID NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, display_name VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_APP_USER_EMAIL ON app_user (email)');
        $this->addSql('COMMENT ON COLUMN app_user.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN app_user.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN app_user.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE box (id UUID NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN box.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN box.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN box.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE box_membership (id UUID NOT NULL, user_id UUID NOT NULL, box_id UUID NOT NULL, role VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_39FE5933A76ED395 ON box_membership (user_id)');
        $this->addSql('CREATE INDEX IDX_39FE5933D8177B3F ON box_membership (box_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BOX_MEMBERSHIP_USER_BOX ON box_membership (user_id, box_id)');
        $this->addSql('COMMENT ON COLUMN box_membership.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN box_membership.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN box_membership.box_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN box_membership.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE box_membership ADD CONSTRAINT FK_BOX_MEMBERSHIP_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE box_membership ADD CONSTRAINT FK_BOX_MEMBERSHIP_BOX FOREIGN KEY (box_id) REFERENCES box (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE box_membership DROP CONSTRAINT FK_BOX_MEMBERSHIP_USER');
        $this->addSql('ALTER TABLE box_membership DROP CONSTRAINT FK_BOX_MEMBERSHIP_BOX');
        $this->addSql('DROP TABLE box_membership');
        $this->addSql('DROP TABLE box');
        $this->addSql('DROP TABLE app_user');
    }
}
