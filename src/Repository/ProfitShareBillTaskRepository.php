<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask;

/**
 * @extends ServiceEntityRepository<ProfitShareBillTask>
 */
#[AsRepository(entityClass: ProfitShareBillTask::class)]
final class ProfitShareBillTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfitShareBillTask::class);
    }

    public function save(ProfitShareBillTask $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProfitShareBillTask $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
