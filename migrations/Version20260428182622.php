<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds a unique constraint on (date, start_time) for the booking table to
 * prevent double-bookings under concurrent requests.
 */
final class Version20260428182622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uniq_booking_date_start unique constraint on booking(date, start_time)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_booking_date_start ON booking (date, start_time)');
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $this->addSql('DROP INDEX uniq_booking_date_start');
        } else {
            $this->addSql('DROP INDEX uniq_booking_date_start ON booking');
        }
    }
}
