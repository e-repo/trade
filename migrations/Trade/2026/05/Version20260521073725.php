<?php

declare(strict_types=1);

namespace DoctrineMigrations\Trade;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521073725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create lot table with volume, price, and termination embeddables, and performance indexes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE trade.lot (id UUID NOT NULL, cargo_type_id UUID NOT NULL, volume_step_id UUID NOT NULL, status VARCHAR(255) NOT NULL, opens_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, version INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, volume_total_volume INT NOT NULL, volume_reserved_volume INT NOT NULL, price_start_price INT NOT NULL, price_price_step INT NOT NULL, termination_closes_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, termination_close_reason VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FE68CABECD33D8BC ON trade.lot (cargo_type_id)');
        $this->addSql('CREATE INDEX IDX_FE68CABE188D3AC4 ON trade.lot (volume_step_id)');
        $this->addSql('CREATE INDEX idx_lot_status ON trade.lot (status)');
        $this->addSql('CREATE INDEX idx_lot_opens_at ON trade.lot (opens_at)');
        $this->addSql('CREATE INDEX idx_lot_closes_at ON trade.lot (termination_closes_at)');
        $this->addSql('COMMENT ON COLUMN trade.lot.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN trade.lot.cargo_type_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN trade.lot.volume_step_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN trade.lot.opens_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN trade.lot.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN trade.lot.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN trade.lot.termination_closes_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE trade.lot ADD CONSTRAINT FK_FE68CABECD33D8BC FOREIGN KEY (cargo_type_id) REFERENCES trade.cargo_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE trade.lot ADD CONSTRAINT FK_FE68CABE188D3AC4 FOREIGN KEY (volume_step_id) REFERENCES trade.volume_step (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA module');
        $this->addSql('ALTER TABLE trade.lot DROP CONSTRAINT FK_FE68CABECD33D8BC');
        $this->addSql('ALTER TABLE trade.lot DROP CONSTRAINT FK_FE68CABE188D3AC4');
        $this->addSql('DROP TABLE trade.lot');
    }
}
