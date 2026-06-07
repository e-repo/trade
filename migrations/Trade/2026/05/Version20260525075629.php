<?php

declare(strict_types=1);

namespace DoctrineMigrations\Trade;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260525075629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE trade.bid (id UUID NOT NULL, lot_id UUID NOT NULL, contractor_id UUID NOT NULL, requested_volume INT NOT NULL, allocated_volume INT NOT NULL, price_per_ton INT NOT NULL, status VARCHAR(255) NOT NULL, rejection_reason TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_bid_lot_id ON trade.bid (lot_id)');
        $this->addSql('CREATE INDEX idx_bid_contractor_id ON trade.bid (contractor_id)');
        $this->addSql('CREATE INDEX idx_bid_lot_price_allocated ON trade.bid (lot_id, price_per_ton, allocated_volume)');
        $this->addSql('COMMENT ON COLUMN trade.bid.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN trade.bid.lot_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN trade.bid.contractor_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN trade.bid.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN trade.bid.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE trade.bid ADD CONSTRAINT FK_BF1B5056A8CBA5F7 FOREIGN KEY (lot_id) REFERENCES trade.lot (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE trade.bid ADD CONSTRAINT FK_BF1B5056B0265DC7 FOREIGN KEY (contractor_id) REFERENCES trade.contractor (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trade.bid DROP CONSTRAINT FK_BF1B5056A8CBA5F7');
        $this->addSql('ALTER TABLE trade.bid DROP CONSTRAINT FK_BF1B5056B0265DC7');
        $this->addSql('DROP TABLE trade.bid');
    }
}
