<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;

/**
 * @extends ServiceEntityRepository<ProfitShareOrder>
 */
#[AsRepository(entityClass: ProfitShareOrder::class)]
final class ProfitShareOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfitShareOrder::class);
    }

    public function save(ProfitShareOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProfitShareOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
