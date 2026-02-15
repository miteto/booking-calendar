<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211122450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if ($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE booking ALTER user_name DROP NOT NULL');
            $this->addSql('ALTER TABLE booking ALTER user_email DROP NOT NULL');
            $this->addSql('ALTER TABLE booking ALTER user_phone DROP NOT NULL');
        } else {
            $this->addSql('ALTER TABLE booking MODIFY user_name VARCHAR(255) DEFAULT NULL');
            $this->addSql('ALTER TABLE booking MODIFY user_email VARCHAR(255) DEFAULT NULL');
            $this->addSql('ALTER TABLE booking MODIFY user_phone VARCHAR(20) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        if ($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE booking ALTER user_name SET NOT NULL');
            $this->addSql('ALTER TABLE booking ALTER user_email SET NOT NULL');
            $this->addSql('ALTER TABLE booking ALTER user_phone SET NOT NULL');
        } else {
            $this->addSql('ALTER TABLE booking MODIFY user_name VARCHAR(255) NOT NULL');
            $this->addSql('ALTER TABLE booking MODIFY user_email VARCHAR(255) NOT NULL');
            $this->addSql('ALTER TABLE booking MODIFY user_phone VARCHAR(20) NOT NULL');
        }
    }
}
