<?php
namespace app\admin\controller;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\AttendanceGroup as M;

class AttendanceGroup extends Base
{
    protected $validate = 'AttendanceGroup';

    //列表
    public function index(){
        //条件筛选
        $keyword = Request::param('keyword');
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['title', 'like', '%'.$keyword.'%'];
            
            //查询员工
            $whra[]=['username', 'like', '%'.$keyword.'%'];
            $uuid= Db::name('users')->where($whra)->value('id');
           
            if($uuid){
                $where1[] = ['','exp',Db::raw("FIND_IN_SET($uuid,member)")];
            }
            //查询部门
            $whrb[]=['title', 'like', '%'.$keyword.'%'];
            $mmid= Db::name('cate')->where($whrb)->value('id');
            if($mmid){
                $where2[]=['','EXP',Db::raw("FIND_IN_SET($mmid,organize)")];
            }
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;
        
        $a = $page-1;
        $b = $a * $pageSize;
        //调取列表
        if(!empty($uuid)){
            $list = Db::name('attendance_group')
            ->order('id asc')
            ->where($where)
            ->whereOr($where1)
            ->where('is_delete',1)
            ->select();
        }else if(!empty($mmid)){
            $list = Db::name('attendance_group')
            ->order('id asc')
            ->where($where)
            ->whereOr($where2)
            ->where('is_delete',1)
            ->select();
        }else{
            $list = Db::name('attendance_group')
            ->order('id asc')
            ->where($where)
            ->where('is_delete',1)
            ->select();
        }
        
        
        $name = '';
        $sj = '';
        foreach ($list as $key => $val){
            
            $classids = explode(',',$val['classes_ids']);
            
            $lists = Db::name('classes')
            ->field('title,one_in,one_out,two_in,two_out,commuting_num')
            ->where('id','In',$classids)
            ->select();
            
            foreach($lists as $k => $v){
                
                $one_in = strtotime($v['one_in']);
                $one_out = strtotime($v['one_out']);
                $two_in = strtotime($v['two_in']);
                $two_out = strtotime($v['two_out']);
                
                if($one_out < $one_in){
                    $lists[$k]['one_out_name'] = '次日 '.$v['one_out'];
                    $lists[$k]['two_in_name'] = '次日 '.$v['two_in'];
                    $lists[$k]['two_out_name'] = '次日 '.$v['two_out'];
                }else if($two_in < $one_out){
                    $lists[$k]['one_out_name'] = $v['one_out'];
                    $lists[$k]['two_in_name'] = '次日 '.$v['two_in'];
                    $lists[$k]['two_out_name'] = '次日 '.$v['two_out'];
                }else if($two_out < $two_in){
                    $lists[$k]['one_out_name'] = $v['one_out'];
                    $lists[$k]['two_in_name'] = $v['two_in'];
                    $lists[$k]['two_out_name'] = '次日 '.$v['two_out'];
                }else{
                    $lists[$k]['one_out_name'] = $v['one_out'];
                    $lists[$k]['two_in_name'] = $v['two_in'];
                    $lists[$k]['two_out_name'] = $v['two_out'];
                }
            }
            
            $list[$key]['classinfo'] = $lists;
            
            $list[$key]['number'] = Db::name('attendance_group_user')
            ->where('attendance_group_id','=',$val['id'])
            ->where('status',1)
            ->count();
            
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
        
        $num = Db::name('attendance_group')->where('title',$data['title'])->where('is_delete',1)->count();
        
        if($num > 0){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '该考勤组名称已存在';
    		return json_encode($rs_arr,true);
    		exit;           
        }
        
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

        
        $where = [];
        $where[] = ['title','=',$data['title']];
        $where[] = ['is_delete','=',1];
        $where[] = ['id','<>',$data['id']];
        $num = Db::name('attendance_group')->where($where)->count();
        
        if($num > 0){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '该考勤组名称已存在';
    		return json_encode($rs_arr,true);
    		exit;           
        }
        
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

        $validate = new \app\common\validate\AttendanceGroup;
        if (!$validate->scene('del')->check($data)) {
            // 验证失败 输出错误信息
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = $validate->getError();
            return json_encode($rs_arr,true);
            exit;
        }else{
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
                Db::name('attendance_group_user')->where('attendance_group_id',$data['id'])->delete();
                
                $rs_arr['status'] = 200;
                $rs_arr['msg'] = $result['msg'];
                return json_encode($rs_arr,true);
                exit;
            }
        }
    }
    
    
    public function Organize(){
        
        $keyword = Request::param('keyword');
        
        $zrules = authss();
        $rules = Db::name('attendance_group')
            ->where('id',Request::param('id'))
            ->value('organize');

        $list['zrules'] = $zrules;
        $list['checkIds'] = $rules;
        
        $where=[];
        
        $where[] = ['attendance_group_id','=',Request::param('id')];
        $where[] = ['leixing','=',1];
        
        $wheres=[];
        if(!empty($keyword)){
            $wheres[]=['username|mobile', 'like', '%'.$keyword.'%'];
            $uuid = Db::name('users')->field('id')->where($wheres)->buildSql(true);
            $ulist = Db::name('attendance_group_user')->where($where)->where('uid','exp','In '.$uuid)->select();
        }else{
            $ulist = Db::name('attendance_group_user')->where($where)->select();
        }
        foreach ($ulist as $key => $val){
            $ulist[$key]['username'] = Db::name('users')->where('id',$val['uid'])->value('username');
            $ulist[$key]['mobile'] = Db::name('users')->where('id',$val['uid'])->value('mobile');
        }
        $list['ulist'] = $ulist;

        $data_rt['status'] = 200;
        $data_rt['msg'] = '获取成功';
        $data_rt['data'] = $list;
        return json_encode($data_rt,true);
        die;

    }
    
    public function organize_status(){
        
        $data = Request::param();
        $info = Db::name('attendance_group_user')->where('id',$data['id'])->find();
        if($info){
            if($info['status'] == 1){
                //设置为排除
                //$upd['status']=2;
                Db::name('attendance_group_user')->delete($data['id']);
                echo apireturns(200,200,'success','');
                die;
            }
        }else{
            echo apireturns(200,201,'该数据不存在','');
            die;
        }
        
        
    }
    
    
    public function organize_status——bf(){
        
        $data = Request::param();
        $info = Db::name('attendance_group_user')->where('id',$data['id'])->find();
        if($info){
            if($info['status'] == 1){
                //设置为排除
                $upd['status']=2;
                Db::name('attendance_group_user')->where('id',$data['id'])->update($upd);
                echo apireturns(200,200,'success','');
                die;
            }else{
                //查询其他考勤组有无此人
                $whr2=[];
                $whr2[] = ['uid','=',$info['uid']];
                $whr2[] = ['attendance_group_id','<>',$data['id']];
                $whr2[] = ['status','=',1];
                $atinfo = Db::name('attendance_group_user')->where($whr2)->find();
                if($atinfo){
                    $username = Db::name('users')->where('id',$info['uid'])->value('username');
                    $attname = Db::name('attendance_group')->where('id',$info['attendance_group_id'])->value('title');
                    echo apireturns(200,201,'冲突人员：'.$username.'目前在'.$attname,'');
                    die;
                }else{
                    //取消排除
                    $upd['status']=1;
                    Db::name('attendance_group_user')->where('id',$data['id'])->update($upd);
                    echo apireturns(200,200,'success','');
                    die;
                }
            }
        }else{
            echo apireturns(200,201,'该数据不存在','');
            die;
        }
        
        
    }
    
    //发布保存权限
    public function Setorganize(){
        
        $data = Request::post();
        $where['id'] = $data['id'];
        $organize = $data['organize'];
        
        $organizes = explode(',',$organize);
      
        if(!empty($organize)){
            $whr1 = [];
            $whr1[] = ['catid','in',$organize];
            $ctlist = Db::name('cateuser')->where($whr1)->select();
            
            $ctname = '';
            foreach ($ctlist as $k1 => $v1){
            
                $whr2 = [];
                $whr2[] = ['uid','=',$v1['uid']];
                $whr2[] = ['attendance_group_id','<>',$data['id']];
                $whr2[] = ['status','=',1];
                $atinfo = Db::name('attendance_group_user')->where($whr2)->find();
                if($atinfo){
                    //查询是否排除
                    $whrp = [];
                    $whrp[] = ['uid','=',$v1['uid']];
                    $whrp[] = ['attendance_group_id','=',$data['id']];
                    $whrp[] = ['status','=',2];
                    $num = Db::name('attendance_group_user')->where($whrp)->count();
                    if($num == 0){
                        //有冲突 查姓名
                        $zdname = Db::name('attendance_group')->where('id',$atinfo['attendance_group_id'])->value('title');
                        $username = Db::name('users')->where('id',$v1['uid'])->value('username');
                        $ctname = $zdname.'-'.$username.' '.$ctname;
                    }
                }
            
            }
            
            if(!empty($ctname)){
                echo apireturns(200,201,'冲突人员：'.$ctname,'');
                die;
            }else{
                //先删除所有站点添加的人员
                Db::name('attendance_group_user')->where('attendance_group_id',$data['id'])->where('leixing',1)->where('status',1)->delete();
                
                foreach ($ctlist as $k2 => $v2){
                    
                    $whr_add['uid'] = $v2['uid'];
                    $whr_add['attendance_group_id'] = $data['id'];
                    $dqkqz = Db::name('attendance_group_user')->where($whr_add)->count();
                    if($dqkqz == 0){
                        $data_add['uid'] = $v2['uid'];
                        $data_add['attendance_group_id'] = $data['id'];
                        $data_add['leixing'] = 1;
                        $data_add['status'] = 1;
                        Db::name('attendance_group_user')->insert($data_add);
                    }
                    
                }
                if(M::update($data,$where)){
                    echo apireturns(200,200,'添加成功','');
                    die;
                }else{
                    echo apireturns(200,201,'添加失败','');
                    die;
                }
            }
            
            
        }else{
            //如果为空 需要将所有站的人员移除 不包含
            $whrd = [];
            $whrd[] = ['catid','in',$organize];
            
            //删除所有站点添加的人员
            Db::name('attendance_group_user')->where('attendance_group_id',$data['id'])->where('leixing',1)->delete();
            
            if(M::update($data,$where)){
                echo apireturns(200,200,'移除成功','');
                die;
            }else{
                echo apireturns(200,201,'移除失败','');
                die;
            }
        }
        
        
        

    }

    //发布显示权限
    public function Member(){

        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;

        $member = Db::name('attendance_group')
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
                $clist[0]['group_name'] = '';
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

        $member = Db::name('attendance_group')
            ->where('id',Request::param('id'))
            ->value('member');

        $members = explode(',',$member);

        if($type == 1){

            //添加
            if(in_array($uid,$members)){
                echo apireturn(201,'您已在当前考勤组','');
                die;
            }else{
                
                $whr['uid'] = $uid;
                $whr['status'] = 1;
                $info = Db::name('attendance_group_user')->where($whr)->find();
                if($info){
                    $username = Db::name('users')->where('id',$uid)->value('username');
                    $attname = Db::name('attendance_group')->where('id',$info['attendance_group_id'])->value('title');
                    echo apireturn(201,$username.'已在'.$attname.'考勤组','');
                    die;
                }
                //更新关联表
                $dataz['attendance_group_id'] = $id;
                $dataz['uid'] = $uid;
                $dataz['leixing'] = 2;
                $dataz['status'] = 1;
                Db::name('attendance_group_user')->insert($dataz);
                
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
                //更新关联表
                $whrz['attendance_group_id'] = $id;
                $whrz['uid'] = $uid;
                $whrz['leixing'] = 2;
                Db::name('attendance_group_user')->where($whrz)->delete();
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

}
