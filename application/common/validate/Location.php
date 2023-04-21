<?php
/**
 * +----------------------------------------------------------------------
 * | 广告验证器
 * +----------------------------------------------------------------------
 */
namespace app\common\validate;

use think\Validate;

class Location extends Validate
{
    protected $rule = [
        'title|位置' => [
            'require' => 'require',
        ],
        'lat|经度' => [
            'require' => 'require',
        ],
        'lng|纬度' => [
            'require' => 'require',
        ]
    ];
}