<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;

/**
 * @extends ServiceEntityRepository<ProfitShareReceiver>
 */
#[AsRepository(entityClass: ProfitShareReceiver::class)]
class ProfitShareReceiverRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfitShareReceiver::class);
    }

    public function save(ProfitShareReceiver $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProfitShareReceiver $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
