<?php
/**
 * +----------------------------------------------------------------------
 * | 产品验证器
 * +----------------------------------------------------------------------
 */
namespace app\common\validate;

use think\Validate;

class Product extends Validate
{
    protected $rule = [
        'cate_id|所属分类' => [
            'require' => 'require',
        ],
        'type_id|所属模型' => [
            'require' => 'require',
            'max'     => '255',
        ],
        'zhandian_id|站点' => [
            'require' => 'require',
            'number'  => 'number',
        ]
    ];
}