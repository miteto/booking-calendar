<?php

namespace App\Tests;

use App\Entity\Slot;
use Doctrine\ORM\EntityManagerInterface;
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

    public function testDateRange(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $year = (int)date('Y') + 1;
        $range = "from_date=$year-03-20&end_date=$year-04-05";

        // One slot just before the range and one on its first day
        $slots = [];
        foreach (["$year-03-19", "$year-03-20"] as $date) {
            $slot = new Slot();
            $slot->setDate(new \DateTime($date));
            $slot->setStartTime(new \DateTime("$date 03:07:00"));
            $slot->setEndTime(new \DateTime("$date 03:08:00"));
            $slot->setBlocked(false);
            $em->persist($slot);
            $slots[] = $slot;
        }
        $em->flush();

        try {
            // Without the range both days are bookable
            $client->request('GET', "/en/$year/3");
            $this->assertSelectorExists("#day-$year-03-19:not(.disabled)");
            $this->assertSelectorExists("#day-$year-03-20:not(.disabled)");

            // Opens on the first month of the range, with no way back
            $crawler = $client->request('GET', "/en?embed=1&$range");
            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('h5', "March $year");
            $this->assertSelectorNotExists('.prev-month');
            $this->assertSelectorExists("#day-$year-03-19.disabled");
            $this->assertSelectorExists("#day-$year-03-20:not(.disabled)");
            $this->assertStringContainsString($range, $crawler->filter("#day-$year-03-20")->attr('href'));

            // The range is passed along to the next month, which is the last one
            $nextLink = $crawler->filter('.next-month')->link();
            $this->assertStringContainsString($range, $nextLink->getUri());
            $crawler = $client->click($nextLink);
            $this->assertSelectorTextContains('h5', "April $year");
            $this->assertSelectorNotExists('.next-month');
            $this->assertStringContainsString($range, $crawler->filter('.prev-month')->attr('href'));

            // ...and survives the redirect after the booking form is submitted (no slot picked here)
            $client->submit($crawler->selectButton('Confirm Booking')->form([
                'booking[userName]' => 'John Doe',
                'booking[userEmail]' => 'john@example.com',
                'booking[userPhone]' => '123456789',
            ]));
            $this->assertResponseRedirects();
            $this->assertStringContainsString("embed=1&$range", $client->getResponse()->headers->get('Location'));
        } finally {
            $em->createQuery('DELETE FROM ' . Slot::class . ' s WHERE s.id IN (:ids)')
                ->execute(['ids' => array_map(fn (Slot $slot) => $slot->getId(), $slots)]);
        }
    }
}
