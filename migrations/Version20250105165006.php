<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250105165006 extends AbstractMigration
{
    public function isTransactional(): bool
    {
        return false;
    }
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE channel (id INT AUTO_INCREMENT NOT NULL,
            channel_tag VARCHAR(60) NOT NULL,
            channel_id VARCHAR(255) NOT NULL,
            name VARCHAR(60) NOT NULL,
            is_own TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            deleted_at DATETIME DEFAULT NULL,
            PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE channel_tags (channel_id INT NOT NULL,
            tag_id INT NOT NULL,
            INDEX IDX_F3E8375872F5A1AA (channel_id),
            INDEX IDX_F3E83758BAD26311 (tag_id),
            PRIMARY KEY(channel_id,
            tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            deleted_at DATETIME DEFAULT NULL,
            PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL,
            user_id VARCHAR(255) NOT NULL,
            username VARCHAR(40) NOT NULL,
            roles JSON NOT NULL,
            waiting_for_message VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            deleted_at DATETIME DEFAULT NULL,
            UNIQUE INDEX UNIQ_8D93D649F85E0677 (username),
            PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user_channels (user_id INT NOT NULL,
            channel_id INT NOT NULL,
            INDEX IDX_139906B6A76ED395 (user_id),
            INDEX IDX_139906B672F5A1AA (channel_id),
            PRIMARY KEY(user_id,
            channel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE channel_tags ADD CONSTRAINT FK_F3E8375872F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE channel_tags ADD CONSTRAINT FK_F3E83758BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_channels ADD CONSTRAINT FK_139906B6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_channels ADD CONSTRAINT FK_139906B672F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE channel_tags DROP FOREIGN KEY FK_F3E8375872F5A1AA');
        $this->addSql('ALTER TABLE channel_tags DROP FOREIGN KEY FK_F3E83758BAD26311');
        $this->addSql('ALTER TABLE user_channels DROP FOREIGN KEY FK_139906B6A76ED395');
        $this->addSql('ALTER TABLE user_channels DROP FOREIGN KEY FK_139906B672F5A1AA');
        $this->addSql('DROP TABLE channel');
        $this->addSql('DROP TABLE channel_tags');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_channels');
    }
}
