<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * @return Booking[]
     */
    public function findInRange(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.date >= :start')
            ->andWhere('b.date <= :end')
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    public function findFiltered(?\DateTimeInterface $from, ?\DateTimeInterface $to, ?string $search, int $page = 1, int $limit = 10): Paginator
    {
        $qb = $this->createQueryBuilder('b');

        if ($from) {
            $qb->andWhere('b.date >= :from')
                ->setParameter('from', $from->format('Y-m-d'));
        }

        if ($to) {
            $qb->andWhere('b.date <= :to')
                ->setParameter('to', $to->format('Y-m-d'));
        }

        if ($search) {
            $qb->andWhere('b.userName LIKE :search OR b.userEmail LIKE :search OR b.userPhone LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('b.date', 'ASC')
            ->addOrderBy('b.startTime', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($qb->getQuery());
    }
}
