<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210105740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE administrateur CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE evenement ADD statut_demande VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E73A201E5 FOREIGN KEY (createur_id) REFERENCES utilisateur (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE inscription_evenement ADD CONSTRAINT FK_AD33AA06FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE inscription_evenement RENAME INDEX fk_ad33aa0638cc5579 TO IDX_AD33AA0638CC5579');
        $this->addSql('ALTER TABLE medecin CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE medecin ADD CONSTRAINT FK_1BDA53C6BF396750 FOREIGN KEY (id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE patient DROP FOREIGN KEY `FK_1ADAD7EBBF396750`');
        $this->addSql('ALTER TABLE patient CHANGE id id INT NOT NULL, CHANGE sexe sexe VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD CONSTRAINT FK_1ADAD7EBBF396750 FOREIGN KEY (id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE responsable_laboratoire DROP FOREIGN KEY `FK_C4592A30BF396750`');
        $this->addSql('ALTER TABLE responsable_laboratoire CHANGE id id INT NOT NULL, CHANGE laboratoire_id laboratoire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE responsable_laboratoire ADD CONSTRAINT FK_C4592A30BF396750 FOREIGN KEY (id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE responsable_parapharmacie DROP FOREIGN KEY `FK_5AF73461BF396750`');
        $this->addSql('ALTER TABLE responsable_parapharmacie CHANGE parapharmacie_id parapharmacie_id INT DEFAULT NULL, CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE responsable_parapharmacie ADD CONSTRAINT FK_5AF73461BF396750 FOREIGN KEY (id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur DROP roles, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE cree_le cree_le DATETIME NOT NULL, CHANGE role role VARCHAR(30) NOT NULL, CHANGE discr discr VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX uniq_9b80ec64e7927c74 TO UNIQ_EMAIL');
        $this->addSql('ALTER TABLE utilisateur_groupe ADD CONSTRAINT FK_6514B6AAFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_groupe ADD CONSTRAINT FK_6514B6AA38CC5579 FOREIGN KEY (groupe_cible_id) REFERENCES groupe_cible (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_groupe RENAME INDEX idx_utilisateur TO IDX_6514B6AAFB88E14F');
        $this->addSql('ALTER TABLE utilisateur_groupe RENAME INDEX idx_groupe TO IDX_6514B6AA38CC5579');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE administrateur CHANGE id id BIGINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681E73A201E5');
        $this->addSql('ALTER TABLE evenement DROP statut_demande');
        $this->addSql('ALTER TABLE inscription_evenement DROP FOREIGN KEY FK_AD33AA06FB88E14F');
        $this->addSql('ALTER TABLE inscription_evenement RENAME INDEX idx_ad33aa0638cc5579 TO FK_AD33AA0638CC5579');
        $this->addSql('ALTER TABLE medecin DROP FOREIGN KEY FK_1BDA53C6BF396750');
        $this->addSql('ALTER TABLE medecin CHANGE id id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE patient DROP FOREIGN KEY FK_1ADAD7EBBF396750');
        $this->addSql('ALTER TABLE patient CHANGE sexe sexe VARCHAR(1) DEFAULT NULL, CHANGE id id BIGINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE patient ADD CONSTRAINT `FK_1ADAD7EBBF396750` FOREIGN KEY (id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE responsable_laboratoire DROP FOREIGN KEY FK_C4592A30BF396750');
        $this->addSql('ALTER TABLE responsable_laboratoire CHANGE laboratoire_id laboratoire_id BIGINT NOT NULL, CHANGE id id BIGINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE responsable_laboratoire ADD CONSTRAINT `FK_C4592A30BF396750` FOREIGN KEY (id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE responsable_parapharmacie DROP FOREIGN KEY FK_5AF73461BF396750');
        $this->addSql('ALTER TABLE responsable_parapharmacie CHANGE parapharmacie_id parapharmacie_id BIGINT NOT NULL, CHANGE id id BIGINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE responsable_parapharmacie ADD CONSTRAINT `FK_5AF73461BF396750` FOREIGN KEY (id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE utilisateur ADD roles JSON NOT NULL, CHANGE id id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE cree_le cree_le DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE discr discr VARCHAR(255) DEFAULT \'user\' NOT NULL');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX uniq_email TO UNIQ_9B80EC64E7927C74');
        $this->addSql('ALTER TABLE utilisateur_groupe DROP FOREIGN KEY FK_6514B6AAFB88E14F');
        $this->addSql('ALTER TABLE utilisateur_groupe DROP FOREIGN KEY FK_6514B6AA38CC5579');
        $this->addSql('ALTER TABLE utilisateur_groupe RENAME INDEX idx_6514b6aa38cc5579 TO IDX_GROUPE');
        $this->addSql('ALTER TABLE utilisateur_groupe RENAME INDEX idx_6514b6aafb88e14f TO IDX_UTILISATEUR');
    }
}
