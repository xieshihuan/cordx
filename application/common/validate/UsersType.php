<?php
namespace app\common\validate;

use think\Validate;

class UsersType extends Validate
{
    protected $rule = [
        'name|会员组名称' => [
            'require' => 'require',
            'max'     => '255',
            'unique'  => 'users_type',
        ],

        'description|描述' => [
            'max' => '255',
        ],
        'sort|排序' => [
            'require' => 'require',
            'number'  => 'number'
        ],
    ];
}