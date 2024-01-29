<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240128102620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE movement_movement_execution_time_for_measure_unit (movement_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', movement_execution_time_for_measure_unit_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_D0D4113D229E70A7 (movement_id), INDEX IDX_D0D4113DEC4A7EC7 (movement_execution_time_for_measure_unit_id), PRIMARY KEY(movement_id, movement_execution_time_for_measure_unit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE movement_execution_time_for_measure_unit (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', measure_unit VARCHAR(255) NOT NULL, execution_time_in_milliseconds INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE movement_movement_execution_time_for_measure_unit ADD CONSTRAINT FK_D0D4113D229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement_movement_execution_time_for_measure_unit ADD CONSTRAINT FK_D0D4113DEC4A7EC7 FOREIGN KEY (movement_execution_time_for_measure_unit_id) REFERENCES movement_execution_time_for_measure_unit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement DROP execution_speed');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE movement_movement_execution_time_for_measure_unit DROP FOREIGN KEY FK_D0D4113D229E70A7');
        $this->addSql('ALTER TABLE movement_movement_execution_time_for_measure_unit DROP FOREIGN KEY FK_D0D4113DEC4A7EC7');
        $this->addSql('DROP TABLE movement_movement_execution_time_for_measure_unit');
        $this->addSql('DROP TABLE movement_execution_time_for_measure_unit');
        $this->addSql('ALTER TABLE movement ADD execution_speed INT NOT NULL');
    }
}
