<?php

namespace app\api\validate;

use think\Validate;

class Admin extends Validate
{
    protected $rule = [
        'username|用户名' => [
            'require' => 'require',
            'min'     => '2',
            'max'     => '25',
            'unique'  => 'admin',
        ],
        'nickname|昵称' => [
            'require' => 'require',
            'max'     => '100',
            'unique'  => 'admin',
        ],
    ];



}