<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareReturnOrderRepository;

#[CoversClass(ProfitShareReturnOrderRepository::class)]
#[RunTestsInSeparateProcesses]
class ProfitShareReturnOrderRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        return new \Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReturnOrder();
    }

    protected function getRepository(): ProfitShareReturnOrderRepository
    {
        return self::getService(ProfitShareReturnOrderRepository::class);
    }

    protected function onSetUp(): void
    {
        // Empty implementation
    }
}