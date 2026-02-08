<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206122139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Utilisateur (id BIGINT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, date_naissance DATE DEFAULT NULL, est_actif TINYINT DEFAULT 1 NOT NULL, photo_profil VARCHAR(255) DEFAULT NULL, cree_le DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, role VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_9B80EC64E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE administrateur (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE medecin (id INT AUTO_INCREMENT NOT NULL, specialite VARCHAR(100) NOT NULL, document_pdf VARCHAR(255) DEFAULT NULL, disponibilite LONGTEXT DEFAULT NULL, annee_experience INT DEFAULT NULL, adresse_cabinet VARCHAR(255) DEFAULT NULL, grade VARCHAR(50) DEFAULT NULL, telephone_cabinet VARCHAR(20) DEFAULT NULL, nom_etablissement VARCHAR(150) DEFAULT NULL, numero_urgence VARCHAR(20) DEFAULT NULL, sexe VARCHAR(1) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE patient (id INT AUTO_INCREMENT NOT NULL, sexe VARCHAR(1) DEFAULT NULL, groupe_sanguin VARCHAR(10) DEFAULT NULL, contact_urgence VARCHAR(20) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE responsable_laboratoire (id INT AUTO_INCREMENT NOT NULL, laboratoire_id BIGINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE responsable_parapharmacie (parapharmacie_id BIGINT NOT NULL, id BIGINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE responsable_parapharmacie ADD CONSTRAINT FK_5AF73461BF396750 FOREIGN KEY (id) REFERENCES Utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE responsable_parapharmacie DROP FOREIGN KEY FK_5AF73461BF396750');
        $this->addSql('DROP TABLE Utilisateur');
        $this->addSql('DROP TABLE administrateur');
        $this->addSql('DROP TABLE medecin');
        $this->addSql('DROP TABLE patient');
        $this->addSql('DROP TABLE responsable_laboratoire');
        $this->addSql('DROP TABLE responsable_parapharmacie');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
