<?php

namespace App\Tests;

use App\Entity\Slot;
use App\Entity\Booking;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class BookingProcessTest extends WebTestCase
{
    public function testBookingAValidSlot(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        // 1. Create a slot
        $date = new \DateTime('2030-01-02');
        $date->setTime(0, 0, 0);
        $startTime = new \DateTime('2030-01-02 10:00:00');
        $endTime = new \DateTime('2030-01-02 11:00:00');

        $existingBooking = $em->getRepository(Booking::class)->findOneBy(['date' => $date]);
        if ($existingBooking) {
            $em->remove($existingBooking);
            $em->flush();
        }

        $slot = $em->getRepository(Slot::class)->findOneBy(['date' => $date, 'startTime' => $startTime]);
        if (!$slot) {
            $slot = new Slot();
            $slot->setDate($date);
            $slot->setStartTime($startTime);
            $slot->setEndTime($endTime);
            $slot->setBlocked(false);
            $em->persist($slot);
            $em->flush();
        }

        $slotId = $slot->getId();
        $this->assertNotNull($slotId);

        // 2. Attempt to book it
        $crawler = $client->request('GET', '/en');
        // We need a date to show the slots and the modal
        $crawler = $client->request('GET', '/en?date=' . $date->format('Y-m-d'));

        $form = $crawler->selectButton('Confirm Booking')->form();
        $form['booking[userName]'] = 'John Doe';
        $form['booking[userEmail]'] = 'john@example.com';
        $form['booking[userPhone]'] = '123456789';
        $form['booking[slot_id]'] = $slotId;
        $form['booking[date]'] = $date->format('Y-m-d');

        $client->submit($form);

        $this->assertResponseRedirects('/en');
        $client->followRedirect();
        $this->assertSelectorExists('.alert-success');

        // 3. Verify it is in the database
        $booking = $em->getRepository(Booking::class)->findOneBy([
            'date' => $date,
        ]);

        $this->assertNotNull($booking, 'Booking should be saved in the database');
        $this->assertEquals('John Doe', $booking->getUserName());
        $this->assertEquals('10:00', $booking->getStartTime()->format('H:i'));
    }
}
