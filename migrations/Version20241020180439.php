<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241020180439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE channel ADD created_at DATETIME NOT NULL,
            ADD updated_at DATETIME NOT NULL,
            ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE tag ADD created_at DATETIME NOT NULL,
            ADD updated_at DATETIME NOT NULL,
            ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD created_at DATETIME NOT NULL,
            ADD updated_at DATETIME NOT NULL,
            ADD deleted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE channel DROP created_at,
            DROP updated_at,
            DROP deleted_at');
        $this->addSql('ALTER TABLE user DROP created_at,
            DROP updated_at,
            DROP deleted_at');
        $this->addSql('ALTER TABLE tag DROP created_at,
            DROP updated_at,
            DROP deleted_at');
    }
}
