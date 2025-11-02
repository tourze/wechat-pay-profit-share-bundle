<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;

/**
 * @internal
 */
#[CoversClass(ProfitShareOperationLogRepository::class)]
#[RunTestsInSeparateProcesses]
class ProfitShareOperationLogRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        return new ProfitShareOperationLog();
    }

    protected function getRepository(): ProfitShareOperationLogRepository
    {
        return self::getService(ProfitShareOperationLogRepository::class);
    }

    protected function onSetUp(): void
    {
        // Empty implementation
    }
}
