<?php

namespace App\Tests;

use App\Entity\Slot;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteOldSlotsCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Slot')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Booking')->execute();
    }

    public function testExecute(): void
    {
        // 1. Setup: Create slots in past, today, and future
        $pastDate = new \DateTime('-1 day');
        $todayDate = new \DateTime('today');
        $futureDate = new \DateTime('+1 day');

        $this->createSlot($pastDate, '10:00:00');
        $this->createSlot($todayDate, '10:00:00');
        $this->createSlot($futureDate, '10:00:00');

        $this->entityManager->flush();

        // Verify they exist
        $repo = $this->entityManager->getRepository(Slot::class);
        $this->assertCount(1, $repo->findBy(['date' => $pastDate]));
        $this->assertCount(1, $repo->findBy(['date' => $todayDate]));
        $this->assertCount(1, $repo->findBy(['date' => $futureDate]));

        // 2. Run the command
        $application = new Application(self::$kernel);
        $command = $application->find('app:delete-old-slots');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Deleted 1 old slot(s)', $output);

        // 3. Verify results
        $this->assertCount(0, $repo->findBy(['date' => $pastDate]), 'Past slot should be deleted');
        $this->assertCount(1, $repo->findBy(['date' => $todayDate]), 'Today\'s slot should NOT be deleted');
        $this->assertCount(1, $repo->findBy(['date' => $futureDate]), 'Future slot should NOT be deleted');
    }

    private function createSlot(\DateTimeInterface $date, string $time): void
    {
        $slot = new Slot();
        $slot->setDate(clone $date);
        $slot->setStartTime(new \DateTime($time));
        $slot->setEndTime((new \DateTime($time))->modify('+1 hour'));
        $slot->setBlocked(false);
        $this->entityManager->persist($slot);
    }
}
