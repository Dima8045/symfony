<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211008100812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter tblProductData: add intMaxStock, decMinPrice, decMaxPrice fields';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tblProductData ADD (intMaxStock INT, decMinPrice decimal(9,2) null, decMaxPrice decimal(9,2) null)');
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tblProductData DROP COLUMN intMaxStock');
        $this->addSql('ALTER TABLE tblProductData DROP COLUMN decMaxPrice');
        $this->addSql('ALTER TABLE tblProductData DROP COLUMN decMaxPrice');
    }
}
