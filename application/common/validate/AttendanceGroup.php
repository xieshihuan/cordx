<?php
namespace app\common\validate;

use think\Validate;

class AttendanceGroup extends Validate
{
    protected $rule = [
        'id' => 'require',
        'title' => 'require',
        'classes_type' => 'require',
    ];
    protected $message  =   [
        'id.require' => 'id不存在',
        'title.require' => '考勤组名称不能为空',
        'classes_type.require' => '班次类型不能为空',
    ];
    protected $scene = [
        'add'   =>  ['title','classes_type'],
        'edit'  =>  ['id','title','classes_type'],
        'del'  =>  ['id'],
    ];
}