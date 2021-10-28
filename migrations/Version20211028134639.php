<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211028134639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renames fields in maxStock => intMaxStock; maxPrice => decMaxPrice; minPrice => decMinPrice';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tblProductData CHANGE maxStock intMaxStock INT NOT NULL, CHANGE maxPrice decMaxPrice decimal(9,2) null, CHANGE minPrice decMinPrice decimal(9,2) null');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tblProductData CHANGE intMaxStock maxStock INT NOT NULL, CHANGE decMaxPrice maxPrice decimal(9,2) null, CHANGE decMinPrice minPrice decimal(9,2) null');
    }
}
