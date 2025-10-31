<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ProfitShareOrderState: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PROCESSING = 'PROCESSING';
    case FINISHED = 'FINISHED';
    case CLOSED = 'CLOSED';

    public function getLabel(): string
    {
        return match ($this) {
            self::PROCESSING => '处理中',
            self::FINISHED => '已完成',
            self::CLOSED => '已关闭',
        };
    }
}
