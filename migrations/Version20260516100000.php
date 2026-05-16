<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260516100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add accent-insensitive normalized athlete names.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE athlete ADD normalized_name VARCHAR(255) DEFAULT NULL');
        $this->addSql(<<<'SQL'
UPDATE athlete SET normalized_name = lower(regexp_replace(translate(display_name, 'ÀÁÂÃÄÅĀĂĄàáâãäåāăąÇĆĈĊČçćĉċčÐĎĐðďđÈÉÊËĒĔĖĘĚèéêëēĕėęěÌÍÎÏĨĪĬĮİìíîïĩīĭįıÑŃŅŇñńņňÒÓÔÕÖØŌŎŐòóôõöøōŏőÙÚÛÜŨŪŬŮŰŲùúûüũūŭůűųÝŸŶýÿŷŽŹŻžźż', 'AAAAAAAAAaaaaaaaaaCCCCCcccccDDDdddEEEEEEEEEeeeeeeeeeIIIIIIIIIiiiiiiiiiNNNNnnnnOOOOOOOOOoooooooooUUUUUUUUUUuuuuuuuuuuYYYyyyZZZzzz'), '[^a-z0-9]+', ' ', 'g'))
SQL);
        $this->addSql(<<<'SQL'
UPDATE athlete SET normalized_name = btrim(regexp_replace(normalized_name, '\s+', ' ', 'g'))
SQL);
        $this->addSql('CREATE INDEX IDX_ATHLETE_NORMALIZED_NAME ON athlete (normalized_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_ATHLETE_NORMALIZED_NAME');
        $this->addSql('ALTER TABLE athlete DROP normalized_name');
    }
}
