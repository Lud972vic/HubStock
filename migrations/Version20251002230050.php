<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251002230050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow movements without assignment (make assignment_id nullable)';
    }

    public function up(Schema $schema): void
    {
        // MariaDB/MySQL: alter assignment_id to be nullable
        $this->addSql('ALTER TABLE movement MODIFY assignment_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert: make assignment_id NOT NULL (will fail if NULLs exist)
        $this->addSql('ALTER TABLE movement MODIFY assignment_id INT NOT NULL');
    }
}