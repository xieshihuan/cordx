<?php
/**
 * +----------------------------------------------------------------------
 * | 友情链接控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use think\facade\Request;
use think\Db;

//实例化默认模型
use app\common\model\Message as M;

class Message extends Base
{
    protected $validate = 'Message';

    //列表
    public function index(){
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        //调取列表
      
        $list = Db::name('message')
            ->field('*')
            ->order('sort ASC,id DESC')
            ->paginate($pageSize,false,['query' => request()->param()]);
            
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $list;
		return json_encode($rs_arr,true);
		exit;
    }


}
