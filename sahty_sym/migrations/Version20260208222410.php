<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208222410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix inheritance foreign key constraints';
    }

    public function up(Schema $schema): void
    {
        // This migration has been consolidated into Version20260208215122
        // No additional changes needed
    }

    public function down(Schema $schema): void
    {
        // This migration has been consolidated into Version20260208215122
        // No changes to revert
    }
}