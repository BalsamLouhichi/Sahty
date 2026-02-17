<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216184123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, text LONGTEXT NOT NULL, type VARCHAR(30) NOT NULL, category VARCHAR(100) DEFAULT NULL, order_in_quiz INT NOT NULL, reverse TINYINT NOT NULL, quiz_id INT NOT NULL, INDEX IDX_B6F7494E853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz DROP questions, CHANGE name name VARCHAR(180) NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE recommandation ADD title VARCHAR(255) NOT NULL, ADD tips LONGTEXT DEFAULT NULL, ADD target_categories VARCHAR(255) DEFAULT NULL, ADD severity VARCHAR(20) DEFAULT \'medium\' NOT NULL, CHANGE type_probleme type_probleme VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('DROP TABLE question');
        $this->addSql('ALTER TABLE quiz ADD questions JSON NOT NULL, CHANGE name name VARCHAR(150) NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE recommandation DROP title, DROP tips, DROP target_categories, DROP severity, CHANGE type_probleme type_probleme VARCHAR(500) NOT NULL');
    }
}
