<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251002233500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performed_by to movement; created_by and returned_by to assignment; create audit table';
    }

    public function up(Schema $schema): void
    {
        // movement.performed_by_id
        $movement = $schema->getTable('movement');
        if (!$movement->hasColumn('performed_by_id')) {
            $this->addSql('ALTER TABLE movement ADD performed_by_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_MOVEMENT_PERFORMED_BY FOREIGN KEY (performed_by_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_MOVEMENT_PERFORMED_BY ON movement (performed_by_id)');
        }

        // assignment.created_by_id, returned_by_id
        $assignment = $schema->getTable('assignment');
        if (!$assignment->hasColumn('created_by_id')) {
            $this->addSql('ALTER TABLE assignment ADD created_by_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_ASSIGNMENT_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_ASSIGNMENT_CREATED_BY ON assignment (created_by_id)');
        }
        if (!$assignment->hasColumn('returned_by_id')) {
            $this->addSql('ALTER TABLE assignment ADD returned_by_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_ASSIGNMENT_RETURNED_BY FOREIGN KEY (returned_by_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_ASSIGNMENT_RETURNED_BY ON assignment (returned_by_id)');
        }

        // audit table
        if (!$schema->hasTable('audit')) {
            $this->addSql('CREATE TABLE audit (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(50) NOT NULL, entity_class VARCHAR(128) NOT NULL, entity_id INT NOT NULL, occurred_at DATETIME NOT NULL, INDEX IDX_AUDIT_USER (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE audit ADD CONSTRAINT FK_AUDIT_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE movement DROP CONSTRAINT FK_MOVEMENT_PERFORMED_BY');
        $this->addSql('ALTER TABLE movement DROP COLUMN performed_by_id');
        $this->addSql('ALTER TABLE assignment DROP CONSTRAINT FK_ASSIGNMENT_CREATED_BY');
        $this->addSql('ALTER TABLE assignment DROP CONSTRAINT FK_ASSIGNMENT_RETURNED_BY');
        $this->addSql('ALTER TABLE assignment DROP COLUMN created_by_id');
        $this->addSql('ALTER TABLE assignment DROP COLUMN returned_by_id');
        $this->addSql('DROP TABLE audit');
    }
}