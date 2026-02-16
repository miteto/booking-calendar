<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminCalendarTest extends WebTestCase
{
    private $entityManager;

    private function createAdmin(): \App\Entity\User
    {
        $user = new \App\Entity\User();
        $user->setEmail('admin_test' . uniqid() . '@example.com');
        $user->setRoles(['ROLE_ADMIN']);

        $hasher = self::getContainer()->get('security.user_password_hasher');
        $user->setPassword($hasher->hashPassword($user, 'password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testCalendarPageIsAccessible(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $admin = $this->createAdmin();
        $client->loginUser($admin);

        $client->request('GET', '/en/admin/calendar');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Booking Calendar');
    }

    public function testCalendarNavigation(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $admin = $this->createAdmin();
        $client->loginUser($admin);

        $client->request('GET', '/en/admin/calendar/2026/02');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'February 2026');

        $client->clickLink('Next');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'March 2026');

        $client->clickLink('Previous');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'February 2026');
    }
    public function testCalendarShowsBookings(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $admin = $this->createAdmin();
        $client->loginUser($admin);

        // Create a booking
        $booking = new \App\Entity\Booking();
        $booking->setDate(new \DateTime('2026-02-15'));
        $booking->setStartTime(new \DateTime('14:00:00'));
        $booking->setEndTime(new \DateTime('15:00:00'));
        $booking->setUserName('Calendar Test User');
        $booking->setUserEmail('calendar@example.com');
        $booking->setUserPhone('123456789');
        $booking->setLocale('en');

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        $client->request('GET', '/en/admin/calendar/2026/02');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table', 'Calendar Test User');
        $this->assertSelectorTextContains('table', '14:00');
    }
}
