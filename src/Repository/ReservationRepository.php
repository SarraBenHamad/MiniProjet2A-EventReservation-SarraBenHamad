<?php
namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function save(Reservation $reservation, bool $flush = true): void
    {
        $this->getEntityManager()->persist($reservation);
        if ($flush) $this->getEntityManager()->flush();
    }
}
