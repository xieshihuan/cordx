<?php
/**
 * +----------------------------------------------------------------------
 * | 广告验证器
 * +----------------------------------------------------------------------
 */
namespace app\common\validate;

use think\Validate;

class Assess extends Validate
{
    protected $rule = [
        'id' => 'require',
        'uid' => 'require',
        'otherid' => 'require',
        'qid'  =>  'require',
        'one'  =>  'require',
        'two'  =>  'require',
        'three'  =>  'require',
    ];
    protected $message  =   [
        'id.require' => 'id不存在',
        'qid.require' => '考核场次不存在',
        'uid.require' => '评价人不存在',
        'otherid.require' => '被评人不存在',
        'one.require' => '文化规章不能为空',
        'two.require' => '专业技能不能为空',
        'three.require' => '合格同事不能为空',
    ];
    protected $scene = [
        'add'   =>  ['qid','otherid'],
        'edit'  =>  ['id','answer'],
    ];
}