<?php
/**
 * +----------------------------------------------------------------------
 * | 广告验证器
 * +----------------------------------------------------------------------
 */
namespace app\common\validate;

use think\Validate;

class Cateuser extends Validate
{
    protected $rule = [
        'uid|会员id' => [
            'require' => 'require',
            'number'  => 'number',
        ],
        'catid|组织id' => [
            'require' => 'require',
            'number'  => 'number',
            'max'     => '255',
        ],
        'level|级别' => [
            'require' => 'require',
            'number'  => 'number',
        ]
    ];
}