<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
use think\facade\Request;


//实例化默认模型
use app\common\model\Template as T;

class Template extends Base
{
    
    protected $validate = 'Template';
    
    
     //权限列表
    //列表
    public function index(){
        //条件筛选
        $parentid = Request::param('parentid');
        $keyword = Request::param('keyword');
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['title', 'like', '%'.$keyword.'%'];
        }
        if(!empty($parentid)){
            $where[]=['parentid', '=', $parentid];
        }else{
            $where[]=['parentid', '=', 0];
        }
        
         $where[]=['is_delete', '=', 1];
         
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $this->view->assign('pageSize', page_size($pageSize));

        //调取列表
        $list = Db::name('template')
            ->order('sort ASC,id DESC')
            ->where($where)
            ->paginate($pageSize,false,['query' => request()->param()]);
        
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $list;
		return json_encode($rs_arr,true);
		exit;
    }

    
    //添加保存
    public function addPosts(){
        
        if(Request::isPost()){
            $data = Request::except('file');
            
            $result = $this->validate($data,$this->validate);
            if (true !== $result) {
                // 验证失败 输出错误信息
                $data_rt['status'] = 500;
                $data_rt['msg'] = $result;
                return json_encode($data_rt);
                exit;
            }else{
                $data['create_time'] = time();
                $data['update_time'] = time();
                $result = T::create($data);
                if($result->id){
                    $data_rt['status'] = 200;
                    $data_rt['msg'] = '添加成功';
                    return json_encode($data_rt);
                    exit;
                }else{
                    $data_rt['status'] = 500;
                    $data_rt['msg'] = '添加失败';
                    return json_encode($data_rt);
                    exit;
                }
            }
        }
    }

    //修改保存
    public function editPosts(){
        if(Request::isPost()) {
            $data = Request::except('file');
            
            $data['update_time'] = time();
            $result = T::where('id' ,'=', $data['id'])
                ->update($data);
        
            $data_rt['status'] = 200;
            $data_rt['msg'] = '修改成功';
            return json_encode($data_rt);
            exit;
        
        }
    }
    
    //删除
    public function dels(){
        if(Request::isPost()) {
            $id = Request::post('id');
            if(empty($id) ){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = 'ID不存在';
        		return json_encode($rs_arr,true);
        		exit;
            }
            
            $data['is_delete'] = 2;
            $result = T::where('id' ,'=', $id)
                ->update($data);
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] ='success';
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
}
