<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241109101242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавление ограничения уникальности для module.category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_95C9EA705E237E06 ON module.category (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX module.UNIQ_95C9EA705E237E06');
    }
}
