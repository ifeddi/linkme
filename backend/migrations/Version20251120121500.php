<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251120121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add verification_token and reset password columns to user table';
    }

    public function up(Schema $schema): void
    {
        // add nullable token columns used by the authentication flows
        $this->addSql("ALTER TABLE user ADD verification_token VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE user ADD reset_password_token VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE user ADD reset_password_expires_at DATETIME DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP verification_token');
        $this->addSql('ALTER TABLE user DROP reset_password_token');
        $this->addSql('ALTER TABLE user DROP reset_password_expires_at');
    }
}

