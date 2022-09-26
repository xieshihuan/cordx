<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
use think\facade\Request;

class Ceshi extends Controller
{
    
    public function index(){
        
        //显示数量
        $pageSize = 10;
        //调取列表
        $list = Db::name('admin_log')
            ->paginate($pageSize,false,['query' => request()->param()]);
            
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $list;
		return json_encode($rs_arr,true);
		exit;
    }

}
