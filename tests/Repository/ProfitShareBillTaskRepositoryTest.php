<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareBillTask;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareBillTaskRepository;

/**
 * @internal
 */
#[CoversClass(ProfitShareBillTaskRepository::class)]
#[RunTestsInSeparateProcesses]
class ProfitShareBillTaskRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        return new ProfitShareBillTask();
    }

    protected function getRepository(): ProfitShareBillTaskRepository
    {
        return self::getService(ProfitShareBillTaskRepository::class);
    }

    protected function onSetUp(): void
    {
        // Empty implementation
    }
}
