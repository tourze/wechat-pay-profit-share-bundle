<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareBillTaskRepository;

#[CoversClass(ProfitShareBillTaskRepository::class)]
#[RunTestsInSeparateProcesses]
class ProfitShareBillTaskRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        return new \Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask();
    }

    protected function getRepository(): \Tourze\WechatPayProfitShareBundle\Repository\ProfitShareBillTaskRepository
    {
        return self::getService(\Tourze\WechatPayProfitShareBundle\Repository\ProfitShareBillTaskRepository::class);
    }

    protected function onSetUp(): void
    {
        // Empty implementation
    }
}