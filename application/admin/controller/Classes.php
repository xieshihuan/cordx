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
use app\common\model\Classes as M;

class Classes extends Base
{
    protected $validate = 'Classes';

    //列表
    public function index(){
        //条件筛选
        $keyword = Request::param('keyword');
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['title', 'like', '%'.$keyword.'%'];
        }
        $where[] = ['is_delete', '=' ,1];
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        //调取列表
        $list = Db::name('classes')
            ->order('id asc')
            ->where($where)
            ->select();
            
            foreach ($list as $key => $v){
                $list[$key]['username'] = Db::name('users')->where('id',$v['uid'])->value('username');
                
              
                $one_in = strtotime($v['one_in']);
                $one_out = strtotime($v['one_out']);
                $two_in = strtotime($v['two_in']);
                $two_out = strtotime($v['two_out']);
                
                if($one_out < $one_in){
                    $list[$key]['one_out_name'] = '次日 '.$v['one_out'];
                    $list[$key]['two_in_name'] = '次日 '.$v['two_in'];
                    $list[$key]['two_out_name'] = '次日 '.$v['two_out'];
                }else if($two_in < $one_out){
                    $list[$key]['one_out_name'] = $v['one_out'];
                    $list[$key]['two_in_name'] = '次日 '.$v['two_in'];
                    $list[$key]['two_out_name'] = '次日 '.$v['two_out'];
                }else if($two_out < $two_in){
                    $list[$key]['one_out_name'] = $v['one_out'];
                    $list[$key]['two_in_name'] = $v['two_in'];
                    $list[$key]['two_out_name'] = '次日 '.$v['two_out'];
                }else{
                    $list[$key]['one_out_name'] = $v['one_out'];
                    $list[$key]['two_in_name'] = $v['two_in'];
                    $list[$key]['two_out_name'] = $v['two_out'];
                }
            
            }
        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;

        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
		return json_encode($rs_arr,true);
		exit;
    }

    //添加保存
    public function addPost(){
        $data = Request::param();
        
        $num = Db::name('classes')->where('title',$data['title'])->where('is_delete',1)->count();
        
        if($num > 0){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '该班次名称已存在';
    		return json_encode($rs_arr,true);
    		exit;           
        }
        
        $m = new M();
        $data['uid'] = $this->admin_id;
        $result =  $m->addPost($data);
        if($result['error']){
            $rs_arr['status'] = 201;
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
        
        $where = [];
        $where[] = ['title','=',$data['title']];
        $where[] = ['id','<>',$data['id']];
        
        $num = Db::name('classes')->where($where)->where('is_delete',1)->count();
        
        if($num > 0){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '该班次名称已存在';
    		return json_encode($rs_arr,true);
    		exit;           
        }
        
        
        $m = new M();
        $result = $m->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 201;
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
        $datas['id'] = $data['id'];
        $datas['is_delete'] = 2;
        $result = $m->editPost($datas);
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
