<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isActive = true')
            ->andWhere('u.isSuspended = false')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findWithActiveSubscriptions(): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.subscriptions', 's')
            ->where('s.status IN (:statuses)')
            ->setParameter('statuses', ['active', 'trialing'])
            ->andWhere('u.isActive = true')
            ->andWhere('u.isSuspended = false')
            ->groupBy('u.id')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(): array
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as total, SUM(CASE WHEN u.isActive = true THEN 1 ELSE 0 END) as active, SUM(CASE WHEN u.isSuspended = true THEN 1 ELSE 0 END) as suspended')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRecentlyRegistered(int $days = 30): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        return $this->createQueryBuilder('u')
            ->where('u.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
