<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006090244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assignment (id INT AUTO_INCREMENT NOT NULL, equipment_id INT NOT NULL, store_id INT NOT NULL, created_by_id INT DEFAULT NULL, returned_by_id INT DEFAULT NULL, assigned_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', quantity INT NOT NULL, returned_quantity INT NOT NULL, returned_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_30C544BA517FE9FE (equipment_id), INDEX IDX_30C544BAB092A811 (store_id), INDEX IDX_30C544BAB03A8386 (created_by_id), INDEX IDX_30C544BA71AD87D9 (returned_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE audit (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(50) NOT NULL, entity_class VARCHAR(128) NOT NULL, entity_id INT NOT NULL, occurred_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_9218FF79A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_64C19C15E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE equipment (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, name VARCHAR(255) NOT NULL, reference VARCHAR(255) NOT NULL, state VARCHAR(50) NOT NULL, stock_quantity INT NOT NULL, deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_D338D583AEA34913 (reference), INDEX IDX_D338D58312469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE movement (id INT AUTO_INCREMENT NOT NULL, assignment_id INT DEFAULT NULL, equipment_id INT NOT NULL, store_id INT DEFAULT NULL, performed_by_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, quantity INT NOT NULL, occurred_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F4DD95F7D19302F8 (assignment_id), INDEX IDX_F4DD95F7517FE9FE (equipment_id), INDEX IDX_F4DD95F7B092A811 (store_id), INDEX IDX_F4DD95F72E65C292 (performed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE store (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, sr VARCHAR(255) DEFAULT NULL, code_fr VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) DEFAULT NULL, type_de_projet VARCHAR(255) DEFAULT NULL, date_ouverture DATE DEFAULT NULL, deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(100) NOT NULL, is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BA517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id)');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BAB092A811 FOREIGN KEY (store_id) REFERENCES store (id)');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BAB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BA71AD87D9 FOREIGN KEY (returned_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE audit ADD CONSTRAINT FK_9218FF79A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE equipment ADD CONSTRAINT FK_D338D58312469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F7D19302F8 FOREIGN KEY (assignment_id) REFERENCES assignment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F7517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id)');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F7B092A811 FOREIGN KEY (store_id) REFERENCES store (id)');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F72E65C292 FOREIGN KEY (performed_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BA517FE9FE');
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BAB092A811');
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BAB03A8386');
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BA71AD87D9');
        $this->addSql('ALTER TABLE audit DROP FOREIGN KEY FK_9218FF79A76ED395');
        $this->addSql('ALTER TABLE equipment DROP FOREIGN KEY FK_D338D58312469DE2');
        $this->addSql('ALTER TABLE movement DROP FOREIGN KEY FK_F4DD95F7D19302F8');
        $this->addSql('ALTER TABLE movement DROP FOREIGN KEY FK_F4DD95F7517FE9FE');
        $this->addSql('ALTER TABLE movement DROP FOREIGN KEY FK_F4DD95F7B092A811');
        $this->addSql('ALTER TABLE movement DROP FOREIGN KEY FK_F4DD95F72E65C292');
        $this->addSql('DROP TABLE assignment');
        $this->addSql('DROP TABLE audit');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE equipment');
        $this->addSql('DROP TABLE movement');
        $this->addSql('DROP TABLE store');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
