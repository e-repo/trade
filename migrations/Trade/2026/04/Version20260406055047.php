<?php

declare(strict_types=1);

namespace DoctrineMigrations\Trade;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seed reference data for Trade module: CargoType, VolumeStep, Contractor
 */
final class Version20260406055047 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed reference data: 1 CargoType, 1 VolumeStep (25 tons), 20 Contractors';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("SET search_path TO trade");

        // Seed CargoType: one cargo type
        $this->addSql("
            INSERT INTO cargo_type (id, name, created_at) VALUES
            ('550e8400-e29b-41d4-a716-446655440001', 'Семена подсолнечника', NOW())
        ");

        // Seed VolumeStep: 25 tons step
        $this->addSql("
            INSERT INTO volume_step (id, name, value, created_at) VALUES
            ('550e8400-e29b-41d4-a716-446655440010', '25 тонн', 25, NOW())
        ");

        // Seed Contractor: 20 test contractors
        $this->addSql("
            INSERT INTO contractor (id, email, first_name, second_name, patronymic, agreement_id, created_at) VALUES
            ('550e8400-e29b-41d4-a716-446655440020', 'contractor1@example.com', 'Иван', 'Иванов', 'Иванович', '550e8400-e29b-41d4-a716-446655440100', NOW()),
            ('550e8400-e29b-41d4-a716-446655440021', 'contractor2@example.com', 'Пётр', 'Петров', 'Петрович', '550e8400-e29b-41d4-a716-446655440101', NOW()),
            ('550e8400-e29b-41d4-a716-446655440022', 'contractor3@example.com', 'Сергей', 'Сергеев', 'Сергеевич', '550e8400-e29b-41d4-a716-446655440102', NOW()),
            ('550e8400-e29b-41d4-a716-446655440023', 'contractor4@example.com', 'Алексей', 'Алексеев', 'Алексеевич', '550e8400-e29b-41d4-a716-446655440103', NOW()),
            ('550e8400-e29b-41d4-a716-446655440024', 'contractor5@example.com', 'Дмитрий', 'Дмитриев', 'Дмитриевич', '550e8400-e29b-41d4-a716-446655440104', NOW()),
            ('550e8400-e29b-41d4-a716-446655440025', 'contractor6@example.com', 'Михаил', 'Михайлов', 'Михайлович', '550e8400-e29b-41d4-a716-446655440105', NOW()),
            ('550e8400-e29b-41d4-a716-446655440026', 'contractor7@example.com', 'Андрей', 'Андреев', 'Андреевич', '550e8400-e29b-41d4-a716-446655440106', NOW()),
            ('550e8400-e29b-41d4-a716-446655440027', 'contractor8@example.com', 'Владимир', 'Владимиров', 'Владимирович', '550e8400-e29b-41d4-a716-446655440107', NOW()),
            ('550e8400-e29b-41d4-a716-446655440028', 'contractor9@example.com', 'Николай', 'Николаев', 'Николаевич', '550e8400-e29b-41d4-a716-446655440108', NOW()),
            ('550e8400-e29b-41d4-a716-446655440029', 'contractor10@example.com', 'Александр', 'Александров', 'Александрович', '550e8400-e29b-41d4-a716-446655440109', NOW()),
            ('550e8400-e29b-41d4-a716-446655440030', 'contractor11@example.com', 'Евгений', 'Евгеньев', 'Евгеньевич', '550e8400-e29b-41d4-a716-446655440110', NOW()),
            ('550e8400-e29b-41d4-a716-446655440031', 'contractor12@example.com', 'Константин', 'Константинов', 'Константинович', '550e8400-e29b-41d4-a716-446655440111', NOW()),
            ('550e8400-e29b-41d4-a716-446655440032', 'contractor13@example.com', 'Максим', 'Максимов', 'Максимович', '550e8400-e29b-41d4-a716-446655440112', NOW()),
            ('550e8400-e29b-41d4-a716-446655440033', 'contractor14@example.com', 'Роман', 'Романов', 'Романович', '550e8400-e29b-41d4-a716-446655440113', NOW()),
            ('550e8400-e29b-41d4-a716-446655440034', 'contractor15@example.com', 'Виктор', 'Викторов', 'Викторович', '550e8400-e29b-41d4-a716-446655440114', NOW()),
            ('550e8400-e29b-41d4-a716-446655440035', 'contractor16@example.com', 'Артём', 'Артёмов', 'Артёмович', '550e8400-e29b-41d4-a716-446655440115', NOW()),
            ('550e8400-e29b-41d4-a716-446655440036', 'contractor17@example.com', 'Игорь', 'Игорев', 'Игоревич', '550e8400-e29b-41d4-a716-446655440116', NOW()),
            ('550e8400-e29b-41d4-a716-446655440037', 'contractor18@example.com', 'Олег', 'Олегов', 'Олегович', '550e8400-e29b-41d4-a716-446655440117', NOW()),
            ('550e8400-e29b-41d4-a716-446655440038', 'contractor19@example.com', 'Павел', 'Павлов', 'Павлович', '550e8400-e29b-41d4-a716-446655440118', NOW()),
            ('550e8400-e29b-41d4-a716-446655440039', 'contractor20@example.com', 'Станислав', 'Станиславов', 'Станиславович', '550e8400-e29b-41d4-a716-446655440119', NOW())
        ");

        // Reset search_path to public for doctrine_migration_versions table
        $this->addSql("SET search_path TO public");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("SET search_path TO trade");

        $this->addSql("DELETE FROM contractor WHERE id IN (
            '550e8400-e29b-41d4-a716-446655440020',
            '550e8400-e29b-41d4-a716-446655440021',
            '550e8400-e29b-41d4-a716-446655440022',
            '550e8400-e29b-41d4-a716-446655440023',
            '550e8400-e29b-41d4-a716-446655440024',
            '550e8400-e29b-41d4-a716-446655440025',
            '550e8400-e29b-41d4-a716-446655440026',
            '550e8400-e29b-41d4-a716-446655440027',
            '550e8400-e29b-41d4-a716-446655440028',
            '550e8400-e29b-41d4-a716-446655440029',
            '550e8400-e29b-41d4-a716-446655440030',
            '550e8400-e29b-41d4-a716-446655440031',
            '550e8400-e29b-41d4-a716-446655440032',
            '550e8400-e29b-41d4-a716-446655440033',
            '550e8400-e29b-41d4-a716-446655440034',
            '550e8400-e29b-41d4-a716-446655440035',
            '550e8400-e29b-41d4-a716-446655440036',
            '550e8400-e29b-41d4-a716-446655440037',
            '550e8400-e29b-41d4-a716-446655440038',
            '550e8400-e29b-41d4-a716-446655440039'
        )");

        $this->addSql("DELETE FROM volume_step WHERE id = '550e8400-e29b-41d4-a716-446655440010'");
        $this->addSql("DELETE FROM cargo_type WHERE id = '550e8400-e29b-41d4-a716-446655440001'");
    }
}
