<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206222607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Utilisateur (id BIGINT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, date_naissance DATE DEFAULT NULL, est_actif TINYINT DEFAULT 1 NOT NULL, photo_profil VARCHAR(255) DEFAULT NULL, cree_le DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, role VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_9B80EC64E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE administrateur (id BIGINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE fiche_medicale (id INT AUTO_INCREMENT NOT NULL, antecedents LONGTEXT DEFAULT NULL, allergies LONGTEXT DEFAULT NULL, traitement_en_cours LONGTEXT DEFAULT NULL, taille NUMERIC(5, 2) NOT NULL, poids NUMERIC(5, 2) NOT NULL, diagnostic LONGTEXT NOT NULL, traitement_prescrit LONGTEXT NOT NULL, observations LONGTEXT DEFAULT NULL, cree_le DATETIME NOT NULL, modifie_le DATETIME DEFAULT NULL, statut VARCHAR(50) NOT NULL, patient_id BIGINT NOT NULL, INDEX IDX_20D23266B899279 (patient_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE medecin (specialite VARCHAR(100) NOT NULL, document_pdf VARCHAR(255) DEFAULT NULL, disponibilite LONGTEXT DEFAULT NULL, annee_experience INT DEFAULT NULL, adresse_cabinet VARCHAR(255) DEFAULT NULL, grade VARCHAR(50) DEFAULT NULL, telephone_cabinet VARCHAR(20) DEFAULT NULL, nom_etablissement VARCHAR(150) DEFAULT NULL, numero_urgence VARCHAR(20) DEFAULT NULL, sexe VARCHAR(1) DEFAULT NULL, id BIGINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE patient (sexe VARCHAR(1) DEFAULT NULL, groupe_sanguin VARCHAR(10) DEFAULT NULL, contact_urgence VARCHAR(20) DEFAULT NULL, id BIGINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE rendez_vous (id INT AUTO_INCREMENT NOT NULL, date_rdv DATE NOT NULL, heure_rdv TIME NOT NULL, raison LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, cree_le DATETIME NOT NULL, date_validation DATETIME DEFAULT NULL, patient_id BIGINT DEFAULT NULL, medecin_id BIGINT NOT NULL, fiche_medicale_id INT NOT NULL, INDEX IDX_65E8AA0A6B899279 (patient_id), INDEX IDX_65E8AA0A4F31A84 (medecin_id), UNIQUE INDEX UNIQ_65E8AA0A9A99F4BC (fiche_medicale_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE responsable_laboratoire (laboratoire_id BIGINT NOT NULL, id BIGINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE responsable_parapharmacie (parapharmacie_id BIGINT NOT NULL, id BIGINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE administrateur ADD CONSTRAINT FK_32EB52E8BF396750 FOREIGN KEY (id) REFERENCES Utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fiche_medicale ADD CONSTRAINT FK_20D23266B899279 FOREIGN KEY (patient_id) REFERENCES patient (id)');
        $this->addSql('ALTER TABLE medecin ADD CONSTRAINT FK_1BDA53C6BF396750 FOREIGN KEY (id) REFERENCES Utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE patient ADD CONSTRAINT FK_1ADAD7EBBF396750 FOREIGN KEY (id) REFERENCES Utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A6B899279 FOREIGN KEY (patient_id) REFERENCES patient (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A4F31A84 FOREIGN KEY (medecin_id) REFERENCES medecin (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A9A99F4BC FOREIGN KEY (fiche_medicale_id) REFERENCES fiche_medicale (id)');
        $this->addSql('ALTER TABLE responsable_laboratoire ADD CONSTRAINT FK_C4592A30BF396750 FOREIGN KEY (id) REFERENCES Utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE responsable_parapharmacie ADD CONSTRAINT FK_5AF73461BF396750 FOREIGN KEY (id) REFERENCES Utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE administrateur DROP FOREIGN KEY FK_32EB52E8BF396750');
        $this->addSql('ALTER TABLE fiche_medicale DROP FOREIGN KEY FK_20D23266B899279');
        $this->addSql('ALTER TABLE medecin DROP FOREIGN KEY FK_1BDA53C6BF396750');
        $this->addSql('ALTER TABLE patient DROP FOREIGN KEY FK_1ADAD7EBBF396750');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A6B899279');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A4F31A84');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A9A99F4BC');
        $this->addSql('ALTER TABLE responsable_laboratoire DROP FOREIGN KEY FK_C4592A30BF396750');
        $this->addSql('ALTER TABLE responsable_parapharmacie DROP FOREIGN KEY FK_5AF73461BF396750');
        $this->addSql('DROP TABLE Utilisateur');
        $this->addSql('DROP TABLE administrateur');
        $this->addSql('DROP TABLE fiche_medicale');
        $this->addSql('DROP TABLE medecin');
        $this->addSql('DROP TABLE patient');
        $this->addSql('DROP TABLE rendez_vous');
        $this->addSql('DROP TABLE responsable_laboratoire');
        $this->addSql('DROP TABLE responsable_parapharmacie');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
