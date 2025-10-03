<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251002223500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Category entity, relate Equipment to Category, migrate existing data';
    }

    public function up(Schema $schema): void
    {
        // Create category table if not exists
        if (!$schema->hasTable('category')) {
            $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_64C19C15E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        $equipment = $schema->getTable('equipment');

        // Add category_id to equipment if not exists (nullable for migration)
        if (!$equipment->hasColumn('category_id')) {
            $this->addSql('ALTER TABLE equipment ADD category_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE equipment ADD CONSTRAINT FK_67B5A19D12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
            $this->addSql('CREATE INDEX IDX_67B5A19D12469DE2 ON equipment (category_id)');
        }

        // If old string column exists, migrate data and drop it
        if ($equipment->hasColumn('category')) {
            $this->addSql('INSERT IGNORE INTO category (name) SELECT DISTINCT category FROM equipment');
            $this->addSql('UPDATE equipment e JOIN category c ON c.name = e.category SET e.category_id = c.id');
            $this->addSql('ALTER TABLE equipment MODIFY category_id INT NOT NULL');
            $this->addSql('ALTER TABLE equipment DROP category');
        } else {
            // Ensure not null if already backfilled
            $this->addSql('ALTER TABLE equipment MODIFY category_id INT NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Recreate old column
        $this->addSql('ALTER TABLE equipment ADD category VARCHAR(255) NOT NULL');

        // Restore category names from relation
        $this->addSql('UPDATE equipment e JOIN category c ON e.category_id = c.id SET e.category = c.name');

        // Remove relation
        $this->addSql('ALTER TABLE equipment DROP FOREIGN KEY FK_67B5A19D12469DE2');
        $this->addSql('DROP INDEX IDX_67B5A19D12469DE2 ON equipment');
        $this->addSql('ALTER TABLE equipment DROP category_id');

        // Drop category table
        $this->addSql('DROP TABLE category');
    }
}