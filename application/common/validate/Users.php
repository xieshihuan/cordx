<?php
/**
 * +----------------------------------------------------------------------
 * | 会员验证器
 * +----------------------------------------------------------------------
 */
namespace app\common\validate;

use think\Validate;

class Users extends Validate
{
    protected $rule = [
        'group_id|用户组' => [
            'require' => 'require',
        ],
        'username|用户名' => [
            'require' => 'require',
            'min'     => '2',
            'max'     => '100',
        ],
        'mobile|手机号' => [
            'unique'  => 'users',
        ],
    ];
}