<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208215122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // STEP 1: First change all column types to ensure compatibility
        $this->addSql('ALTER TABLE utilisateur DROP roles, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE cree_le cree_le DATETIME NOT NULL, CHANGE role role VARCHAR(30) NOT NULL, CHANGE discr discr VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX uniq_9b80ec64e7927c74 TO UNIQ_EMAIL');
        $this->addSql('ALTER TABLE administrateur CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE patient CHANGE id id INT NOT NULL, CHANGE sexe sexe VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE responsable_laboratoire CHANGE id id INT NOT NULL, CHANGE laboratoire_id laboratoire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE responsable_parapharmacie CHANGE parapharmacie_id parapharmacie_id INT DEFAULT NULL, CHANGE id id INT NOT NULL');
        
        // STEP 2: Remove old foreign key first
        $this->addSql('ALTER TABLE medecin DROP FOREIGN KEY `FK_Medecin_Utilisateur`');
        $this->addSql('DROP INDEX FK_Medecin_Utilisateur ON medecin');
        $this->addSql('ALTER TABLE medecin DROP utilisateur_id');
        
        // STEP 3: Now create all foreign keys with compatible column types
        $this->addSql('ALTER TABLE administrateur ADD CONSTRAINT FK_32EB52E8BF396750 FOREIGN KEY (id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE medecin ADD CONSTRAINT FK_1BDA53C6BF396750 FOREIGN KEY (id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E73A201E5 FOREIGN KEY (createur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE inscription_evenement ADD groupe_cible_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE inscription_evenement ADD CONSTRAINT FK_AD33AA06FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE inscription_evenement ADD CONSTRAINT FK_AD33AA0638CC5579 FOREIGN KEY (groupe_cible_id) REFERENCES groupe_cible (id)');
        $this->addSql('CREATE INDEX IDX_AD33AA0638CC5579 ON inscription_evenement (groupe_cible_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE administrateur DROP FOREIGN KEY FK_32EB52E8BF396750');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681E73A201E5');
        $this->addSql('ALTER TABLE inscription_evenement DROP FOREIGN KEY FK_AD33AA06FB88E14F');
        $this->addSql('ALTER TABLE inscription_evenement DROP FOREIGN KEY FK_AD33AA0638CC5579');
        $this->addSql('DROP INDEX IDX_AD33AA0638CC5579 ON inscription_evenement');
        $this->addSql('ALTER TABLE inscription_evenement DROP groupe_cible_id');
        $this->addSql('ALTER TABLE medecin DROP FOREIGN KEY FK_1BDA53C6BF396750');
        $this->addSql('ALTER TABLE medecin ADD utilisateur_id BIGINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE medecin ADD CONSTRAINT `FK_Medecin_Utilisateur` FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX FK_Medecin_Utilisateur ON medecin (utilisateur_id)');
        $this->addSql('ALTER TABLE patient CHANGE sexe sexe VARCHAR(1) DEFAULT NULL, CHANGE id id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE responsable_laboratoire CHANGE laboratoire_id laboratoire_id BIGINT NOT NULL, CHANGE id id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE responsable_parapharmacie CHANGE parapharmacie_id parapharmacie_id BIGINT NOT NULL, CHANGE id id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD roles JSON NOT NULL, CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE cree_le cree_le DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE discr discr VARCHAR(255) DEFAULT \'user\' NOT NULL');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX uniq_email TO UNIQ_9B80EC64E7927C74');
    }
}
