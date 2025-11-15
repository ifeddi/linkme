<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250923120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status field to follow table for follow request system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE follow ADD status VARCHAR(20) DEFAULT \'pending\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE follow DROP status');
    }
}
