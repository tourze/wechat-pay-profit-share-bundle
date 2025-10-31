<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use WechatPayBundle\WechatPayBundle;

class WechatPayProfitShareBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            WechatPayBundle::class => ['all' => true],
            DoctrineBundle::class => ['all' => true],
        ];
    }
}
