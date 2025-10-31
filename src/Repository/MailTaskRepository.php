<?php

namespace Tourze\SMTPMailerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;

/**
 * 邮件任务仓库
 *
 * @extends ServiceEntityRepository<MailTask>
 */
#[AsRepository(entityClass: MailTask::class)]
class MailTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailTask::class);
    }

    /**
     * 查找待处理的邮件任务
     *
     * @return MailTask[]
     */
    public function findPendingTasks(): array
    {
        /** @var MailTask[] $result */
        $result = $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', MailTaskStatus::PENDING)
            ->orderBy('t.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        return $result;
    }

    /**
     * 查找计划发送的任务
     *
     * @return MailTask[]
     */
    public function findScheduledTasks(): array
    {
        $now = new \DateTimeImmutable();

        /** @var MailTask[] $result */
        $result = $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.scheduledTime IS NOT NULL')
            ->andWhere('t.scheduledTime <= :now')
            ->setParameter('status', MailTaskStatus::PENDING)
            ->setParameter('now', $now)
            ->orderBy('t.scheduledTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        return $result;
    }

    /**
     * 按状态查找任务
     *
     * @return MailTask[]
     */
    public function findByStatus(string $status): array
    {
        /** @var MailTask[] $result */
        $result = $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.updateTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        return $result;
    }

    /**
     * 按日期范围查找任务
     *
     * @return MailTask[]
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var MailTask[] $result */
        $result = $this->createQueryBuilder('t')
            ->where('t.createTime >= :startDate')
            ->andWhere('t.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        return $result;
    }

    /**
     * 按SMTP配置查找任务
     *
     * @return MailTask[]
     */
    public function findBySmtpConfig(int $smtpConfigId): array
    {
        /** @var MailTask[] $result */
        $result = $this->createQueryBuilder('t')
            ->where('t.smtpConfig = :smtpConfigId')
            ->setParameter('smtpConfigId', $smtpConfigId)
            ->orderBy('t.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        return $result;
    }

    public function save(MailTask $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MailTask $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
