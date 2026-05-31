<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds a per-booking cancellation token so users can cancel via a non-guessable link.
 */
final class Version20260528163941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add booking.cancellation_token (unique, nullable) for cancellation links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD cancellation_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_booking_cancellation_token ON booking (cancellation_token)');
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $this->addSql('DROP INDEX uniq_booking_cancellation_token');
        } else {
            $this->addSql('DROP INDEX uniq_booking_cancellation_token ON booking');
        }
        $this->addSql('ALTER TABLE booking DROP cancellation_token');
    }
}
