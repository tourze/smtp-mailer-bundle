<?php

namespace Tourze\SMTPMailerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * SMTP配置仓库
 *
 * @method SMTPConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method SMTPConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method SMTPConfig[] findAll()
 * @method SMTPConfig[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SMTPConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SMTPConfig::class);
    }

    /**
     * 获取所有启用的SMTP配置
     */
    public function findAllEnabled(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据权重获取SMTP配置
     */
    public function findAllEnabledWithWeight(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('s.weight', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据优先级获取SMTP配置
     */
    public function findAllEnabledByPriority(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('s.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取启用的SMTP配置总数
     */
    public function countEnabled(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.enabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
