<?php

namespace App\Repository;

use App\Entity\Slot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Slot>
 */
class SlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slot::class);
    }

    /**
     * @return Slot[]
     */
    public function findByDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.date = :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByDateAndStart(\DateTimeInterface $date, \DateTimeInterface $start): ?Slot
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.date = :date')
            ->andWhere('s.startTime = :start')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('start', $start->format('H:i:s'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Slot[]
     */
    public function findInRange(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.date >= :start')
            ->andWhere('s.date <= :end')
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
