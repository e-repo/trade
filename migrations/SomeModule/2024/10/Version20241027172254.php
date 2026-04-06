<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241027172254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создание схемы module и таблицы module.category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS module');

        $this->addSql('CREATE TABLE module.category (
            id UUID NOT NULL,
            name VARCHAR(50) NOT NULL,
            description VARCHAR(255) NOT NULL,
            created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('COMMENT ON COLUMN module.category.id IS \'Код категории(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN module.category.name IS \'Наименование категории\'');
        $this->addSql('COMMENT ON COLUMN module.category.description IS \'Описание категории\'');
        $this->addSql('COMMENT ON COLUMN module.category.created_at IS \'Дата создания категории(DC2Type:datetimetz_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE module.category');
        $this->addSql('DROP SCHEMA IF EXISTS module CASCADE');
    }
}
