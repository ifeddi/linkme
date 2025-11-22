<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251122130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing user_id column, index, unique constraint and foreign key to `like` table';
    }

    public function up(Schema $schema): void
    {
        // Add the user_id column as nullable so the migration won't fail on existing rows.
        $this->addSql("ALTER TABLE `like` ADD user_id INT DEFAULT NULL");

        // Add index for user_id (matches original naming convention used elsewhere)
        $this->addSql("ALTER TABLE `like` ADD INDEX IDX_AC6340B3A76ED395 (user_id)");

        // Add the foreign key constraint referencing user(id). NULL values are allowed.
        $this->addSql("ALTER TABLE `like` ADD CONSTRAINT FK_AC6340B3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)");

        // Add a unique index on (user_id, post_id) to enforce one-like-per-user-per-post behaviour.
        // Using CREATE UNIQUE INDEX so it can be added after the table exists.
        $this->addSql("CREATE UNIQUE INDEX UNIQ_AC6340B3A76ED3954B89032C ON `like` (user_id, post_id)");
    }

    public function down(Schema $schema): void
    {
        // Remove the foreign key, unique index, index and column in reverse order.
        $this->addSql("ALTER TABLE `like` DROP FOREIGN KEY FK_AC6340B3A76ED395");
        $this->addSql("DROP INDEX UNIQ_AC6340B3A76ED3954B89032C ON `like`");
        $this->addSql("DROP INDEX IDX_AC6340B3A76ED395 ON `like`");
        $this->addSql("ALTER TABLE `like` DROP user_id");
    }
}

