<?php

namespace App\Command;

use App\Entity\Slot;
use App\Repository\SlotConfigRepository;
use App\Repository\SlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate-slots',
    description: 'Generate slots for the next N months (default 3) based on SlotConfig.',
)]
class GenerateSlotsCommand extends Command
{
    public function __construct(
        private readonly SlotConfigRepository $slotConfigRepository,
        private readonly SlotRepository $slotRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('months', mode: InputOption::VALUE_REQUIRED, description: 'Months ahead to generate', default: 3)
            ->addOption('from', mode: InputOption::VALUE_REQUIRED, description: 'Start date (Y-m-d), defaults to today')
            ->addOption('dry-run', mode: InputOption::VALUE_NONE, description: 'Do not write, only show what would be created');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $months = (int)$input->getOption('months');
        $fromStr = $input->getOption('from');
        $dryRun = (bool)$input->getOption('dry-run');

        $startDate = $fromStr ? new \DateTime($fromStr) : new \DateTime('today');
        $startDate->setTime(0, 0, 0);
        $endDate = (clone $startDate)->modify('+' . $months . ' months');

        $created = 0;
        $skipped = 0;

        for ($d = clone $startDate; $d < $endDate; $d->modify('+1 day')) {
            $dayOfWeek = $d->format('l');
            $config = $this->slotConfigRepository->findOneBy(['dayOfWeek' => $dayOfWeek]);
            if (!$config) {
                $config = $this->slotConfigRepository->findOneBy(['dayOfWeek' => null]); // global
            }
            if (!$config) {
                $skipped++;
                continue;
            }

            $slotStart = new \DateTime($d->format('Y-m-d') . ' ' . $config->getStartTime()->format('H:i:s'));
            $slotEndBoundary = new \DateTime($d->format('Y-m-d') . ' ' . $config->getEndTime()->format('H:i:s'));
            $interval = (int)$config->getSlotInterval();

            for ($current = clone $slotStart; $current < $slotEndBoundary; $current->modify('+' . $interval . ' minutes')) {
                $end = (clone $current)->modify('+' . $interval . ' minutes');
                if ($end > $slotEndBoundary) {
                    break;
                }
                // avoid duplicates
                $existing = $this->slotRepository->findOneByDateAndStart($d, $current);
                if ($existing) {
                    continue;
                }

                $slot = (new Slot())
                    ->setDate(clone $d)
                    ->setStartTime(clone $current)
                    ->setEndTime($end);

                if (!$dryRun) {
                    $this->em->persist($slot);
                }
                $created++;
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        $output->writeln(sprintf('Created %d slot(s). Skipped days without config: %d', $created, $skipped));

        return Command::SUCCESS;
    }
}
