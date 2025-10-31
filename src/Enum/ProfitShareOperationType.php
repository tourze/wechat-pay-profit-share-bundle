<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ProfitShareOperationType: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case REQUEST_ORDER = 'request_order';
    case QUERY_ORDER = 'query_order';
    case UNFREEZE_ORDER = 'unfreeze_order';
    case REQUEST_RETURN = 'request_return';
    case QUERY_RETURN = 'query_return';
    case QUERY_REMAINING_AMOUNT = 'query_remaining_amount';
    case QUERY_MAX_RATIO = 'query_max_ratio';
    case ADD_RECEIVER = 'add_receiver';
    case DELETE_RECEIVER = 'delete_receiver';
    case APPLY_BILL = 'apply_bill';
    case DOWNLOAD_BILL = 'download_bill';
    case NOTIFICATION = 'notification';

    public function getLabel(): string
    {
        return match ($this) {
            self::REQUEST_ORDER => '请求分账',
            self::QUERY_ORDER => '查询分账',
            self::UNFREEZE_ORDER => '解冻剩余资金',
            self::REQUEST_RETURN => '请求分账回退',
            self::QUERY_RETURN => '查询分账回退',
            self::QUERY_REMAINING_AMOUNT => '查询剩余金额',
            self::QUERY_MAX_RATIO => '查询最大分账比例',
            self::ADD_RECEIVER => '添加分账接收方',
            self::DELETE_RECEIVER => '删除分账接收方',
            self::APPLY_BILL => '申请分账账单',
            self::DOWNLOAD_BILL => '下载分账账单',
            self::NOTIFICATION => '分账通知',
        };
    }
}
