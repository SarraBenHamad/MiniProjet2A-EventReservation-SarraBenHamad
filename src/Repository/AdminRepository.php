<?php
namespace App\Repository;

use App\Entity\Admin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AdminRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Admin::class);
    }

    public function save(Admin $admin, bool $flush = true): void
    {
        $this->getEntityManager()->persist($admin);
        if ($flush) $this->getEntityManager()->flush();
    }
}
