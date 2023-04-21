<?php
/**
 * +----------------------------------------------------------------------
 * | 广告管理控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\Tikus as M;

class Tikus extends Base
{
    protected $validate = 'Tikus';

    //列表
    public function index(){
        
        $data = Request::param();
        
        $title = $data['title'];
        $language = $data['language'];
        $start = strtotime($data['start']);
        $end = strtotime($data['end']);
        
        //全局查询条件
        $where=[];
        if(!empty($title)){
            $where[]=['title', 'like', '%'.$title.'%'];
        }
        if(!empty($language)){
            $where[]=['language', '=', $language];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end=="")
        {
            $where[] = ['create_time','>=',$start];
        }
        if(isset($end)&&$end!=""&&isset($start)&&$start=="")
        {
            $where[] = ['create_time','<=',$end];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end!="")
        {
            $where[] = ['create_time','between',[$start,$end]];
        }
        
        $where[] = ['is_delete','=',0];
        
        
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        //调取列表
        $list = Db::name('tikus')
            ->order('sort ASC,id ASC')
            ->where($where)
            ->select();
        foreach ($list as $key => $val){
            $list[$key]['number'] = Db::name('tiku')->where('tiku_id',$val['id'])->count();
            $list[$key]['mscore'] = Db::name('tiku')->where('tiku_id',$val['id'])->sum('score');
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
        $result = $this->validate($data,$this->validate);
        if (true !== $result) {
            //验证失败 输出错误信息
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = $result;
            return json_encode($rs_arr,true);
            exit;
        }else{
            
            // $num = Db::name('tikus')->where('title',$data['title'])->where('is_delete',0)->count();
            // if($num > 0){
            //     $rs_arr['status'] = 201;
            //     $rs_arr['msg'] = '题库已存在，请重新创建';
            //     return json_encode($rs_arr,true);
            //     exit;
            // }
            $m = new M();
            $result =  $m->save($data);
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
            
            // $whr = [];
            // $whr[] = ['id','neq',$data['id']];
            // $num = Db::name('tikus')->where('title',$data['title'])->where('is_delete',0)->where($whr)->count();
            // if($num > 0){
            //     $rs_arr['status'] = 201;
            //     $rs_arr['msg'] = '题库已存在，请重新创建';
            //     return json_encode($rs_arr,true);
            //     exit;
            // }
            
            $whr = [];
            $whr[] = ['start','lt',time()];
            $whr[] = ['end','gt',time()];
            $whr[] = ['tiku_id','=', $data['id']];
            $wnum = Db::name('daxuetang')->where($whr)->count();
            if($wnum == 0){
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
            }else{
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '题库考核中，无法操作';
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
            $whr = [];
            $whr[] = ['start','lt',time()];
            $whr[] = ['end','gt',time()];
            $whr[] = ['tiku_id','=', $id];
            $wnum = Db::name('daxuetang')->where($whr)->count();
            if($wnum == 0) {
                $m = new M();
                $data['id'] = $id;
                $data['is_delete'] = 1;
                $result = $m->editPost($data);
                
                $rs_arr['status'] = 200;
                $rs_arr['msg'] = 'success';
                return json_encode($rs_arr, true);
                exit;
            }else{
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '题库考核中，无法操作';
                return json_encode($rs_arr,true);
                exit;
            }
        }
    }

    public function is_kaohe(){
        $data = Request::param();
        
        $whr = [];
        $whr[] = ['start','lt',time()];
        $whr[] = ['end','gt',time()];
        $whr[] = ['tiku_id','=', $data['id']];
        $wnum = Db::name('daxuetang')->where($whr)->count();
        if($wnum == 0){
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = 'success';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '题库考核中，无法操作';
            return json_encode($rs_arr,true);
            exit;
        }
    }
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


}
