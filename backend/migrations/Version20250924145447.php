<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924145447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_conversation ON conversation');
        $this->addSql('ALTER TABLE conversation CHANGE last_message_at last_message_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE conversation RENAME INDEX idx_8a8e26e9a76ed395 TO IDX_8A8E26E956AE248B');
        $this->addSql('ALTER TABLE conversation RENAME INDEX idx_8a8e26e9a76ed396 TO IDX_8A8E26E9441B8B65');
        $this->addSql('ALTER TABLE follow CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE status status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE message CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE read_at read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE message RENAME INDEX idx_message_created_at TO IDX_B6BD307F8B8E8428');
        $this->addSql('ALTER TABLE notification CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user CHANGE last_seen_at last_seen_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE follow CHANGE created_at created_at DATETIME NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'pending\' NOT NULL');
        $this->addSql('ALTER TABLE notification CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE message CHANGE created_at created_at DATETIME NOT NULL, CHANGE read_at read_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE message RENAME INDEX idx_b6bd307f8b8e8428 TO IDX_message_created_at');
        $this->addSql('ALTER TABLE user CHANGE last_seen_at last_seen_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE conversation CHANGE last_message_at last_message_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_conversation ON conversation (user1_id, user2_id)');
        $this->addSql('ALTER TABLE conversation RENAME INDEX idx_8a8e26e9441b8b65 TO IDX_8A8E26E9A76ED396');
        $this->addSql('ALTER TABLE conversation RENAME INDEX idx_8a8e26e956ae248b TO IDX_8A8E26E9A76ED395');
    }
}
