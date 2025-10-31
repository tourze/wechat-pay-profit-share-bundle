<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WechatPayProfitShareBundle\Repository\ProfitShareOrderRepository;

#[CoversClass(ProfitShareOrderRepository::class)]
#[RunTestsInSeparateProcesses]
class ProfitShareOrderRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        return new \Tourze\WechatPayProfitShareBundle\Entity\ProfitShareOrder();
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