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
final class ProfitShareReturnOrderRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $order = new ProfitShareReturnOrder();
        $order->setSubMchId('test_sub_mch_' . bin2hex(random_bytes(4)));
        $order->setOutReturnNo('test_return_' . bin2hex(random_bytes(8)));

        return $order;
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
