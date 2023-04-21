<?php
namespace app\common\validate;

use think\Validate;

class Spec extends Validate
{
    protected $rule = [
        'id' => 'require',
        'title' => 'require',
        'tiku_id' => 'require',
    ];
    protected $message  =   [
        'id.require' => 'id不存在',
        'title.require' => '名称不存在',
        'tiku_id.require' => '属性不存在',
    ];
    protected $scene = [
        'add'   =>  ['title','tiku_id'],
        'edit'  =>  ['id','title','tiku_id'],
        'del'  =>  ['id'],
    ];
}