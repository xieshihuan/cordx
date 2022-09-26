<?php
/**
 * +----------------------------------------------------------------------
 * | 广告验证器
 * +----------------------------------------------------------------------
 */
namespace app\common\validate;

use think\Validate;

class Ad extends Validate
{
    protected $rule = [
        'type_id|广告位置' => [
            'require' => 'require',
        ],
        'name|广告名称' => [
            'require' => 'require',
            'max'     => '255',
        ],
        'description|描述' => [
            'max' => '255',
        ],
        'sort|排序' => [
            'require' => 'require',
            'number'  => 'number',
        ]
    ];
}