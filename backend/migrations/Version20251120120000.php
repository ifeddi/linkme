<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251120120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_verified column to user table for email verification flag';
    }

    public function up(Schema $schema): void
    {
        // add the is_verified column with default 0 to avoid issues with existing rows
        $this->addSql("ALTER TABLE user ADD is_verified TINYINT(1) NOT NULL DEFAULT 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP is_verified');
    }
}

