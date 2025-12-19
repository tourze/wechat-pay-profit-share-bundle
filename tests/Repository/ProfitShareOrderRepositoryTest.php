<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOrderRepository;

/**
 * @internal
 */
#[CoversClass(ProfitShareOrderRepository::class)]
#[RunTestsInSeparateProcesses]
final class ProfitShareOrderRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $order = new ProfitShareOrder();
        $order->setSubMchId('test_sub_mch_' . bin2hex(random_bytes(4)));
        $order->setTransactionId('test_transaction_' . bin2hex(random_bytes(8)));
        $order->setOutOrderNo('test_out_order_' . bin2hex(random_bytes(8)));

        return $order;
    }

    protected function getRepository(): ProfitShareOrderRepository
    {
        return self::getService(ProfitShareOrderRepository::class);
    }

    protected function onSetUp(): void
    {
        // Empty implementation
    }
}
