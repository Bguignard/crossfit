<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510191000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user athlete profile links.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_athlete_profile (id UUID NOT NULL, user_id UUID NOT NULL, athlete_id UUID NOT NULL, link_type VARCHAR(32) NOT NULL, primary_profile BOOLEAN NOT NULL, verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EC49AAEFA76ED395 ON user_athlete_profile (user_id)');
        $this->addSql('CREATE INDEX IDX_EC49AAEFFE6BCB8B ON user_athlete_profile (athlete_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_ATHLETE_PROFILE_USER_ATHLETE ON user_athlete_profile (user_id, athlete_id)');
        $this->addSql('COMMENT ON COLUMN user_athlete_profile.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_athlete_profile.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_athlete_profile.athlete_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_athlete_profile.verified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_athlete_profile.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_athlete_profile.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_athlete_profile ADD CONSTRAINT FK_USER_ATHLETE_PROFILE_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_athlete_profile ADD CONSTRAINT FK_USER_ATHLETE_PROFILE_ATHLETE FOREIGN KEY (athlete_id) REFERENCES athlete (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_athlete_profile DROP CONSTRAINT FK_USER_ATHLETE_PROFILE_USER');
        $this->addSql('ALTER TABLE user_athlete_profile DROP CONSTRAINT FK_USER_ATHLETE_PROFILE_ATHLETE');
        $this->addSql('DROP TABLE user_athlete_profile');
    }
}
