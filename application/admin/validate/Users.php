<?php

namespace app\admin\validate;

use think\Validate;

class Users extends Validate
{
    protected $rule = [
        'username|用户名' => [
            'require' => 'require',
            'min'     => '2',
            'max'     => '25',
        ],
        'mobile|手机号' => [
            'require' => 'require',
            'max'     => '100',
        ],
    ];



}