<?php

namespace App\Command;

use App\Entity\Slot;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:delete-old-slots',
    description: 'Deletes slots before today.',
)]
class DeleteOldSlotsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $today = new \DateTime('today');

        $count = $this->entityManager->createQueryBuilder()
            ->delete(Slot::class, 's')
            ->where('s.date < :today')
            ->setParameter('today', $today->format('Y-m-d'))
            ->getQuery()
            ->execute();

        $io->success(sprintf('Deleted %d old slot(s).', $count));

        return Command::SUCCESS;
    }
}
