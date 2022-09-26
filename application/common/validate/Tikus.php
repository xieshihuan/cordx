<?php
/**
 * +----------------------------------------------------------------------
 * | 广告位验证器
 * +----------------------------------------------------------------------
 */
namespace app\common\validate;

use think\Validate;

class Tikus extends Validate
{
    protected $rule = [
        // 'title' => [
        //     'require' => 'require',
        //     'max'     => '255',
        //     'unique' => 'tikus'
        // ]
    ];

    protected $message = [
        // 'title.require' => '请输入题库名称',
        // 'title.max' => '题库名称不能超过255字符',
        // 'title.unique' => '题库已存在，请重新创建',
    ];

}