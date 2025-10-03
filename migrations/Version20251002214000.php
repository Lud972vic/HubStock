<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251002214000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique index on equipment.reference';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipment ADD UNIQUE INDEX UNIQ_EQUIPMENT_REFERENCE (reference)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_EQUIPMENT_REFERENCE ON equipment');
    }
}