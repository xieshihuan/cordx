<?php
namespace app\admin\controller;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\Tiku as M;

class Tiku extends Base
{
    protected $validate = 'Tiku';

    //列表
    public function index(){
        
        $data = Request::param();
        
        $type_id = $data['type_id'];
        $tiku_id = $data['tiku_id'];
        $question = $data['question'];
        
        //全局查询条件
        $where=[];
        if(!empty($type_id)){
            $where[]=['type_id', '=', $type_id];
        }
        if(!empty($tiku_id)){
            $where[]=['tiku_id', '=', $tiku_id];
        }
        if(!empty($question)){
            $where[]=['question', 'like', '%'.$question.'%'];
        }
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;
        $a = $page-1;
        $b = $a * $pageSize;

        //调取列表
        $list = Db::name('tiku')
            ->order('sort ASC,id ASC')
            ->where($where)
            ->select();
        foreach ($list as $key => $val){
            $list[$key]['num'] = count(explode("\n",$val['z_result']));
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
            // 验证失败 输出错误信息
            $rs_arr['status'] = 201;
	        $rs_arr['msg'] = $result;
    		return json_encode($rs_arr,true);
    		exit;
        }else{

            $whr = [];
            $whr[] = ['start','lt',time()];
            $whr[] = ['end','gt',time()];
            $whr[] = ['tiku_id','=', $data['tiku_id']];
            $wnum = Db::name('daxuetang')->where($whr)->count();
            if($wnum > 0) {
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '题库考核中，无法操作';
                return json_encode($rs_arr,true);
                exit;

            }else{
                $whr1[] = ['question','=',$data['question']];
                $whr1[] = ['tiku_id','=',$data['tiku_id']];
                $num = Db::name('tiku')->where($whr1)->count();
                if($num > 0){
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = '问题重复';
                    return json_encode($rs_arr,true);
                    exit;
                }

                $m = new M();
                $type_id = Db::name('ti_type')->where('name',$data['type_name'])->value('id');
                $data['type_id'] = $type_id;
                $result =  $m->save($data);
                if($result['error']){
                    $rs_arr['status'] = 500;
                    $rs_arr['msg'] = $result['msg'];
                    return json_encode($rs_arr,true);
                    exit;
                }else{
                    $rs_arr['status'] = 200;
                    $rs_arr['msg'] = 'success';
                    return json_encode($rs_arr,true);
                    exit;
                }
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

            $whr = [];
            $whr[] = ['start','lt',time()];
            $whr[] = ['end','gt',time()];
            $whr[] = ['tiku_id','=', $data['tiku_id']];
            $wnum = Db::name('daxuetang')->where($whr)->count();
            if($wnum > 0) {
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '题库考核中，无法操作';
                return json_encode($rs_arr,true);
                exit;

            }else{
                $whr1[] = ['question','=',$data['question']];
                $whr1[] = ['tiku_id','=',$data['tiku_id']];
                $whr1[] = ['id','neq',$data['id']];
                $num = Db::name('tiku')->where($whr1)->count();
                if($num > 0){
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = '问题重复';
                    return json_encode($rs_arr,true);
                    exit;
                }

                $m = new M();
                $data['type_id'] = Db::name('ti_type')->where('name',$data['type_name'])->value('id');
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
            }else{
                $data = Db::name('tiku')->where('id',$id)->find();
            }

            $whr = [];
            $whr[] = ['start','lt',time()];
            $whr[] = ['end','gt',time()];
            $whr[] = ['tiku_id','=', $data['tiku_id']];
            $wnum = Db::name('daxuetang')->where($whr)->count();
            if($wnum > 0) {
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '题库考核中，无法操作';
                return json_encode($rs_arr,true);
                exit;
            }else{
                $m = new M();
                $m->del($id);

                $rs_arr['status'] = 200;
                $rs_arr['msg'] ='success';
                return json_encode($rs_arr,true);
                exit;
            }
        }
    }
    
    public function copyPost(){
        $tiku_id = Request::post('tiku_id');
        $oldid = Request::post('oldid');
        if(empty($tiku_id)){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '题库ID不存在';
    		return json_encode($rs_arr,true);
    		exit;
        }
        if(empty($oldid)){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '旧ID不存在';
    		return json_encode($rs_arr,true);
    		exit;
        }
        
        $list = Db::name('tiku')->where('tiku_id',$oldid)->select();
        foreach($list as $key => $val){
            $data['tiku_id'] = $tiku_id;
            $data['type_id'] = $val['type_id'];
            $data['type_name'] = $val['type_name'];
            $data['sort'] = $val['sort'];
            $data['question'] = $val['question'];
            $data['result'] = $val['result'];
            $data['z_result'] = $val['z_result'];
            $data['score'] = $val['score'];
            $data['answers'] = $val['answers'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            Db::name('tiku')->insert($data);
        }
        
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = '复制成功';
		return json_encode($rs_arr,true);
		exit;
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
