<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReturnOrder;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareReturnOrderRepository;

/**
 * @internal
 */
#[CoversClass(ProfitShareReturnOrderRepository::class)]
#[RunTestsInSeparateProcesses]
class ProfitShareReturnOrderRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        return new ProfitShareReturnOrder();
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
