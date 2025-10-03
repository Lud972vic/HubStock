<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251002220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create movements table to record assignment additions and returns';
    }

    public function up(Schema $schema): void
    {
        // Create table
        $this->addSql('CREATE TABLE movement (
            id INT AUTO_INCREMENT NOT NULL,
            assignment_id INT NOT NULL,
            equipment_id INT NOT NULL,
            store_id INT NOT NULL,
            type VARCHAR(20) NOT NULL,
            quantity INT NOT NULL,
            occurred_at DATETIME NOT NULL,
            INDEX IDX_MOV_ASSIGNMENT (assignment_id),
            INDEX IDX_MOV_EQUIPMENT (equipment_id),
            INDEX IDX_MOV_STORE (store_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Foreign keys
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_MOV_ASSIGNMENT FOREIGN KEY (assignment_id) REFERENCES assignment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_MOV_EQUIPMENT FOREIGN KEY (equipment_id) REFERENCES equipment (id)');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_MOV_STORE FOREIGN KEY (store_id) REFERENCES store (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE movement');
    }
}