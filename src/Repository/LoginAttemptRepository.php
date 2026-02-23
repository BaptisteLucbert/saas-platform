<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LoginAttempt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginAttempt>
 */
class LoginAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginAttempt::class);
    }

    public function countRecentFailedByIp(string $ip, int $minutes = 15): int
    {
        $since = new \DateTimeImmutable("-{$minutes} minutes");
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.ipAddress = :ip')
            ->andWhere('l.success = false')
            ->andWhere('l.createdAt >= :since')
            ->setParameter('ip', $ip)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countRecentFailedByEmail(string $email, int $minutes = 15): int
    {
        $since = new \DateTimeImmutable("-{$minutes} minutes");
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.email = :email')
            ->andWhere('l.success = false')
            ->andWhere('l.createdAt >= :since')
            ->setParameter('email', $email)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(LoginAttempt $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush)
            $this->getEntityManager()->flush();
    }
}
