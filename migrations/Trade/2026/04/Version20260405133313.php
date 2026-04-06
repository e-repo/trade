<?php

declare(strict_types=1);

namespace DoctrineMigrations\Trade;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405133313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Trade module schema and reference tables: CargoType, Contractor, VolumeStep';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA trade');
        $this->addSql('CREATE SCHEMA module');
        $this->addSql('CREATE TABLE trade.cargo_type (id UUID NOT NULL, name VARCHAR(100) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CD621D1A5E237E06 ON trade.cargo_type (name)');
        $this->addSql('COMMENT ON COLUMN trade.cargo_type.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN trade.cargo_type.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN trade.cargo_type.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE trade.contractor (id UUID NOT NULL, email VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, second_name VARCHAR(100) NOT NULL, patronymic VARCHAR(100) DEFAULT NULL, agreement_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_52440431E7927C74 ON trade.contractor (email)');
        $this->addSql('COMMENT ON COLUMN trade.contractor.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN trade.contractor.agreement_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN trade.contractor.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN trade.contractor.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE trade.volume_step (id UUID NOT NULL, name VARCHAR(50) NOT NULL, value INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88791165E237E06 ON trade.volume_step (name)');
        $this->addSql('COMMENT ON COLUMN trade.volume_step.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN trade.volume_step.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE trade.cargo_type');
        $this->addSql('DROP TABLE module.category');
        $this->addSql('DROP TABLE trade.contractor');
        $this->addSql('DROP TABLE trade.volume_step');
    }
}
