<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ProfitShareBillStatus: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case READY = 'ready';
    case DOWNLOADED = 'downloaded';
    case FAILED = 'failed';
    case EXPIRED = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待生成',
            self::READY => '可下载',
            self::DOWNLOADED => '已下载',
            self::FAILED => '失败',
            self::EXPIRED => '已过期',
        };
    }
}
