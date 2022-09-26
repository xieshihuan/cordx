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
        'catid|所属分类' => [
            'require' => 'require',
        ],
        'title|产品标题' => [
            'require' => 'require',
            'max'     => '255',
        ],
        'sort|排序' => [
            'require' => 'require',
            'number'  => 'number',
        ]
    ];
}