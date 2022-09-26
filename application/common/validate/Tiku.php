<?php
/**
 * +----------------------------------------------------------------------
 * | 广告验证器
 * +----------------------------------------------------------------------
 */
namespace app\common\validate;

use think\Validate;

class Tiku extends Validate
{
    protected $rule = [
        'question|问题' => [
            'require' => 'require',
        ],
        'result|答案' => [
            'require' => 'require',
        ],
        'score|分数' => [
            'require' => 'require',
        ]
    ];
}