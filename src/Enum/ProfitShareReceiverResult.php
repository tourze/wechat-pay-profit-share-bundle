<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ProfitShareReceiverResult: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'PENDING';
    case SUCCESS = 'SUCCESS';
    case CLOSED = 'CLOSED';
    case FAILED = 'FAILED';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::SUCCESS => '成功',
            self::CLOSED => '已关闭',
            self::FAILED => '失败',
        };
    }
}
