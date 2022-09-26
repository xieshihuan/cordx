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
use app\common\model\Article as M;

class Article extends Base
{
    protected $validate = 'Article';

    //列表
    public function index(){
        //条件筛选
        $keyword = Request::param('keyword');
        $catid = Request::param('catid');
        $status = Request::param('status');
        $is_tuijian = Request::param('is_tuijian');
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['a.title|a.description', 'like', '%'.$keyword.'%'];
        }
        if(!empty($catid)){
            $where[]=['catid', '=', $catid];
        }   
        if(!empty($status)){
            $where[]=['a.status', '=', $status];
        }  
        if(!empty($is_tuijian)){
            $where[]=['a.is_tuijian', '=', $is_tuijian];
        }   
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $this->view->assign('pageSize', page_size($pageSize));

        //调取列表
        $list = Db::name('article')
            ->alias('a')
            ->leftJoin('cate at','a.catid = at.id')
            ->field('a.*,at.catname as cate_name')
            ->order('a.sort ASC,a.id DESC')
            ->where($where)
            ->paginate($pageSize,false,['query' => request()->param()]);
        
       
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $list;
		return json_encode($rs_arr,true);
		exit;
    }
    
    public function detail(){
        $id = Request::param('id');
        
        if(!empty($id)){
            $where[]=['id', '=', $id];
        }else{
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = 'id不能为空';
    		return json_encode($rs_arr,true);
    		exit;
        }
        
        $ainfo = Db::name('article')->where($where)->find();
        
        if(!empty($ainfo['images'])){
            $alist = explode(',',$ainfo['images']);
            foreach ($alist as $key => $val){
                $wheres['id'] = $val;
                $alist[$key] = Db::name('img')->field('id,thumb')->where($wheres)->find();
                $request = Request::instance();
                $domain = $request->domain();
                $alist[$key]['url'] = $domain.$alist[$key]['thumb'];
            }
            $ainfo['duotu'] = $alist;
        }
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $ainfo;
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
            if( empty($id) ){
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
