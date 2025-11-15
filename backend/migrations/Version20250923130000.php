<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250923130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create conversation and message tables for private chat system';
    }

    public function up(Schema $schema): void
    {
        // Créer la table conversation
        $this->addSql('CREATE TABLE conversation (
            id INT AUTO_INCREMENT NOT NULL,
            user1_id INT NOT NULL,
            user2_id INT NOT NULL,
            last_message_at DATETIME DEFAULT NULL,
            last_message_preview VARCHAR(160) DEFAULT NULL,
            unread_user1 INT DEFAULT 0 NOT NULL,
            unread_user2 INT DEFAULT 0 NOT NULL,
            INDEX IDX_8A8E26E9A76ED395 (user1_id),
            INDEX IDX_8A8E26E9A76ED396 (user2_id),
            UNIQUE INDEX unique_conversation (user1_id, user2_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Créer la table message
        $this->addSql('CREATE TABLE message (
            id INT AUTO_INCREMENT NOT NULL,
            conversation_id INT NOT NULL,
            sender_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            is_sticker TINYINT(1) DEFAULT 0 NOT NULL,
            sticker_code VARCHAR(64) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            read_at DATETIME DEFAULT NULL,
            INDEX IDX_B6BD307F9AC0396 (conversation_id),
            INDEX IDX_B6BD307FF624B39D (sender_id),
            INDEX IDX_message_created_at (created_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Ajouter la colonne last_seen_at à la table user
        $this->addSql('ALTER TABLE user ADD last_seen_at DATETIME DEFAULT NULL');

        // Ajouter les contraintes de clés étrangères
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9A76ED395 FOREIGN KEY (user1_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9A76ED396 FOREIGN KEY (user2_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9A76ED395');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9A76ED396');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F9AC0396');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE message');
        $this->addSql('ALTER TABLE user DROP last_seen_at');
    }
}
