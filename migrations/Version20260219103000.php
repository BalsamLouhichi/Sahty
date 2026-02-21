<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs de consultation en ligne sur la table rendez_vous';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE rendez_vous ADD type_consultation VARCHAR(20) DEFAULT 'cabinet' NOT NULL, ADD meeting_url VARCHAR(500) DEFAULT NULL, ADD meeting_provider VARCHAR(50) DEFAULT NULL, ADD meeting_created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rendez_vous DROP type_consultation, DROP meeting_url, DROP meeting_provider, DROP meeting_created_at');
    }
}
