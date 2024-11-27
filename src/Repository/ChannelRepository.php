<?php

namespace App\Repository;

use App\Entity\Channel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Channel>
 */
class ChannelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Channel::class);
    }

    /**
     * @return Channel[] Returns an array of Channel objects
     */
    public function findByIsOwn(bool $value): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isOwn = :isOwn')
            ->setParameter('isOwn', $value)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }


    //    public function findOneBySomeField($value): ?Channel
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
