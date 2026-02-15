<?php

namespace App\Tests;

use App\Entity\Slot;
use App\Entity\Booking;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class BookingNoticeTest extends WebTestCase
{
    public function testCannotBookSlotWithinNoticePeriod(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        // 1. Create a slot very close to now (e.g., 30 minutes from now)
        // Ensure MINIMUM_BOOKING_NOTICE is 60 (set in .env)

        $now = new \DateTime();
        $slotTime = (clone $now)->modify('+30 minutes');

        $date = clone $slotTime;
        $date->setTime(0, 0, 0);

        $startTime = clone $slotTime;
        $endTime = (clone $slotTime)->modify('+1 hour');

        $slot = new Slot();
        $slot->setDate($date);
        $slot->setStartTime($startTime);
        $slot->setEndTime($endTime);
        $slot->setBlocked(false);
        $em->persist($slot);
        $em->flush();

        $slotId = $slot->getId();

        // 2. Attempt to book it
        $crawler = $client->request('GET', '/en?date=' . $date->format('Y-m-d'));

        $form = $crawler->selectButton('Confirm Booking')->form();
        $form['booking[userName]'] = 'Test User';
        $form['booking[userEmail]'] = 'test@example.com';
        $form['booking[userPhone]'] = '999999999';
        $form['booking[slot_id]'] = $slotId;
        $form['booking[date]'] = $date->format('Y-m-d');

        $client->submit($form);

        $this->assertResponseRedirects('/en?date=' . $date->format('Y-m-d'));
        $client->followRedirect();

        // Should show error flash
        $this->assertSelectorExists('.alert-danger');
        $this->assertSelectorTextContains('.alert-danger', 'It is too late to book this slot.');

        // 3. Verify it is NOT in the database
        $booking = $em->getRepository(Booking::class)->findOneBy([
            'date' => $date,
            'startTime' => $startTime,
        ]);

        $this->assertNull($booking, 'Booking should NOT be saved in the database');

        // Clean up
        $slotToRemove = $em->getRepository(Slot::class)->find($slotId);
        if ($slotToRemove) {
            $em->remove($slotToRemove);
            $em->flush();
        }
    }

    public function testCanBookSlotOutsideNoticePeriod(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        // 1. Create a slot outside notice period (e.g., 180 minutes from now)
        $now = new \DateTime();
        $slotTime = (clone $now)->modify('+180 minutes');

        $date = clone $slotTime;
        $date->setTime(0, 0, 0);

        $startTime = clone $slotTime;
        $endTime = (clone $slotTime)->modify('+1 hour');

        $slot = new Slot();
        $slot->setDate($date);
        $slot->setStartTime($startTime);
        $slot->setEndTime($endTime);
        $slot->setBlocked(false);
        $em->persist($slot);
        $em->flush();

        $slotId = $slot->getId();

        // 2. Attempt to book it
        $crawler = $client->request('GET', '/en?date=' . $date->format('Y-m-d'));

        $form = $crawler->selectButton('Confirm Booking')->form();
        $form['booking[userName]'] = 'Good User';
        $form['booking[userEmail]'] = 'good@example.com';
        $form['booking[userPhone]'] = '888888888';
        $form['booking[slot_id]'] = $slotId;
        $form['booking[date]'] = $date->format('Y-m-d');

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();

        $this->assertSelectorExists('.alert-success');

        // 3. Verify it IS in the database
        $booking = $em->getRepository(Booking::class)->findOneBy([
            'date' => $date,
            'startTime' => $startTime,
        ]);

        $this->assertNotNull($booking, 'Booking should be saved in the database');

        // Clean up
        if ($booking) {
            $em->remove($booking);
        }
        $slotToRemove = $em->getRepository(Slot::class)->find($slotId);
        if ($slotToRemove) {
            $em->remove($slotToRemove);
        }
        $em->flush();
    }
}
