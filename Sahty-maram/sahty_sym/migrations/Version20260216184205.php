<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216184205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, text LONGTEXT NOT NULL, type VARCHAR(30) NOT NULL, category VARCHAR(100) DEFAULT NULL, order_in_quiz INT NOT NULL, reverse TINYINT NOT NULL, quiz_id INT NOT NULL, INDEX IDX_B6F7494E853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE quiz (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE recommandation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, tips LONGTEXT DEFAULT NULL, min_score INT NOT NULL, max_score INT NOT NULL, type_probleme VARCHAR(500) DEFAULT NULL, target_categories VARCHAR(255) DEFAULT NULL, severity VARCHAR(20) DEFAULT \'medium\' NOT NULL, created_at DATETIME NOT NULL, quiz_id INT NOT NULL, INDEX IDX_C7782A28853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE recommandation ADD CONSTRAINT FK_C7782A28853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE administrateur CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE administrateur ADD CONSTRAINT FK_32EB52E8BF396750 FOREIGN KEY (id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE medecin DROP sexe, CHANGE specialite specialite VARCHAR(100) DEFAULT NULL, CHANGE nom_etablissement nom_etablissement VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE patient CHANGE sexe sexe VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE responsable_laboratoire CHANGE laboratoire_id laboratoire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE responsable_parapharmacie CHANGE parapharmacie_id parapharmacie_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE recommandation DROP FOREIGN KEY FK_C7782A28853CD175');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE recommandation');
        $this->addSql('ALTER TABLE administrateur DROP FOREIGN KEY FK_32EB52E8BF396750');
        $this->addSql('ALTER TABLE administrateur CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE medecin ADD sexe VARCHAR(1) DEFAULT NULL, CHANGE specialite specialite VARCHAR(100) NOT NULL, CHANGE nom_etablissement nom_etablissement VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE patient CHANGE sexe sexe VARCHAR(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE responsable_laboratoire CHANGE laboratoire_id laboratoire_id INT NOT NULL');
        $this->addSql('ALTER TABLE responsable_parapharmacie CHANGE parapharmacie_id parapharmacie_id INT NOT NULL');
    }
}
