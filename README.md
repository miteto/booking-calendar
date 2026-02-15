# Calendar & Booking System

A multilingual booking system built with Symfony 8. It allows clients to book time slots and provides an administrative interface for managing slots, bookings, and site settings.

## Features

- **Public Booking Interface**: Clean and easy-to-use interface for clients to book available slots.
- **Admin Dashboard**:
    - **Booking Management**: View, edit, and delete client bookings.
    - **Slot & Availability Management**: Block entire days or toggle individual slots manually.
    - **Slot Configuration**: Define slot duration and working hours per day of the week.
    - **Slot Generation**: Automatically generate future time slots based on your configurations via browser or CLI.
    - **Site Settings**: Customize email templates and other site-wide settings.
- **Automated Notifications**: Send email reminders to clients before their scheduled appointments.
- **Embed Option**: Easily integrate the booking calendar into other websites using an iframe.
- **Multilingual Support**: Fully translated into English and Bulgarian.
- **Modern Tech Stack**: Built with Symfony 8, Doctrine ORM, Twig, and Symfony UX (Turbo/Stimulus).

## Requirements

- PHP 8.4+
- PostgreSQL / MySQL
- Composer

## Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone git@github.com:miteto/booking-calendar.git
   cd booking-calendar
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Database Setup:**
   Ensure you have a PostgreSQL or Mysql server running and update your `.env.local`.


4. **Configure Environment:**
   Copy the default environment file and adjust the settings:
   ```bash
   cp .env .env.local
   ```
   Edit `.env.local` to set your `DATABASE_URL`, `MAILER_DSN`, and other configuration variables.


5. **Run Migrations:**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

6. **Create an Admin User:**
   ```bash
   php bin/console app:create-user your-email@example.com your-password
   ```

7. **Generate Initial Slots:**
   ```bash
   php bin/console app:generate-slots --months=3
   ```

8. **Start the Web Server:**
   You can use the Symfony CLI:
   ```bash
   symfony serve
   ```
   Or use a local server like Apache or Nginx pointing to the `public/` directory.

## Administration

### Slot Management

Slots are the available time intervals that clients can book. They are managed through several interfaces:

- **Slot Configuration**: Define the default "blueprint" for your slots (start/end time, duration) for each day of the week or a global default.
- **Generation via Browser**: Navigate to **Admin -> Generate Slots** to create slots for a specific date range based on your configuration.
- **Manual Blocking**: In the **Admin -> Availability** section, you can:
    - Toggle individual slots to block/unblock them.
    - Block or unblock an entire day with a single click.
    - Blocked slots are not visible to clients.

### Mail Templates

You can customize the emails sent by the system in **Admin -> Settings**. There are templates for both Booking Confirmations and Reminders, available in English and Bulgarian.

**Available Placeholders:**
- `%name%`: Client's name.
- `%email%`: Client's email address.
- `%phone%`: Client's phone number.
- `%date%`: Date of the appointment (YYYY-MM-DD).
- `%time%`: Start time of the appointment (HH:MM).
- `%hours%`: Number of hours remaining until the appointment (used in **Reminders** only).

## Embed Option

To embed the booking calendar into another website (e.g., via an `<iframe>`), add the `embed=1` query parameter to the URL:

```text
https://your-domain.com/?embed=1
```

**What this does:**
- Removes the navigation header and footer.
- Applies a compact layout optimized for smaller containers.
- Sets a transparent background for better integration.

Example iframe code:
```html
<iframe src="https://your-domain.com/?embed=1" width="100%" height="600" frameborder="0"></iframe>
```

## Configuration (.env)

Key configuration variables in your `.env` or `.env.local`:

- `DATABASE_URL`: Connection string for PostgreSQL.
- `MAILER_DSN`: Configuration for sending emails.
- `MINIMUM_BOOKING_NOTICE`: Minimum minutes before a slot can be booked (default: 120).
- `NOTIFICATION_HOURS`: Comma-separated list of hours before an appointment to send a reminder (e.g., `2,12`).
- `APP_TIMEZONE`: The application's timezone (default: `Europe/Sofia`).

## CLI Commands

- `app:create-user <email> <password>`: Creates a new user with administrative privileges.
- `app:generate-slots [--months=N] [--from=YYYY-MM-DD]`: Generates time slots based on `SlotConfig`.
- `app:notify-bookings`: Checks for upcoming bookings and sends email reminders. This should be scheduled via a cron job (e.g., every 5 minutes).

## Running Tests

The project includes tests to ensure reliability. You can run them using PHPUnit:

```bash
php bin/phpunit
```
