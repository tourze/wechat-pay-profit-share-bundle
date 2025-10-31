<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareReceiverRepository;

#[CoversClass(ProfitShareReceiverRepository::class)]
#[RunTestsInSeparateProcesses]
class ProfitShareReceiverRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        return new \Tourze\WechatPayProfitShareBundle\Entity\ProfitShareReceiver();
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