<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;

/**
 * @extends ServiceEntityRepository<ProfitShareOperationLog>
 */
#[AsRepository(entityClass: ProfitShareOperationLog::class)]
class ProfitShareOperationLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfitShareOperationLog::class);
    }

    public function save(ProfitShareOperationLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProfitShareOperationLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
