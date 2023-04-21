<?php
/**
 * +----------------------------------------------------------------------
 * | 新闻管理控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use app\common\model\Cate;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\Flow as M;

class Flow extends Base
{
    protected $validate = 'Flow';
    
    //列表
    public function index(){
        //条件筛选
        $keyword = Request::param('keyword');
        $cate_id = Request::param('cate_id');
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['title', 'like', '%'.$keyword.'%'];
        }
        if(!empty($cate_id)){
            $where[]=['cate_id', '=', $cate_id];
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : config('page');
        
        $a = $pageSize*($page-1);
        
        
        $count = Db::name('flow')
            ->order('sort asc,id asc')
            ->where($where)
            ->count();
        //调取列表
        $list = Db::name('flow')
            ->order('sort asc,id asc')
            ->where($where)
            ->limit($a.','.$pageSize)
            ->select();
        
        
        foreach ($list as $key => $val){
            $ulist = Db::name('users')->where('id','in',$val['flow_uid'])->select();
            $uname = '';
            foreach($ulist as $keys => $vals){
                $uname.= $vals['username'].',';
            }
            $list[$key]['flow_name'] = rtrim($uname,',');
        }
        
        $rlist['count'] = $count;
        $rlist['data'] = $list;
          
         
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $rlist;
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
