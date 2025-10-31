<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOperationLogRepository;

#[CoversClass(ProfitShareOperationLogRepository::class)]
#[RunTestsInSeparateProcesses]
class ProfitShareOperationLogRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        return new \Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOperationLog();
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