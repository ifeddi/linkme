<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour changer le stockage des images en Base64
 */
final class Version20250923112700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change image storage from URLs to Base64 for User and Post entities';
    }

    public function up(Schema $schema): void
    {
        // Modifier la colonne media_url en media pour les posts
        $this->addSql('ALTER TABLE post CHANGE media_url media LONGTEXT DEFAULT NULL');
        
        // Modifier la colonne profile_photo_url en profile_photo pour les users
        $this->addSql('ALTER TABLE user CHANGE profile_photo_url profile_photo LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revenir aux URLs
        $this->addSql('ALTER TABLE post CHANGE media media_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE profile_photo profile_photo_url VARCHAR(255) DEFAULT NULL');
    }
}
