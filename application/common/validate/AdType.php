<?php
/**
 * +----------------------------------------------------------------------
 * | 广告位验证器
 * +----------------------------------------------------------------------
 */
namespace app\common\validate;

use think\Validate;

class AdType extends Validate
{
    protected $rule = [
        'name|广告位名称' => [
            'require' => 'require',
            'max'     => '255',
            'unique'  => 'ad_type',
        ],
        'description|描述' => [
            'max' => '255'
        ],
        'sort|排序' => [
            'require' => 'require',
            'number'  => 'number',
        ]
    ];
}