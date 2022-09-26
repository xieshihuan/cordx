<?php
/**
 * +----------------------------------------------------------------------
 * | 广告验证器
 * +----------------------------------------------------------------------
 */
namespace app\common\validate;

use think\Validate;

class Daxuetang extends Validate
{
    protected $rule = [
        'tiku_id|标题' => [
            'require' => 'require',
        ],
        'exam_name|考试名称' => [
            'require' => 'require',
        ],
        'start|开始时间' => [
            'require' => 'require',
        ],
        'end|结束时间' => [
            'require' => 'require',
        ],
        'exam_time|考试时间' => [
            'require' => 'require',
        ]
    ];
}