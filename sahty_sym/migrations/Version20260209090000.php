<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260209090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create join table utilisateur_groupe to support ManyToMany between utilisateur and groupe_cible';
    }

    public function up(Schema $schema): void
    {
        // Create the join table if it does not exist. Do not add foreign keys to avoid FK compatibility issues.
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS utilisateur_groupe (
                utilisateur_id INT NOT NULL,
                groupe_cible_id INT NOT NULL,
                INDEX IDX_UTILISATEUR (utilisateur_id),
                INDEX IDX_GROUPE (groupe_cible_id),
                PRIMARY KEY (utilisateur_id, groupe_cible_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
        SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS utilisateur_groupe');
    }
}
