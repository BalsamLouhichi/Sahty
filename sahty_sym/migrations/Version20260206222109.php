<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206222109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des tables fiche_medicale et rendez_vous avec relations';
    }

    public function up(Schema $schema): void
    {
        // Création des tables manquantes
        $this->addSql('CREATE TABLE fiche_medicale (
            id INT AUTO_INCREMENT NOT NULL,
            antecedents LONGTEXT DEFAULT NULL,
            allergies LONGTEXT DEFAULT NULL,
            traitement_en_cours LONGTEXT DEFAULT NULL,
            taille NUMERIC(5, 2) NOT NULL,
            poids NUMERIC(5, 2) NOT NULL,
            diagnostic LONGTEXT NOT NULL,
            traitement_prescrit LONGTEXT NOT NULL,
            observations LONGTEXT DEFAULT NULL,
            cree_le DATETIME NOT NULL,
            modifie_le DATETIME DEFAULT NULL,
            statut VARCHAR(50) NOT NULL,
            patient_id BIGINT UNSIGNED NOT NULL,
            INDEX IDX_20D23266B899279 (patient_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB');

        $this->addSql('CREATE TABLE rendez_vous (
            id INT AUTO_INCREMENT NOT NULL,
            date_rdv DATE NOT NULL,
            heure_rdv TIME NOT NULL,
            raison LONGTEXT DEFAULT NULL,
            statut VARCHAR(20) NOT NULL,
            cree_le DATETIME NOT NULL,
            date_validation DATETIME DEFAULT NULL,
            patient_id BIGINT UNSIGNED DEFAULT NULL,
            medecin_id BIGINT UNSIGNED NOT NULL,
            fiche_medicale_id INT NOT NULL,
            INDEX IDX_65E8AA0A6B899279 (patient_id),
            INDEX IDX_65E8AA0A4F31A84 (medecin_id),
            UNIQUE INDEX UNIQ_65E8AA0A9A99F4BC (fiche_medicale_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB');

        // Contraintes
        $this->addSql('ALTER TABLE fiche_medicale ADD CONSTRAINT FK_20D23266B899279 FOREIGN KEY (patient_id) REFERENCES patient (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A6B899279 FOREIGN KEY (patient_id) REFERENCES patient (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A4F31A84 FOREIGN KEY (medecin_id) REFERENCES medecin (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A9A99F4BC FOREIGN KEY (fiche_medicale_id) REFERENCES fiche_medicale (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fiche_medicale DROP FOREIGN KEY FK_20D23266B899279');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A6B899279');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A4F31A84');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A9A99F4BC');
        $this->addSql('DROP TABLE fiche_medicale');
        $this->addSql('DROP TABLE rendez_vous');
    }
}
