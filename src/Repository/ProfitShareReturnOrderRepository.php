<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReturnOrder;

/**
 * @extends ServiceEntityRepository<ProfitShareReturnOrder>
 */
#[AsRepository(entityClass: ProfitShareReturnOrder::class)]
class ProfitShareReturnOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfitShareReturnOrder::class);
    }

    public function save(ProfitShareReturnOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProfitShareReturnOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
