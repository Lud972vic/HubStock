<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251002235000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at (soft delete) to equipment, store, assignment, category';
    }

    public function up(Schema $schema): void
    {
        // equipment.deleted_at
        $equipment = $schema->getTable('equipment');
        if (!$equipment->hasColumn('deleted_at')) {
            $this->addSql('ALTER TABLE equipment ADD deleted_at DATETIME DEFAULT NULL');
        }

        // store.deleted_at
        $store = $schema->getTable('store');
        if (!$store->hasColumn('deleted_at')) {
            $this->addSql('ALTER TABLE store ADD deleted_at DATETIME DEFAULT NULL');
        }

        // assignment.deleted_at
        $assignment = $schema->getTable('assignment');
        if (!$assignment->hasColumn('deleted_at')) {
            $this->addSql('ALTER TABLE assignment ADD deleted_at DATETIME DEFAULT NULL');
        }

        // category.deleted_at
        $category = $schema->getTable('category');
        if (!$category->hasColumn('deleted_at')) {
            $this->addSql('ALTER TABLE category ADD deleted_at DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('equipment')->hasColumn('deleted_at')) {
            $this->addSql('ALTER TABLE equipment DROP COLUMN deleted_at');
        }
        if ($schema->getTable('store')->hasColumn('deleted_at')) {
            $this->addSql('ALTER TABLE store DROP COLUMN deleted_at');
        }
        if ($schema->getTable('assignment')->hasColumn('deleted_at')) {
            $this->addSql('ALTER TABLE assignment DROP COLUMN deleted_at');
        }
        if ($schema->getTable('category')->hasColumn('deleted_at')) {
            $this->addSql('ALTER TABLE category DROP COLUMN deleted_at');
        }
    }
}