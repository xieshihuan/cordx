<?php
/**
 * +----------------------------------------------------------------------
 * | 广告管理控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use app\common\model\AdType;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\Ad as M;

class Ad extends Base
{
    protected $validate = 'Ad';

    //列表
    public function index(){
        
        $data = Request::param();
        
        $keyword = Request::param('keyword');
        $type_id = Request::param('type_id');
        $type = Request::param('type');
        
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['a.name|a.description', 'like', '%'.$keyword.'%'];
        }
        if(!empty($type_id)){
            $where[]=['a.type_id', '=', $type_id];
        } 
        if(!empty($type)){
            $where[]=['a.type', '=', $type];
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        //调取列表
        $list = Db::name('ad')
            ->alias('a')
            ->leftJoin('ad_type at','a.type_id = at.id')
            ->field('a.*,at.name as type_name')
            ->order('a.sort ASC,a.id DESC')
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
        $result = $this->validate($data,$this->validate);
        if (true !== $result) {
            // 验证失败 输出错误信息
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = $result;
    		return json_encode($rs_arr,true);
    		exit;
        }else{
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
    }


    //修改保存
    public function editPost(){
        $data = Request::param();
        $result = $this->validate($data,$this->validate);
        if (true !== $result) {
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = $result;
    		return json_encode($rs_arr,true);
    		exit;
        }else{
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
    }

    //删除
    public function del(){
        if(Request::isPost()) {
            $id = Request::post('id');
            if(empty($id)){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = 'ID不存在';
        		return json_encode($rs_arr,true);
        		exit;
            }
            
            $m = new M();
            $m->del($id);
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] ='success';
    		return json_encode($rs_arr,true);
    		exit;
        }
    }

    //批量删除
    // public function selectDel(){
    //     if(Request::isPost()) {
    //         $id = Request::post('id');
    //         if (empty($id)) {
    //             return ['error'=>1,'msg'=>'ID不存在'];
    //         }
    //         $m = new M();
    //         return $m->selectDel($id);
    //     }
    // }

    //排序
    // public function sort(){
    //     if(Request::isPost()){
    //         $data = Request::param();
    //         if (empty($data['id'])){
    //             return ['error'=>1,'msg'=>'ID不存在'];
    //         }
    //         $m = new M();
    //         return $m->sort($data);
    //     }
    // }

    //状态
    // public function state(){
    //     if(Request::isPost()){
    //         $id = Request::post('id');
    //         if (empty($id)){
    //             return ['error'=>1,'msg'=>'ID不存在'];
    //         }
    //         $m = new M();
    //         return $m->state($id);
    //     }
    // }

}
