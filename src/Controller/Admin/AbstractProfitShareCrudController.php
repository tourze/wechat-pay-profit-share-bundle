<?php

declare(strict_types=1);

namespace Tourze\WechatPayProfitShareBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Tourze\WechatPayProfitShareBundle\Controller\Admin\Traits\JsonFormatterTrait;
use Tourze\WechatPayProfitShareBundle\Controller\Admin\Traits\SensitiveDataMaskingTrait;

/**
 * 分账后台管理控制器基类
 *
 * 提供所有分账相关 CRUD 控制器的公共配置：
 * - 默认分页（每页 20 条，最大 100 条）（FR-030）
 * - 默认按 createTime DESC 排序（FR-031）
 * - 敏感数据脱敏方法（FR-032）
 * - JSON 格式化方法
 */
abstract class AbstractProfitShareCrudController extends AbstractCrudController
{
    use SensitiveDataMaskingTrait;
    use JsonFormatterTrait;

    /**
     * 配置 CRUD 默认行为
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(5)
            ->showEntityActionsInlined()
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->setDateFormat('yyyy-MM-dd')
        ;
    }

    /**
     * 格式化金额（分转元）
     *
     * @param int|null $cents 金额（分）
     * @return string 格式化后的金额字符串
     */
    protected function formatAmount(?int $cents): string
    {
        if (null === $cents) {
            return '0.00 元';
        }

        return number_format($cents / 100, 2) . ' 元';
    }

    /**
     * 格式化布尔值
     */
    protected function formatBoolean(?bool $value): string
    {
        if (null === $value) {
            return '未知';
        }

        return $value ? '是' : '否';
    }
}
