<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareReceiverRepository;

/**
 * @internal
 */
#[CoversClass(ProfitShareReceiverRepository::class)]
#[RunTestsInSeparateProcesses]
class ProfitShareReceiverRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $receiver = new ProfitShareReceiver();
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
