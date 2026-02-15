<?php

namespace App\Tests;

use App\Entity\Booking;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminBookingFilterTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
    }

    private function createAdmin(): User
    {
        $user = new User();
        $user->setEmail('admin_test@example.com');
        $user->setRoles(['ROLE_ADMIN']);

        $hasher = self::getContainer()->get('security.user_password_hasher');
        $user->setPassword($hasher->hashPassword($user, 'password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createBooking(string $date, string $name, string $email, string $phone): Booking
    {
        $booking = new Booking();
        $booking->setDate(new \DateTime($date));
        $booking->setStartTime(new \DateTime('10:00:00'));
        $booking->setEndTime(new \DateTime('11:00:00'));
        $booking->setUserName($name);
        $booking->setUserEmail($email);
        $booking->setUserPhone($phone);
        $booking->setLocale('en');

        $this->entityManager->persist($booking);
        return $booking;
    }

    public function testFiltersAndPagination(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Booking')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();

        // Setup data
        $this->createAdmin();
        $this->createBooking('2025-01-01', 'John Doe', 'john@example.com', '123456');
        $this->createBooking('2025-01-02', 'Jane Smith', 'jane@example.com', '654321');
        $this->createBooking('2025-02-01', 'Alice Brown', 'alice@example.com', '111222');

        // Add more for pagination (limit is 10)
        for ($i = 1; $i <= 10; $i++) {
            $this->createBooking('2025-03-01', "User $i", "user$i@example.com", "999$i");
        }

        $this->entityManager->flush();

        // Login
        $client->request('GET', '/en/login');
        $client->submitForm('Sign in', [
            '_username' => 'admin_test@example.com',
            '_password' => 'password',
        ]);
        $this->assertResponseRedirects('/en/admin');
        $client->followRedirect();

        // 1. Test no filters (should see first 10)
        $this->assertSelectorExists('table tbody tr');
        $rows = $client->getCrawler()->filter('table tbody tr');
        $this->assertEquals(10, count($rows));

        // 2. Test date filter
        $client->request('GET', '/en/admin?from=2025-01-01&to=2025-01-31');
        $rows = $client->getCrawler()->filter('table tbody tr');
        $this->assertEquals(2, count($rows));
        $this->assertSelectorTextContains('table tbody', 'John Doe');
        $this->assertSelectorTextContains('table tbody', 'Jane Smith');

        // 3. Test search filter (name)
        $client->request('GET', '/en/admin?search=Alice');
        $rows = $client->getCrawler()->filter('table tbody tr');
        $this->assertEquals(1, count($rows));
        $this->assertSelectorTextContains('table tbody', 'Alice Brown');

        // 4. Test search filter (email)
        $client->request('GET', '/en/admin?search=jane@example.com');
        $rows = $client->getCrawler()->filter('table tbody tr');
        $this->assertEquals(1, count($rows));
        $this->assertSelectorTextContains('table tbody', 'Jane Smith');

        // 5. Test search filter (phone)
        $client->request('GET', '/en/admin?search=123456');
        $rows = $client->getCrawler()->filter('table tbody tr');
        $this->assertEquals(1, count($rows));
        $this->assertSelectorTextContains('table tbody', 'John Doe');

        // 6. Test pagination
        $client->request('GET', '/en/admin');
        $this->assertSelectorExists('.pagination');
        $this->assertSelectorTextContains('.pagination', '2');

        $client->clickLink('2');
        $rows = $client->getCrawler()->filter('table tbody tr');
        // Total bookings: 3 + 10 = 13. Page 1 has 10, Page 2 should have 3.
        $this->assertEquals(3, count($rows));
    }
}
