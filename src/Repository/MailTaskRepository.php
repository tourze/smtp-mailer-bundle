<?php

namespace Tourze\SMTPMailerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\SMTPMailerBundle\Entity\MailTask;

/**
 * 邮件任务仓库
 *
 * @method MailTask|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailTask|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailTask[] findAll()
 * @method MailTask[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailTask::class);
    }

    /**
     * 查找待处理的邮件任务
     */
    public function findPendingTasks(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', MailTask::STATUS_PENDING)
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找计划发送的任务
     */
    public function findScheduledTasks(): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.scheduledAt IS NOT NULL')
            ->andWhere('t.scheduledAt <= :now')
            ->setParameter('status', MailTask::STATUS_PENDING)
            ->setParameter('now', $now)
            ->orderBy('t.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 按状态查找任务
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 按日期范围查找任务
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.createdAt >= :startDate')
            ->andWhere('t.createdAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 按SMTP配置查找任务
     */
    public function findBySmtpConfig(int $smtpConfigId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.smtpConfig = :smtpConfigId')
            ->setParameter('smtpConfigId', $smtpConfigId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
