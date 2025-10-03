<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251003090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_active flag to user table';
    }

    public function up(Schema $schema): void
    {
        $user = $schema->getTable('user');
        if (!$user->hasColumn('is_active')) {
            $this->addSql('ALTER TABLE user ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('user')->hasColumn('is_active')) {
            $this->addSql('ALTER TABLE user DROP COLUMN is_active');
        }
    }
}