<?php
namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function save(Event $event, bool $flush = true): void
    {
        $this->getEntityManager()->persist($event);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(Event $event, bool $flush = true): void
    {
        $this->getEntityManager()->remove($event);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.date >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
