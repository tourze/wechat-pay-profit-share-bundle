<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareReceiverRepository;

/**
 * @internal
 */
#[CoversClass(ProfitShareReceiverRepository::class)]
#[RunTestsInSeparateProcesses]
final class ProfitShareReceiverRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        // 创建关联的 ProfitShareOrder
        $order = new ProfitShareOrder();
        $order->setSubMchId('test_sub_mch_' . bin2hex(random_bytes(4)));
        $order->setTransactionId('test_transaction_' . bin2hex(random_bytes(8)));
        $order->setOutOrderNo('test_out_order_' . bin2hex(random_bytes(8)));

        $receiver = new ProfitShareReceiver();
        $receiver->setOrder($order);
        $receiver->setType('MERCHANT_ID');
        $receiver->setAccount('test_account_' . bin2hex(random_bytes(4)));
        $receiver->setAmount(100);
        $receiver->setDescription('Test profit share');

        return $receiver;
    }

    protected function getRepository(): ProfitShareReceiverRepository
    {
        return self::getService(ProfitShareReceiverRepository::class);
    }

    protected function onSetUp(): void
    {
        // Empty implementation
    }
}
