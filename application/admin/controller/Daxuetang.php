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
use app\common\model\Daxuetang as M;

class Daxuetang extends Base
{
    protected $validate = 'Daxuetang';

    //列表
    public function index(){

        $id = Request::param('id');
        $tiku_id = Request::param('tiku_id');
        $start = strtotime(Request::param('start'));
        $end = strtotime(Request::param('end'));
        $status = Request::param('status');
        //全局查询条件
        $where=[];
        if(!empty($tiku_id)){
            $where[]=['tiku_id', '=', $tiku_id];
        }

        if(!empty($id)){
            $where[]=['id', '=', $id];
        }

        if(empty($status)){
            if(isset($start)&&$start!=""&&isset($end)&&$end=="")
            {
                $where[] = ['end','>=',$start];
            }
            if(isset($end)&&$end!=""&&isset($start)&&$start=="")
            {
                $where[] = ['start','<=',$end];
            }
            if(isset($start)&&$start!=""&&isset($end)&&$end!="")
            {
                $where[] = ['end','>=',$start];
                $where[] = ['start','<=',$end];
            }
        }else{
            if($status == 1){
                $where[] = ['start','>',time()];
            }else if($status == 3){
                $where[] = ['end','<',time()];
            }else{
                $where[] = ['start','<=',time()];
                $where[] = ['end','>=',time()];
            }
        }

        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        //调取列表
        $list = Db::name('daxuetang')
            ->order('start desc')
            ->where($where)
            ->select();

        foreach ($list as $key => $val){
            if($val['start'] > time()){
                $list[$key]['status'] = 1;
            }else if($val['end'] < time()){
                $list[$key]['status'] = 3;
            }else{
                $list[$key]['status'] = 2;
            }

            $mlist = explode(',',$val['member']);

            $arr = '';
            foreach ($mlist as $k => $v){
                $arr .= Db::name('users')->where('id',$v)->value('username').',';
            }

            $list[$key]['mlist'] = rtrim($arr,',');


            $olist = explode(',',$val['organize']);

            $arrs = '';
            foreach ($olist as $k => $v){
                $arrs .= Db::name('cate')->where('id',$v)->value('title').',';
            }

            $list[$key]['olist'] = rtrim($arrs,',');
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

            $tnum = Db::name('tiku')->where('tiku_id',$data['tiku_id'])->count();
            if($tnum > 0){
                
                $whr['tiku_id'] = $data['tiku_id'];
                $whr['start'] = strtotime($data['start']);
                $whr['end'] = strtotime($data['end']);
                $cnum = Db::name('daxuetang')->where($whr)->count();
                if($cnum > 0){
                    $rs_arr['status'] = 201;
        	        $rs_arr['msg'] = '不能重复添加';
            		return json_encode($rs_arr,true);
            		exit;
                }
                
                $m = new M();
                $data['start'] = strtotime($data['start']);
                $data['end'] = strtotime($data['end']);
                $data['title'] = Db::name('tikus')->where('id',$data['tiku_id'])->value('title');
                
                
                $id = $m->insertGetId($data);
                if($id){
                    $rs_arr['status'] = 200;
                    $rs_arr['msg'] = '添加成功';
                    $rs_arr['data'] = $id;
                    return json_encode($rs_arr,true);
                    exit;
                }else{
                    $rs_arr['status'] = 500;
                    $rs_arr['msg'] = '添加失败';
                    return json_encode($rs_arr,true);
                    exit;
                }
            }else{
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '当前题库无考题';
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
            
            $whr = [];
            $whr[] = ['tiku_id','=',$data['tiku_id']];
            $whr[] = ['id','neq',$data['id']];
            $whr[] = ['start','=',$data['start']];
            $whr[] = ['end','=',$data['end']];
            $cnum = Db::name('daxuetang')->where($whr)->count();
            if($cnum > 0){
                $rs_arr['status'] = 201;
    	        $rs_arr['msg'] = '不能重复添加';
        		return json_encode($rs_arr,true);
        		exit;
            }
                
            $m = new M();
            $data['start'] = strtotime($data['start']);
            $data['end'] = strtotime($data['end']);
            $data['title'] = Db::name('tikus')->where('id',$data['tiku_id'])->value('title');
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


    //发布显示权限
    public function Organize(){
        $zrules = authss();
        $rules = Db::name('daxuetang')
            ->where('id',Request::param('id'))
            ->value('organize');

        $list['zrules'] = $zrules;
        $list['checkIds'] = $rules;

        $data_rt['status'] = 200;
        $data_rt['msg'] = '获取成功';
        $data_rt['data'] = $list;
        return json_encode($data_rt,true);
        die;

    }
    //全员非全缘
    public function Setrestrict(){
        $is_restrict = Request::post('is_restrict');
        $id = Request::post('id');
        if(!empty($is_restrict)){

            $where['id'] = $id;
            $data['is_restrict'] = $is_restrict;
            if(M::update($data,$where)){
                $data_rt['status'] = 200;
                $data_rt['msg'] = '修改成功';
                return json_encode($data_rt,true);
                die;
            }else{
                $data_rt['status'] = 201;
                $data_rt['msg'] = '修改失败';
                return json_encode($data_rt,true);
                die;
            }

        }else{
            $data_rt['status'] = 201;
            $data_rt['msg'] = '请选择类别';
            return json_encode($data_rt,true);
            die;
        }

    }

    //发布保存权限
    public function Setorganize(){
        $organize = Request::post('organize');

        $data = Request::post();
        $where['id'] = $data['id'];
        if(M::update($data,$where)){
            $data_rt['status'] = 200;
            $data_rt['msg'] = '添加成功';
            return json_encode($data_rt,true);
            die;
        }else{
            $data_rt['status'] = 201;
            $data_rt['msg'] = '添加失败';
            return json_encode($data_rt,true);
            die;
        }

    }

    //发布显示权限
    public function Member(){

        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;

        $member = Db::name('daxuetang')
            ->where('id',Request::param('id'))
            ->value('member');

        $whr = [];
        $whr[] = ['id','in',$member];
        $memberlist = Db::name('users')->field('id,username,mobile')->where($whr)->select();

        $members = explode(',',$member);

        foreach ($memberlist as $key => $val){

            $whra['uid'] = $val['id'];
            $whra['leixing'] = 1;
            $clist = Db::name('cateuser')
                ->where($whra)
                ->select();
            if($clist){
                foreach ($clist as $keys => $vals){
                    $group_name = self::select_name($vals['catid']);
                    $arr = explode('/',$group_name);
                    $arrs = array_reverse($arr);
                    $group_list = implode('/',$arrs);
                    $group_list = ltrim($group_list,'/');
                    $clist[$keys]['group_name'] = $group_list;
                }
            }else{
                $clist[$keys]['group_name'] = '';
            }

            $memberlist[$key]['clist'] = $clist;
        }

        $data_rt['total'] = count($memberlist);
        $memberlist = array_slice($memberlist,$b,$pageSize);
        $data_rt['data'] = $memberlist;

        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $data_rt;
        return json_encode($rs_arr,true);
        exit;

    }

    public function select_name($id){

        $str = '';
        $whr['id'] = $id;
        $info = Db::name('cate')->where($whr)->find();
        $str .= $info['title'].'/';

        if($id != 1 && $id != 0){
            $str .= self::select_name($info['parentid']);
        }else{
            $str .= '';
        }

        return $str;
    }

    //发布保存权限
    public function Setmember(){

        $id = Request::param('id');
        $uid = Request::param('uid');
        $type = Request::param('type');

        $member = Db::name('daxuetang')
            ->where('id',Request::param('id'))
            ->value('member');

        $members = explode(',',$member);

        if($type == 1){

            //添加
            if(in_array($uid,$members)){
                echo apireturn(201,'您已经插入','');
                die;
            }else{
                //添加
                $data['member'] = trim($member.','.$uid,',');
                $where['id'] = $id;
                if(M::update($data,$where)){
                    $data_rt['status'] = 200;
                    $data_rt['msg'] = '添加成功';
                    return json_encode($data_rt,true);
                    die;
                }else{
                    $data_rt['status'] = 201;
                    $data_rt['msg'] = '添加失败';
                    return json_encode($data_rt,true);
                    die;
                }
            }
        }else{
            //移除
            if(in_array($uid,$members)){
                //修改
                $memberss = array_diff($members,[$uid]);
                $data['member'] = implode(',',$memberss);
                $where['id'] = $id;
                if(M::update($data,$where)){
                    $data_rt['status'] = 200;
                    $data_rt['msg'] = '修改成功';
                    return json_encode($data_rt,true);
                    die;
                }else{
                    $data_rt['status'] = 201;
                    $data_rt['msg'] = '修改失败';
                    return json_encode($data_rt,true);
                    die;
                }
            }else{
                echo apireturn(201,'您不在当前组织','');
                die;
            }
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
