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
use app\common\model\Link as M;

class Link extends Base
{
    protected $validate = 'Link';

    //列表
    public function index(){
        
        $data = Request::post();
        
        $keyword = $data['keyword'];
        $language = $data['language'];
        $typeid = $data['typeid'];
        
        //全局查询条件
        $where=[];
        if(!empty($language)){
           $where[] = ['language','=',$language];
        }else{
            $where[] = ['language','=','1'];
        }
        if(!empty($keyword)){
            $where[]=['name|description', 'like', '%'.$keyword.'%'];
        }
        if(!empty($typeid)){
            $where[] = ['typeid','=',$typeid];
        }else{
            $where[] = ['typeid','=','1'];
        }
        
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        //调取列表
        $list = Db::name('link')
            ->field('*')
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
    public function addPost(){
        $data = Request::param();
        $result = $this->validate($data,$this->validate);
        if (true !== $result) {
            // 验证失败 输出错误信息
            $this->error($result);
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
            // 验证失败 输出错误信息
            $this->error($result);
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

}
