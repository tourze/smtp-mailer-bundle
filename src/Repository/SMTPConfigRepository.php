<?php

namespace Tourze\SMTPMailerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * SMTP配置仓库
 *
 * @extends ServiceEntityRepository<SMTPConfig>
 */
#[AsRepository(entityClass: SMTPConfig::class)]
class SMTPConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SMTPConfig::class);
    }

    /**
     * 获取所有启用的SMTP配置
     *
     * @return SMTPConfig[]
     */
    public function findAllEnabled(): array
    {
        /** @var SMTPConfig[] $result */
        $result = $this->createQueryBuilder('s')
            ->where('s.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        return $result;
    }

    /**
     * 根据权重获取SMTP配置
     *
     * @return SMTPConfig[]
     */
    public function findAllEnabledWithWeight(): array
    {
        /** @var SMTPConfig[] $result */
        $result = $this->createQueryBuilder('s')
            ->where('s.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('s.weight', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        return $result;
    }

    /**
     * 根据优先级获取SMTP配置
     *
     * @return SMTPConfig[]
     */
    public function findAllEnabledByPriority(): array
    {
        /** @var SMTPConfig[] $result */
        $result = $this->createQueryBuilder('s')
            ->where('s.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('s.priority', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        return $result;
    }

    /**
     * 获取启用的SMTP配置总数
     */
    public function countEnabled(): int
    {
        $count = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.valid = :valid')
            ->setParameter('valid', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $count;
    }

    public function save(SMTPConfig $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SMTPConfig $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
