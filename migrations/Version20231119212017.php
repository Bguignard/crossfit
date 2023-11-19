<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231119212017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE block (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', rounds INT NOT NULL, rest_time INT DEFAULT NULL, order_in_workout INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE block_movement_cluster (block_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', movement_cluster_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_BB3E3A53E9ED820C (block_id), INDEX IDX_BB3E3A53B9324ECC (movement_cluster_id), PRIMARY KEY(block_id, movement_cluster_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE body_part (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE implement (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE movement (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, difficulty INT NOT NULL, movement_type VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE movement_body_part (movement_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', body_part_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_2EAC802F229E70A7 (movement_id), INDEX IDX_2EAC802FA515F27A (body_part_id), PRIMARY KEY(movement_id, body_part_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE movement_cluster (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', movement_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', repetitions INT NOT NULL, movement_intensity DOUBLE PRECISION NOT NULL, rep_unit VARCHAR(255) NOT NULL, INDEX IDX_CAC7354D229E70A7 (movement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE movement_cluster_implement (movement_cluster_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', implement_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_EE9D826EB9324ECC (movement_cluster_id), INDEX IDX_EE9D826E687C4337 (implement_id), PRIMARY KEY(movement_cluster_id, implement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workout (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', workout_origin_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, number_of_rounds INT NOT NULL, time_cap INT NOT NULL, workout_type VARCHAR(255) NOT NULL, INDEX IDX_649FFB7274781498 (workout_origin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workout_block (workout_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', block_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_DAD02436A6CCCFC9 (workout_id), INDEX IDX_DAD02436E9ED820C (block_id), PRIMARY KEY(workout_id, block_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workout_origin (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE block_movement_cluster ADD CONSTRAINT FK_BB3E3A53E9ED820C FOREIGN KEY (block_id) REFERENCES block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE block_movement_cluster ADD CONSTRAINT FK_BB3E3A53B9324ECC FOREIGN KEY (movement_cluster_id) REFERENCES movement_cluster (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement_body_part ADD CONSTRAINT FK_2EAC802F229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement_body_part ADD CONSTRAINT FK_2EAC802FA515F27A FOREIGN KEY (body_part_id) REFERENCES body_part (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement_cluster ADD CONSTRAINT FK_CAC7354D229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id)');
        $this->addSql('ALTER TABLE movement_cluster_implement ADD CONSTRAINT FK_EE9D826EB9324ECC FOREIGN KEY (movement_cluster_id) REFERENCES movement_cluster (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement_cluster_implement ADD CONSTRAINT FK_EE9D826E687C4337 FOREIGN KEY (implement_id) REFERENCES implement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_649FFB7274781498 FOREIGN KEY (workout_origin_id) REFERENCES workout_origin (id)');
        $this->addSql('ALTER TABLE workout_block ADD CONSTRAINT FK_DAD02436A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workout_block ADD CONSTRAINT FK_DAD02436E9ED820C FOREIGN KEY (block_id) REFERENCES block (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE block_movement_cluster DROP FOREIGN KEY FK_BB3E3A53E9ED820C');
        $this->addSql('ALTER TABLE block_movement_cluster DROP FOREIGN KEY FK_BB3E3A53B9324ECC');
        $this->addSql('ALTER TABLE movement_body_part DROP FOREIGN KEY FK_2EAC802F229E70A7');
        $this->addSql('ALTER TABLE movement_body_part DROP FOREIGN KEY FK_2EAC802FA515F27A');
        $this->addSql('ALTER TABLE movement_cluster DROP FOREIGN KEY FK_CAC7354D229E70A7');
        $this->addSql('ALTER TABLE movement_cluster_implement DROP FOREIGN KEY FK_EE9D826EB9324ECC');
        $this->addSql('ALTER TABLE movement_cluster_implement DROP FOREIGN KEY FK_EE9D826E687C4337');
        $this->addSql('ALTER TABLE workout DROP FOREIGN KEY FK_649FFB7274781498');
        $this->addSql('ALTER TABLE workout_block DROP FOREIGN KEY FK_DAD02436A6CCCFC9');
        $this->addSql('ALTER TABLE workout_block DROP FOREIGN KEY FK_DAD02436E9ED820C');
        $this->addSql('DROP TABLE block');
        $this->addSql('DROP TABLE block_movement_cluster');
        $this->addSql('DROP TABLE body_part');
        $this->addSql('DROP TABLE implement');
        $this->addSql('DROP TABLE movement');
        $this->addSql('DROP TABLE movement_body_part');
        $this->addSql('DROP TABLE movement_cluster');
        $this->addSql('DROP TABLE movement_cluster_implement');
        $this->addSql('DROP TABLE workout');
        $this->addSql('DROP TABLE workout_block');
        $this->addSql('DROP TABLE workout_origin');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
