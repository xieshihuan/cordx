<?php
namespace app\common\validate;

use think\Validate;

class ProductCate extends Validate
{
    protected $rule = [
        'id' => 'require',
        'title' => 'require',
        'sort' => 'require',
    ];
    protected $message  =   [
        'id.require' => 'id不存在',
        'title.require' => '名称不存在',
        'sort.require' => '排序不存在',
    ];
    protected $scene = [
        'add'   =>  ['title','sort'],
        'edit'  =>  ['id','title','sort'],
        'del'  =>  ['id'],
    ];
}