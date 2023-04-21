<?php
/**
 * +----------------------------------------------------------------------
 * | 新闻管理控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\FlowList as M;

class FlowList extends Base
{
    protected $validate = 'FlowList';
    
    //列表
    public function index(){
        //条件筛选
        $keyword = Request::param('keyword');
        $flow_type = Request::param('flow_type');
        $status = Request::param('status');
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['fl.title|u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        if(!empty($flow_type)){
            $where[]=['fl.flow_type', '=', $flow_type];
        }
        if(!empty($status)){
            $where[]=['fl.status', '=', $status];
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
       
        //调取列表
        $list = Db::name('flow_list')
            ->alias('fl')
            ->leftJoin('users u','fl.create_uid = u.id')
            ->order('id desc')
            ->where($where)
            ->paginate($pageSize,false,['query' => request()->param()]);

        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $list;
		return json_encode($rs_arr,true);
		exit;
    }

    //添加保存
    public function addPost(){
        $data = Request::param();

 
        $m = new M();
        $result =  $m->addPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
        
    }

    //修改保存
    public function editPost(){
        $data = Request::param();

    
        $m = new M();
        $result = $m->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
    
    }

    //删除
    public function del(){
        $data = Request::param();
        
        $m = new M();
        $result = $m->del($data['id']);
        if($result['error']){
            $rs_arr['status'] = 500;
            $rs_arr['msg'] = $result['msg'];
            return json_encode($rs_arr,true);
            exit;
        }else{
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = $result['msg'];
            return json_encode($rs_arr,true);
            exit;
        }
        
    }
}
