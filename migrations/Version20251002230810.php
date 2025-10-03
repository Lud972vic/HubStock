<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251002230810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make movement.store_id nullable to allow adjustments without store';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE movement MODIFY store_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE movement MODIFY store_id INT NOT NULL');
    }
}