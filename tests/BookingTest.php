<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Book Your Appointment');
        $this->assertSelectorExists('.calendar-grid');
        $this->assertSelectorExists('.next-month');
    }

    public function testNavigation(): void
    {
        $client = static::createClient();

        // Go to next month
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');
        $nextMonth = $currentMonth + 1;
        $nextYear = $currentYear;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        $crawler = $client->request('GET', "/en/$nextYear/$nextMonth");
        $this->assertResponseIsSuccessful();

        // Should have a Prev arrow now
        $this->assertSelectorExists('.prev-month');
    }
}
