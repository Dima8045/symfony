<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211001094633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tblProductData table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            CREATE TABLE tblProductData (
              intProductDataId int(10) unsigned NOT NULL AUTO_INCREMENT,
              strProductName varchar(50) NOT NULL,
              strProductDesc varchar(255) NOT NULL,
              strProductCode varchar(10) NOT NULL,
              dtmAdded datetime DEFAULT NULL,
              dtmDiscontinued datetime DEFAULT NULL,
              stmTimestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (intProductDataId),
              UNIQUE KEY (strProductCode)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores product data'
        ");

    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE tblProductData');
    }
}
