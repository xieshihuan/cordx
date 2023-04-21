<?php
namespace app\api\controller;
use think\Db;
use think\facade\Request;
use think\facade\Env;

class Flow extends Base
{
    
    //获取架构
    public function jiagou(){

        $parentid = input('parentid');
  
        $where=[];
        if($parentid){
            $where[]=['parentid', '=', $parentid];
        }else{
            $where[] = ['parentid','=','1'];
        }
       
        $list = Db::name('cate')->where($where)->order('sort asc')->select();

        foreach ($list as $key => $val){
            $wheres['parentid'] = $val['id'];
            $zzlist = Db::name('cate')->where($wheres)->select();

            $list[$key]['friend'] = count($zzlist);
            
            $wheress['id'] = $val['parentid'];
            $parentids = Db::name('cate')->where($wheress)->value('parentid');
            if($parentids){
                $list[$key]['parentids'] = $parentids;
            }else{
                $list[$key]['parentids'] = 1;
            }
        }

        $data_rt['status'] = 200;
        $data_rt['msg'] = 'success';
        $data_rt['data'] = $list;
        //print_r($list);
        return json_encode($data_rt);
        exit;

    }
    
    
    //查询站点名称
    public function catename(){
        $id = Request::param('id');
        
        $where = [];
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请输入id';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $where[] = ['id','=',$id];
        }

        $title = Db::name('cate')
            ->where($where)
            ->value('title');
            
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $title;
        return json_encode($rs_arr,true);
        exit;
    }
    
    //获取用户列表
    public function userlist(){
        //条件筛选
        $keyword = Request::param('keyword');
        $uid = Request::param('uid');

        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        
        if(!empty($uid)){
            $where[]=['u.id', 'not in', $uid];
        }
        
        
        $where[]=['u.id', '>', '1'];
        $where[]=['u.is_delete', '=', '1'];
       
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : config('page');
        
        $a = $pageSize*($page-1);
        
        $count = Db::name('users')
            ->alias('u')
            ->leftJoin('cateuser cu','cu.uid = u.id')
            ->leftJoin('cate c','c.id = u.id')
            ->field('u.id,u.username,u.mobile')
            ->order('u.id ASC')
            ->group('u.id')
            ->where($where)
            ->count();
        
        //调取列表
        $list = Db::name('users')
            ->alias('u')
            ->field('u.id,u.username,u.mobile')
            ->order('u.id ASC')
            ->limit($a.','.$pageSize)
            ->group('u.id')
            ->where($where)
            ->select();
        

        foreach ($list as $key => $val){
            $list[$key]['mobile'] = substr_replace($val['mobile'],'****',-8,-4);
        }
          
        $rlist['count'] = $count;
        $rlist['data'] = $list;
          
         
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $rlist;
		return json_encode($rs_arr,true);
		exit;
    }
    
    //获取班次
    public function classes(){
        $info = Db::name('attendance_group_user')->where('uid',$this->user_id)->where('status',1)->find();
        //判断有无考勤组
        if($info){
            $classids = Db::name('attendance_group')->where('id',$info['attendance_group_id'])->value('classes_ids');
            if(!empty($classids)){
                $list = Db::name('classes')->where('id','in',$classids)->select();
                foreach($list as $key => $v){
                    $one_in = strtotime($v['one_in']);
                    $one_out = strtotime($v['one_out']);
                    $two_in = strtotime($v['two_in']);
                    $two_out = strtotime($v['two_out']);
                    
                    if($one_out < $one_in){
                        $one_out_name = '次日 '.$v['one_out'];
                        $two_in_name = '次日 '.$v['two_in'];
                        $two_out_name = '次日 '.$v['two_out'];
                    }else if($two_in < $one_out){
                        $one_out_name = $v['one_out'];
                        $two_in_name = '次日 '.$v['two_in'];
                        $two_out_name = '次日 '.$v['two_out'];
                    }else if($two_out < $two_in){
                        $one_out_name = $v['one_out'];
                        $two_in_name = $v['two_in'];
                        $two_out_name = '次日 '.$v['two_out'];
                    }else{
                        $one_out_name = $v['one_out'];
                        $two_in_name = $v['two_in'];
                        $two_out_name = $v['two_out'];
                    }
                    
                    if($v['commuting_num'] == 1){
                        $list[$key]['text'] = $v['title'].' '.$v['one_in'].$one_out_name;
                    }else{
                        
                        $list[$key]['text'] = $v['title'].' '.$v['one_in'].'-'.$one_out_name.' '.$two_in_name.'-'.$two_out_name;
                    }
                    
                }
                $data['classeslist'] = $list;
                
                echo apireturn(200,'success',$data);die;
            }else{
                echo apireturn(201,'当前考勤组无配置班次','');die;
            }
        }else{
            echo apireturn(201,'请联系管理员设置考勤组','');die;
        }
    }
    
    //获取审批列表
    public function get_list(){
        
    
        $whr = [];
        $whr[] = ['status','=',1];
        $list = Db::name('flow_cate')->where($whr)->select();
        
        if($list){
            echo apireturn(200,'success',$list);die;
        }else{
            $list = array();
            $data_rt['status'] = 200;
            $data_rt['msg'] = 'success';
            $data_rt['data'] = $list;
            return json_encode($data_rt);
            exit;
        }
        
    }
    
    //获取审批流
    public function get_flow(){
        
        $flow_type = $this->request->param('flow_type');
        $leixing = $this->request->param('leixing');

        if(empty($flow_type)){
            echo apireturn(201,'请选择审批类别','');
            die;
        }else{
            
            $whr = [];
            $whr[] = ['flow_type','=',$flow_type];
            $whr[] = ['status','=',1];
            $id = Db::name('flow_cate')->where($whr)->value('id');
            
            if(empty($id)){
                echo apireturn(201,'审批类别不存在','');
                die;
            }
            
            $list = Db::name('flow')->where('cate_id',$id)->order('sort asc')->select();
            foreach ($list as $key => $val){
                // $ulist = Db::name('users')->where('id','in',$val['flow_uid'])->select();
                // $uname = '';
                // foreach($ulist as $keys => $vals){
                //     $uname.= $vals['username'].',';
                // }
                // $list[$key]['flow_name'] = rtrim($uname,',');
                if($flow_type == 'qingjia' && $val['flow_leixing'] == 3 && $leixing == 1){
                    $list[$key]['flow_uid'] = '';
                    $list[$key]['flow_user'] = array();
                    $list[$key]['is_disable'] = 0;
                }else if($flow_type == 'qingjia' && $val['flow_leixing'] == 3 && $leixing == 5){
                    $list[$key]['flow_uid'] = '';
                    $list[$key]['flow_user'] = array();
                    $list[$key]['is_disable'] = 0;
                }else{
                    $list[$key]['flow_user'] = Db::name('users')->field('id,username')->where('id','in',$val['flow_uid'])->select();
                    if($val['flow_uid'] != ''){
                        $list[$key]['is_disable'] = 1;
                    }else{
                        $list[$key]['is_disable'] = 0;
                    }
                }
            }
                
            if($list){
                echo apireturn(200,'success',$list);die;
            }else{
                $list = array();
                $data_rt['status'] = 200;
                $data_rt['msg'] = 'success';
                $data_rt['data'] = $list;
                return json_encode($data_rt);
                exit;
            }
            
        }
    }

    //提交审批
    public function submit(){
        
        $data = $this->request->param();
          
        if(empty($data['flow_type'])){
            echo apireturn(201,'请选择审批类别','');
            die;
        }else{
            
            $name = $data['flow_type'];
            unset($data['flow_type']);
            $uid = $data['uid'];
            
            $uids = explode(',',$uid);
            
            $parentid = date('YmdHis').randString();
            
            foreach ($uids as $k => $v){
                
                $data['uid'] = $v;
                
                if(Db::name($name)->where($data)->count() == 0){
                    //获取补卡表返回的id
                    $id = Db::name($name)->insertGetId($data);
                    
                    $unionid = date('YmdHis').randString();
                    $dataz['parentid'] = $parentid;
                    $dataz['unionid'] = $unionid;
                    $dataz['flow_type'] = $name;
                    $dataz['flow_id'] = $id;
                    $dataz['uid'] = $this->user_id;
                    $dataz['shenqing_uid'] = $v;
                    $dataz['status'] = 1;
                    $dataz['create_time'] = time();
                    $dataz['update_time'] = time();
                    Db::name('flow_list')->insert($dataz);
                }
                
            }
            
            $flowlist = json_decode($data['json'],true);
            foreach($flowlist as $key => $val){
                if($val['flow_leixing'] != 4){
                    
                    $flow_uid = '';
                    foreach($val['flow_user'] as $keys => $vals){
                        $flow_uid.=$vals['id'].',';
                    }
                    $flow_uid = rtrim($flow_uid,',');
                    
                    if(!empty($flow_uid)){
                        $ulist = Db::name('users')->where('id','in',$flow_uid)->select();
                        foreach($ulist as $keys => $vals){
                            $data_flow['unionid'] = $parentid;
                            $data_flow['flow_way'] = $val['flow_way'];
                            $data_flow['flow_leixing'] = $val['flow_leixing'];
                            $data_flow['sort'] = $val['sort'];
                            $data_flow['apply_uid'] = $vals['id'];
                            $data_flow['shenqing_uid'] = $uid;
                            $data_flow['uid'] = $this->user_id;
                            $data_flow['is_send'] = 1;
                            $data_flow['status'] = 1;
                            $data_flow['create_time'] = time();
                            $data_flow['update_time'] = time();
                            Db::name('flow_apply')->insert($data_flow);
                        }
                    }
                }else{
                    $data_flow['unionid'] = $parentid;
                    $data_flow['flow_way'] = $val['flow_way'];
                    $data_flow['flow_leixing'] = $val['flow_leixing'];
                    $data_flow['sort'] = $val['sort'];
                    $data_flow['apply_uid'] = 0;
                    $data_flow['shenqing_uid'] = $uid;
                    $data_flow['uid'] = $this->user_id;
                    $data_flow['is_send'] = 1;
                    $data_flow['status'] = 1;
                    $data_flow['create_time'] = time();
                    $data_flow['update_time'] = time();
                    Db::name('flow_apply')->insert($data_flow);
                }
            }
            
            start_apply($parentid,2);
            echo apireturn(200,'success','');
            die;
        }
        
        
    }
    
    //判断当前记录状态
    public function checkdata(){
        $classid = $this->request->param('classid');
        $riqi = $this->request->param('riqi');
        
        //获取班次信息
        $classesinfo = Db::name('classes')->where('id',$classid)->find();
        
        $whr = [];
        $whr[] = ['uid','=',$this->user_id];
        $whr[] = ['classesid','=',$classid];
        $whr[] = ['riqi','=',$riqi];
        
        $queka = array();
        
        if($classesinfo['commuting_num'] == 1){
            $oneinfo = Db::name('check_log')->where($whr)->where('check_num',1)->find();
            if(empty($oneinfo)){
                $queka[0]['check_num'] = 1;
                $queka[0]['shijian'] = $classesinfo['one_in'];
                $queka[0]['zcshijian'] = $classesinfo['one_in'];
                $queka[0]['status'] = 4;
            }
            $twoinfo = Db::name('check_log')->where($whr)->where('check_num',2)->find();
            if(empty($twoinfo)){
                $queka[1]['check_num'] = 2;
                $queka[1]['shijian'] = $classesinfo['one_out'];
                $queka[1]['zcshijian'] = $classesinfo['one_out'];
                $queka[1]['status'] = 4;
            }
        }else{
            $oneinfo = Db::name('check_log')->where($whr)->where('check_num',1)->find();
            if(empty($oneinfo)){
                $queka[0]['check_num'] = 1;
                $queka[0]['shijian'] = $classesinfo['one_in'];
                $queka[0]['zcshijian'] = $classesinfo['one_in'];
                $queka[0]['status'] = 4;
            }
            $twoinfo = Db::name('check_log')->where($whr)->where('check_num',2)->find();
            if(empty($twoinfo)){
                $queka[1]['check_num'] = 2;
                $queka[1]['shijian'] = $classesinfo['one_out'];
                $queka[1]['zcshijian'] = $classesinfo['one_out'];
                $queka[1]['status'] = 4;
            }
            $threeinfo = Db::name('check_log')->where($whr)->where('check_num',3)->find();
            if(empty($threeinfo)){
                $queka[2]['check_num'] = 3;
                $queka[2]['shijian'] = $classesinfo['two_in'];
                $queka[2]['zcshijian'] = $classesinfo['two_in'];
                $queka[2]['status'] = 4;
            }
            $fourinfo = Db::name('check_log')->where($whr)->where('check_num',4)->find();
            if(empty($fourinfo)){
                $queka[3]['check_num'] = 4;
                $queka[3]['shijian'] = $classesinfo['two_out'];
                $queka[3]['zcshijian'] = $classesinfo['two_out'];
                $queka[3]['status'] = 4;
            }
        }
        
        $whr[] = ['status','<>',1];
        $list = Db::name('check_log')->field('check_num,shijian,zcshijian,status')->where($whr)->select();
        
        $lists = array_merge($list,$queka);
        $name = array_column($lists,'check_num');
        array_multisort($name,SORT_ASC,$lists);
        
        echo apireturn(200,'success',$lists);die;
        
    }
    
    //获取本人提交审批列表
    public function apply_log(){
        $data = $this->request->param();
        $whr['shenqing_uid'] = $this->user_id;
        
        if($data['flow_type'] != '0'){
            $whr['fl.flow_type'] = $data['flow_type'];
        }
        
        if($data['status'] > 0){
            $whr['fl.status'] = $data['status'];
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : 10;
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        
        $list = Db::name('flow_list')
            ->alias('fl')
            ->leftJoin('users u','fl.uid = u.id')
            ->leftJoin('flow_cate fc','fl.flow_type = fc.flow_type')
            ->field('fl.*,fc.title as title')
            ->order('fl.id DESC')
            ->where($whr)
            ->select();
            
        foreach($list as $key => $val){
            if($val['flow_type'] == 'buka'){
                $info = Db::name($val['flow_type'])->field('uid,leixing,riqi,shijian,reason')->where('id',$val['flow_id'])->find();
                
                $list[$key]['one'] = Db::name('users')->where('id',$info['uid'])->value('username');
                
                $list[$key]['two'] = $info['riqi'];
                
                if($info['leixing'] == 2){
                    $list[$key]['three'] = '迟到'.'，'.$info['shijian'];
                }else if($info['leixing'] == 3){
                    $list[$key]['three'] = '早退'.'，'.$info['shijian'];
                }else if($info['leixing'] == 4){
                    $list[$key]['three'] = '缺卡'.'，'.$info['shijian'];
                }
                
                $list[$key]['four'] = $info['reason'];
                $list[$key]['submit_time'] = date('m-d H:i',$val['create_time']);
            }else if($val['flow_type'] == 'qingjia'){
                $info = Db::name($val['flow_type'])->field('uid,leixing,start,end')->where('id',$val['flow_id'])->find();
                
                $list[$key]['one'] = Db::name('users')->where('id',$info['uid'])->value('username');
                
                if($info['leixing'] == 1){
                    $list[$key]['two'] = '事假';
                }else if($info['leixing'] == 2){
                    $list[$key]['two'] = '婚假';
                }else if($info['leixing'] == 3){
                    $list[$key]['two'] = '产假';
                }else if($info['leixing'] == 4){
                    $list[$key]['two'] = '丧假';
                }else if($info['leixing'] == 5){
                    $list[$key]['two'] = '调休';
                }

                $list[$key]['three'] = $info['start'];
                $list[$key]['four'] = $info['end'];
                
                $list[$key]['submit_time'] = date('m-d H:i',$val['create_time']);
            }else if($val['flow_type'] == 'jiaban'){
                $info = Db::name($val['flow_type'])->field('uid,leixing,start,end')->where('id',$val['flow_id'])->find();
                
                $list[$key]['one'] = Db::name('users')->where('id',$info['uid'])->value('username');
                
                if($info['leixing'] == 1){
                    $list[$key]['two'] = '日常加班';
                }else if($info['leixing'] == 2){
                    $list[$key]['two'] = '周六日加班';
                }
                $list[$key]['three'] = $info['start'];
                $list[$key]['four'] = $info['end'];
                
                $list[$key]['submit_time'] = date('m-d H:i',$val['create_time']);
            }else if($val['flow_type'] == 'burujia'){
                $info = Db::name($val['flow_type'])->field('uid,leixing,start,end')->where('id',$val['flow_id'])->find();
                
                $list[$key]['one'] = Db::name('users')->where('id',$info['uid'])->value('username');
                
                if($info['leixing'] == 1){
                    $list[$key]['two'] = '延后上班1小时';
                }else if($info['leixing'] == 2){
                    $list[$key]['two'] = '提前下班1小时';
                }
                $list[$key]['three'] = $info['start'];
                $list[$key]['four'] = $info['end'];
                
                $list[$key]['submit_time'] = date('m-d H:i',$val['create_time']);
            }else if($val['flow_type'] == 'gnchuchai'){
                $info = Db::name($val['flow_type'])->field('uid,shichang,start,end,start_type,end_type')->where('id',$val['flow_id'])->find();
                
                $list[$key]['one'] = Db::name('users')->where('id',$info['uid'])->value('username');
          
                $list[$key]['two'] = $info['shichang'].'天';
                
                if($info['start_type'] == 1){
                    $start_name = '上午';
                }else{
                    $start_name = '下午';
                }
                if($info['end_type'] == 1){
                    $end_name = '上午';
                }else{
                    $end_name = '下午';
                }
                $list[$key]['three'] = $info['start'].' '.$start_name;
                $list[$key]['four'] = $info['end'].' '.$end_name;
                
                $list[$key]['submit_time'] = date('m-d H:i',$val['create_time']);
            }else if($val['flow_type'] == 'gjchuchai'){
                $info = Db::name($val['flow_type'])->field('uid,shichang,start,end,start_type,end_type')->where('id',$val['flow_id'])->find();
                
                $list[$key]['one'] = Db::name('users')->where('id',$info['uid'])->value('username');
          
                $list[$key]['two'] = $info['shichang'];
                
                if($info['start_type'] == 1){
                    $start_name = '上午';
                }else{
                    $start_name = '下午';
                }
                if($info['end_type'] == 1){
                    $end_name = '上午';
                }else{
                    $end_name = '下午';
                }
                $list[$key]['three'] = $info['start'].' '.$start_name;
                $list[$key]['four'] = $info['end'].' '.$end_name;
                
                $list[$key]['submit_time'] = date('m-d H:i',$val['create_time']);
            }
        }
        
        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;
        
        echo apireturn(200,'success',$data_rt);die;
    }
    
    //审批人获取审批列表
    public function apply_list(){
        $data = $this->request->param();
        
        $whr = [];
        
        $whr[] = ['fa.apply_uid','=',$this->user_id];
        
        if(!empty($data['keyword'])){
            $whr[]=['u.username|u.mobile', 'like', '%'.$data['keyword'].'%'];
        }
        if(!empty($data['flow_type'])){
            $whr[] = ['fl.flow_type','=',$data['flow_type']];
        }
        if(!empty($data['flow_leixing'])){
            $whr[] = ['fa.flow_leixing','=',$data['flow_leixing']];
        }
        if(!empty($data['is_daiban'])){
            $whr[] = ['fa.status','=',1];
        }else{
            $whr[] = ['fa.status','>',1];
        }
        
        if(!empty($data['status'])){
            $whr[] = ['fl.status','=',$data['status']];
        }
        
        if(!empty($data['is_read'])){
            $whr[] = ['fa.is_read','=',$data['is_read']];
        }
        $whr[] = ['fa.is_send','=',2];
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        
        $list = Db::name('flow_apply')
            ->distinct(true)
            ->alias('fa')
            ->leftJoin('users u','fa.shenqing_uid = u.id')
            ->rightJoin('flow_list fl','fa.unionid = fl.parentid')
            ->field('fa.*,fl.status as status,fl.flow_type as flow_type,fl.flow_id as flow_id,fl.parentid')
            ->order('fa.id DESC')
            ->where($whr)
            ->group('parentid')
            ->select();
       
        
        foreach($list as $key => $val){
            if($val['flow_type'] == 'buka'){
                $info = Db::name($val['flow_type'])->field('uid,leixing,riqi,shijian,reason')->where('id',$val['flow_id'])->find();
                
                $flow_ids= Db::name('flow_list')->field('flow_id')->where('parentid',$val['parentid'])->buildSql(true);
                $uids= Db::name($val['flow_type'])->field('uid')->where('id','exp','In '.$flow_ids)->buildSql(true);
                
                $ulist = Db::name('users')->field('username')->where('id','exp','In '.$uids)->select();
                $uname = '';
                foreach ($ulist as $k => $v){
                    $uname .= $v['username'].',';
                }
                $uname = rtrim($uname,',');
                $list[$key]['one'] = $uname;
                
                $list[$key]['two'] = $info['riqi'];
                
                if($info['leixing'] == 2){
                    $list[$key]['three'] = '迟到'.'，'.$info['shijian'];
                }else if($info['leixing'] == 3){
                    $list[$key]['three'] = '早退'.'，'.$info['shijian'];
                }else if($info['leixing'] == 4){
                    $list[$key]['three'] = '缺卡'.'，'.$info['shijian'];
                }
                $list[$key]['four'] = $info['reason'];
                $list[$key]['submit_time'] = date('m-d H:i',$val['create_time']);
            }else if($val['flow_type'] == 'qingjia'){
                $info = Db::name($val['flow_type'])->field('uid,leixing,start,end')->where('id',$val['flow_id'])->find();
                
                $flow_ids= Db::name('flow_list')->field('flow_id')->where('parentid',$val['parentid'])->buildSql(true);
                $uids= Db::name($val['flow_type'])->field('uid')->where('id','exp','In '.$flow_ids)->buildSql(true);
                
                $ulist = Db::name('users')->field('username')->where('id','exp','In '.$uids)->select();
                $uname = '';
                foreach ($ulist as $k => $v){
                    $uname .= $v['username'].',';
                }
                $uname = rtrim($uname,',');
                $list[$key]['one'] = $uname;
                
                if($info['leixing'] == 1){
                    $list[$key]['two'] = '事假';
                }else if($info['leixing'] == 2){
                    $list[$key]['two'] = '婚假';
                }else if($info['leixing'] == 3){
                    $list[$key]['two'] = '产假';
                }else if($info['leixing'] == 4){
                    $list[$key]['two'] = '丧假';
                }else if($info['leixing'] == 5){
                    $list[$key]['two'] = '调休';
                }
                
                $list[$key]['three'] = $info['start'];
                $list[$key]['four'] = $info['end'];
                
                $list[$key]['submit_time'] = date('m-d H:i',$val['create_time']);
            }else if($val['flow_type'] == 'jiaban'){
                $info = Db::name($val['flow_type'])->field('uid,leixing,start,end')->where('id',$val['flow_id'])->find();
                
                $flow_ids= Db::name('flow_list')->field('flow_id')->where('parentid',$val['parentid'])->buildSql(true);
                $uids= Db::name($val['flow_type'])->field('uid')->where('id','exp','In '.$flow_ids)->buildSql(true);
                
                $ulist = Db::name('users')->field('username')->where('id','exp','In '.$uids)->select();
                $uname = '';
                foreach ($ulist as $k => $v){
                    $uname .= $v['username'].',';
                }
                $uname = rtrim($uname,',');
                $list[$key]['one'] = $uname;
                
                if($info['leixing'] == 1){
                    $list[$key]['two'] = '日常加班';
                }else if($info['leixing'] == 2){
                    $list[$key]['two'] = '周六日加班';
                }
                $list[$key]['three'] = $info['start'];
                $list[$key]['four'] = $info['end'];
                
                $list[$key]['submit_time'] = date('m-d H:i',$val['create_time']);
            }else if($val['flow_type'] == 'burujia'){
                $info = Db::name($val['flow_type'])->field('uid,leixing,start,end')->where('id',$val['flow_id'])->find();
                $list[$key]['title'] = '哺乳假';
                
                $flow_ids= Db::name('flow_list')->field('flow_id')->where('parentid',$val['parentid'])->buildSql(true);
                $uids= Db::name($val['flow_type'])->field('uid')->where('id','exp','In '.$flow_ids)->buildSql(true);
                
                $ulist = Db::name('users')->field('username')->where('id','exp','In '.$uids)->select();
                $uname = '';
                foreach ($ulist as $k => $v){
                    $uname .= $v['username'].',';
                }
                $uname = rtrim($uname,',');
                $list[$key]['one'] = $uname;
                
                if($info['leixing'] == 1){
                    $list[$key]['two'] = '延后上班1小时';
                }else if($info['leixing'] == 2){
                    $list[$key]['two'] = '提前下班1小时';
                }
                $list[$key]['three'] = $info['start'];
                $list[$key]['four'] = $info['end'];
                
                $list[$key]['submit_time'] = date('m-d H:i',$val['create_time']);
            }else if($val['flow_type'] == 'gnchuchai'){
                $info = Db::name($val['flow_type'])->field('uid,shichang,start,end,start_type,end_type')->where('id',$val['flow_id'])->find();
                $list[$key]['title'] = '国内出差';
                
                $flow_ids= Db::name('flow_list')->field('flow_id')->where('parentid',$val['parentid'])->buildSql(true);
                $uids= Db::name($val['flow_type'])->field('uid')->where('id','exp','In '.$flow_ids)->buildSql(true);
                
                $ulist = Db::name('users')->field('username')->where('id','exp','In '.$uids)->select();
                $uname = '';
                foreach ($ulist as $k => $v){
                    $uname .= $v['username'].',';
                }
                $uname = rtrim($uname,',');
                $list[$key]['one'] = $uname;
          
                $list[$key]['two'] = $info['shichang'].'天';
                
                if($info['start_type'] == 1){
                    $start_name = '上午';
                }else{
                    $start_name = '下午';
                }
                if($info['end_type'] == 1){
                    $end_name = '上午';
                }else{
                    $end_name = '下午';
                }
                $list[$key]['three'] = $info['start'].' '.$start_name;
                $list[$key]['four'] = $info['end'].' '.$end_name;
                
                $list[$key]['submit_time'] = date('m-d H:i',$val['create_time']);
            }else if($val['flow_type'] == 'gjchuchai'){
                $info = Db::name($val['flow_type'])->field('uid,shichang,start,end,start_type,end_type')->where('id',$val['flow_id'])->find();
                
                $list[$key]['title'] = '国际出差';
                
                $flow_ids= Db::name('flow_list')->field('flow_id')->where('parentid',$val['parentid'])->buildSql(true);
                $uids= Db::name($val['flow_type'])->field('uid')->where('id','exp','In '.$flow_ids)->buildSql(true);
                
                $ulist = Db::name('users')->field('username')->where('id','exp','In '.$uids)->select();
                $uname = '';
                foreach ($ulist as $k => $v){
                    $uname .= $v['username'].',';
                }
                $uname = rtrim($uname,',');
                $list[$key]['one'] = $uname;
          
                $list[$key]['two'] = $info['shichang'];
                
                if($info['start_type'] == 1){
                    $start_name = '上午';
                }else{
                    $start_name = '下午';
                }
                if($info['end_type'] == 1){
                    $end_name = '上午';
                }else{
                    $end_name = '下午';
                }
                $list[$key]['three'] = $info['start'].' '.$start_name;
                $list[$key]['four'] = $info['end'].' '.$end_name;
                
                $list[$key]['submit_time'] = date('m-d H:i',$val['create_time']);
            }
        }
        
        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;
        
        echo apireturn(200,'success',$data_rt);die;
    }
    
    //获取提交审批详情
    public function apply_detail(){
        $data = $this->request->param();
        
        //$whr['uid'] = $this->user_id;
        
        $whr['fl.parentid'] = $data['parentid'];
        
        $flowinfo = Db::name('flow_list')
            ->alias('fl')
            ->leftJoin('flow_cate fc','fl.flow_type = fc.flow_type')
            ->leftJoin('users u','fl.uid = u.id')
            ->field('fl.*,fc.title as title,u.username as tj_username')
            ->order('fl.id DESC')
            ->where($whr)
            ->find();
        
        $info = array();
        
        //查询申请单
        if($flowinfo['flow_type'] == 'buka'){
            $info = Db::name($flowinfo['flow_type'])->where('id',$flowinfo['flow_id'])->find();
            if($info['leixing'] == 2){
                $info['lxname'] = '迟到';
            }else if($info['leixing'] == 3){
                $info['lxname'] = '早退';
            }else if($info['leixing'] == 4){
                $info['lxname'] = '缺卡';
            }
            $info['submit_time'] = date('Y-m-d H:i',$flowinfo['create_time']);
            
            $flow_ids= Db::name('flow_list')->field('flow_id')->where('parentid',$flowinfo['parentid'])->buildSql(true);
            $uids= Db::name($flowinfo['flow_type'])->field('uid')->where('id','exp','In '.$flow_ids)->buildSql(true);
            
            $ulist = Db::name('users')->field('username')->where('id','exp','In '.$uids)->select();
            $uname = '';
            foreach ($ulist as $k => $v){
                $uname .= $v['username'].',';
            }
            $uname = rtrim($uname,',');
            $info['username'] = $uname;
            
            $info['usernames'] = Db::name('users')->where('id',$this->user_id)->value('username');
            $info['mobile'] = Db::name('users')->where('id',$info['uid'])->value('mobile');
            $info['group_name'] = Db::name('cate')->where('id',$info['group_id'])->value('title');
            //查询班次
            $classinfo = Db::name('classes')->field('title,commuting_num,one_in,one_out,two_in,two_out')->where('id',$info['classid'])->find();
            if($classinfo['commuting_num'] == 1){
                $info['class'] = $classinfo['title'].' '.$classinfo['one_in'].'~'.$classinfo['one_out'];
            }else{
                $info['class'] = $classinfo['title'].' '.$classinfo['one_in'].'~'.$classinfo['one_out'].';'.$classinfo['two_in'].'~'.$classinfo['two_out'];
            }
        }else if($flowinfo['flow_type'] == 'qingjia'){
            $info = Db::name($flowinfo['flow_type'])->where('id',$flowinfo['flow_id'])->find();
            if($info['leixing'] == 1){
                $info['lxname'] = '事假';
            }else if($info['leixing'] == 2){
                $info['lxname'] = '婚假';
            }else if($info['leixing'] == 3){
                $info['lxname'] = '产假';
            }else if($info['leixing'] == 4){
                $info['lxname'] = '丧假';
            }else if($info['leixing'] == 5){
                $info['lxname'] = '调休';
            }
            $info['submit_time'] = date('Y-m-d H:i',$flowinfo['create_time']);
            $flow_ids= Db::name('flow_list')->field('flow_id')->where('parentid',$flowinfo['parentid'])->buildSql(true);
            $uids= Db::name($flowinfo['flow_type'])->field('uid')->where('id','exp','In '.$flow_ids)->buildSql(true);
            
            $ulist = Db::name('users')->field('username')->where('id','exp','In '.$uids)->select();
            $uname = '';
            foreach ($ulist as $k => $v){
                $uname .= $v['username'].',';
            }
            $uname = rtrim($uname,',');
            $info['username'] = $uname;
            $info['usernames'] = Db::name('users')->where('id',$this->user_id)->value('username');
            $info['mobile'] = Db::name('users')->where('id',$info['uid'])->value('mobile');
            $info['group_name'] = Db::name('cate')->where('id',$info['group_id'])->value('title');
            
            if(!empty($info['image'])){
                $photo_list = Db::name('flow_image')->where('id','in',$info['image'])->select();
                foreach ($photo_list as $keys => $vals){
                    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                    $photo_list[$keys]['url'] = $http_type.$_SERVER['HTTP_HOST'].$vals['url'];
                }
                $info['image_list'] = $photo_list;
            }
        }else if($flowinfo['flow_type'] == 'jiaban'){
            $info = Db::name($flowinfo['flow_type'])->where('id',$flowinfo['flow_id'])->find();
            if($info['leixing'] == 1){
                $info['lxname'] = '日常加班';
            }else if($info['leixing'] == 2){
                $info['lxname'] = '周六日加班';
            }
            $info['submit_time'] = date('Y-m-d H:i',$flowinfo['create_time']);
            $flow_ids= Db::name('flow_list')->field('flow_id')->where('parentid',$flowinfo['parentid'])->buildSql(true);
            $uids= Db::name($flowinfo['flow_type'])->field('uid')->where('id','exp','In '.$flow_ids)->buildSql(true);
            
            $ulist = Db::name('users')->field('username')->where('id','exp','In '.$uids)->select();
            $uname = '';
            foreach ($ulist as $k => $v){
                $uname .= $v['username'].',';
            }
            $uname = rtrim($uname,',');
            $info['username'] = $uname;
            $info['usernames'] = Db::name('users')->where('id',$this->user_id)->value('username');
            $info['mobile'] = Db::name('users')->where('id',$info['uid'])->value('mobile');
            $info['group_name'] = Db::name('cate')->where('id',$info['group_id'])->value('title');
        }else if($flowinfo['flow_type'] == 'burujia'){
            $info = Db::name($flowinfo['flow_type'])->where('id',$flowinfo['flow_id'])->find();
            if($info['leixing'] == 1){
                $info['lxname'] = '延后上班1小时';
            }else if($info['leixing'] == 2){
                $info['lxname'] = '提前下班1小时';
            }
            $info['submit_time'] = date('Y-m-d H:i',$flowinfo['create_time']);
            $flow_ids= Db::name('flow_list')->field('flow_id')->where('parentid',$flowinfo['parentid'])->buildSql(true);
            $uids= Db::name($flowinfo['flow_type'])->field('uid')->where('id','exp','In '.$flow_ids)->buildSql(true);
            
            $ulist = Db::name('users')->field('username')->where('id','exp','In '.$uids)->select();
            $uname = '';
            foreach ($ulist as $k => $v){
                $uname .= $v['username'].',';
            }
            $uname = rtrim($uname,',');
            $info['username'] = $uname;
            $info['usernames'] = Db::name('users')->where('id',$this->user_id)->value('username');
            $info['mobile'] = Db::name('users')->where('id',$info['uid'])->value('mobile');
            $info['group_name'] = Db::name('cate')->where('id',$info['group_id'])->value('title');
        }else if($flowinfo['flow_type'] == 'gnchuchai'){
            $info = Db::name($flowinfo['flow_type'])->where('id',$flowinfo['flow_id'])->find();
            if($info['start_type'] == 1){
                $info['start_name'] = '上午';
            }else if($info['start_type'] == 2){
                $info['start_name'] = '下午';
            }
            if($info['end_type'] == 1){
                $info['end_name'] = '上午';
            }else if($info['end_type'] == 2){
                $info['end_name'] = '下午';
            }
            
            $info['submit_time'] = date('Y-m-d H:i',$flowinfo['create_time']);
            $flow_ids= Db::name('flow_list')->field('flow_id')->where('parentid',$flowinfo['parentid'])->buildSql(true);
            $uids= Db::name($flowinfo['flow_type'])->field('uid')->where('id','exp','In '.$flow_ids)->buildSql(true);
            
            $ulist = Db::name('users')->field('username')->where('id','exp','In '.$uids)->select();
            $uname = '';
            foreach ($ulist as $k => $v){
                $uname .= $v['username'].',';
            }
            $uname = rtrim($uname,',');
            $info['username'] = $uname;
            $info['usernames'] = Db::name('users')->where('id',$this->user_id)->value('username');
            $info['mobile'] = Db::name('users')->where('id',$info['uid'])->value('mobile');
            $info['group_name'] = Db::name('cate')->where('id',$info['group_id'])->value('title');
            
            if(!empty($info['image'])){
                $photo_list = Db::name('flow_image')->where('id','in',$info['image'])->select();
                foreach ($photo_list as $keys => $vals){
                    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                    $photo_list[$keys]['url'] = $http_type.$_SERVER['HTTP_HOST'].$vals['url'];
                }
                $info['image_list'] = $photo_list;
            }
        }else if($flowinfo['flow_type'] == 'gjchuchai'){
            $info = Db::name($flowinfo['flow_type'])->where('id',$flowinfo['flow_id'])->find();
            if($info['start_type'] == 1){
                $info['start_name'] = '上午';
            }else{
                $info['start_name'] = '下午';
            }
            if($info['end_type'] == 1){
                $info['end_name'] = '上午';
            }else{
                $info['end_name'] = '下午';
            }
            
            $info['submit_time'] = date('Y-m-d H:i',$flowinfo['create_time']);
            $flow_ids= Db::name('flow_list')->field('flow_id')->where('parentid',$flowinfo['parentid'])->buildSql(true);
            $uids= Db::name($flowinfo['flow_type'])->field('uid')->where('id','exp','In '.$flow_ids)->buildSql(true);
            
            $ulist = Db::name('users')->field('username')->where('id','exp','In '.$uids)->select();
            $uname = '';
            foreach ($ulist as $k => $v){
                $uname .= $v['username'].',';
            }
            $uname = rtrim($uname,',');
            $info['username'] = $uname;
            $info['usernames'] = Db::name('users')->where('id',$this->user_id)->value('username');
            $info['mobile'] = Db::name('users')->where('id',$info['uid'])->value('mobile');
            $info['group_name'] = Db::name('cate')->where('id',$info['group_id'])->value('title');
            
            if(!empty($info['image'])){
                $photo_list = Db::name('flow_image')->where('id','in',$info['image'])->select();
                foreach ($photo_list as $keys => $vals){
                    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                    $photo_list[$keys]['url'] = $http_type.$_SERVER['HTTP_HOST'].$vals['url'];
                }
                $info['image_list'] = $photo_list;
            }
        }
        
        $flowinfo['top'] = $info;
        
        //查询审批记录
        $whrz['unionid'] = $data['parentid'];
        //$whrz['uid'] = $this->user_id;
        $applist = Db::name('flow_apply')->field('sort,flow_way,flow_leixing')->where($whrz)->order('sort asc')->group('sort')->select();
        foreach($applist as $key => $val){
            $whrzz['sort'] = $val['sort'];
            $whrzz['unionid'] = $data['parentid'];
            
            $fa = Db::name('flow_apply')->alias('fa')->leftJoin('users u','fa.apply_uid = u.id')->field('u.username as username,fa.status,fa.is_send,fa.remark,fa.app_time as apptime')->where($whrzz)->order('sort asc')->select();
            
            foreach ($fa as $k => $v){
                if(!empty($v['apptime'])){
                    $fa[$k]['apptime'] = date('Y-m-d H:i',$v['apptime']);
                }
            }
            $applist[$key]['erji'] = $fa;
        }
        
        //更新为已读
        $upd['is_read'] = 2;
        Db::name('flow_apply')->where('unionid',$data['parentid'])->where('apply_uid',$this->user_id)->update($upd);
            
            
        //判断审批按钮
        if(Db::name('flow_apply')->where('unionid',$data['parentid'])->where('flow_leixing',2)->where('is_send',2)->where('status',1)->where('apply_uid',$this->user_id)->count()>0){
            $flowinfo['is_button'] = 1;
        }else{
            $flowinfo['is_button'] = 0;
        }
        //判断取消按钮
        if(Db::name('flow_list')->where('parentid',$data['parentid'])->where('status',1)->where('uid',$this->user_id)->count()>0){
            $flowinfo['is_quxiao'] = 1;
        }else{
            $flowinfo['is_quxiao'] = 0;
        }
        //判断再次发起按钮
        if(Db::name('flow_list')->where('parentid',$data['parentid'])->where('status','<>',1)->where('shenqing_uid',$this->user_id)->count()>0){
            $flowinfo['is_reapply'] = 1;
        }else{
            $flowinfo['is_reapply'] = 0;
        }
        
        $flowinfo['applist'] = $applist;
        
        echo apireturn(200,'success',$flowinfo);die;
        
    }
    
    //取消申请
    public function cancel(){
        $data = $this->request->param();
        $whr['uid'] = $this->user_id;
        $whr['parentid'] = $data['parentid'];
        if(Db::name('flow_list')->where($whr)->value('status') == 1){
            $data['status'] = 4;
            if(Db::name('flow_list')->where($whr)->update($data)){
                
                $dataz['status'] = 4;
                Db::name('flow_apply')->where('unionid',$data['parentid'])->where('status',1)->update($dataz);
                
                echo apireturn(200,'success','');die;
            }else{
                echo apireturn(201,'fiald','');die;
            }
        }else{
            echo apireturn(201,'当前状态不可取消','');die;
        }
        
    }
    
    
    //处理审批
    public function apply_handle(){
        $id = $this->request->param('id');
        $unionid = $this->request->param('parentid');
        $status = $this->request->param('status');
        $remark = $this->request->param('remark');
        $whr['id'] = $id;
        $whr['unionid'] = $unionid;
        $whr['apply_uid'] = $this->user_id;
        $infoz = Db::name('flow_apply')->where($whr)->find();
        if($infoz['flow_way'] == 1){
            if($infoz['status'] == 1){
                $data['status'] = $status;
                $data['remark'] = $remark;
                $data['app_time'] = time();
                Db::name('flow_apply')->where($whr)->update($data);
                if($status == 3){
                    
                    $flows = Db::name('flow_list')->field('uid,flow_type,flow_id,create_time')->where('parentid',$unionid)->find();
                    $info = Db::name($flows['flow_type'])->where('id',$flows['flow_id'])->find();
                
                    if($flows['flow_type'] == 'jiaban'){
                        $uname = Db::name('users')->where('id',$flows['uid'])->value('username');
                        
                        $neirong = $info['start'].' 至 '.$info['end'];
                        if($info['leixing'] == 1){
                            $neirong .= '，「日常加班」申请';
                        }else if($info['leixing'] == 2){
                            $neirong .= '，「周六日加班」申请';
                        }
                        
                        $shijian = date('m-d H:i',$flows['create_time']);
                    }else if($flows['flow_type'] == 'buka'){
                    
                        $uname = Db::name('users')->where('id',$flows['uid'])->value('username');
                        
                        $neirong = $info['riqi'].' ';
                        if($info['leixing'] == 2){
                            $lxname = $info['shijian'].'，迟到';
                        }else if($info['leixing'] == 3){
                            $lxname = $info['shijian'].'，早退';
                        }else if($info['leixing'] == 4){
                            $lxname = $info['shijian'].'，缺卡';
                        }
                        $neirong .=  '补卡申请';
                        
                        $shijian = date('m-d H:i',$flows['create_time']);
                    }else if($flows['flow_type'] == 'qingjia'){
                       
                        $uname = Db::name('users')->where('id',$flows['uid'])->value('username');
                        
                        $neirong = $info['start'].' 至 '.$info['end'];
                        if($info['leixing'] == 1){
                            $neirong .= '，事假 ';
                        }else if($info['leixing'] == 2){
                            $neirong .= '，婚假 ';
                        }else if($info['leixing'] == 3){
                            $neirong .= '，产假 ';
                        }else if($info['leixing'] == 4){
                            $neirong .= '，丧假 ';
                        }else if($info['leixing'] == 5){
                            $neirong .= '，调休 ';
                        }
                        
                        $shijian = date('m-d H:i',$flows['create_time']);
                    }else if($flows['flow_type'] == 'gnchuchai'){
                        
                        $uname = Db::name('users')->where('id',$flows['uid'])->value('username');
                        
                        $neirong = $info['start'].' 至 ';
                        $neirong .= $info['end'].' ，从 ';
                        $neirong .= $info['chufa'].' 至 ';
                        $neirong .= $info['mudi'].' ';
                        
                        if($info['cctype'] == 1){
                            $neirong .= '单程';
                        }else{
                            $neirong .= '往返';
                        }
                        $neirong = str_replace('>','',$neirong);
                        
                        
                        $shijian = date('m-d H:i',$flows['create_time']);
                    }else if($flows['flow_type'] == 'gjchuchai'){
                        
                        $uname = Db::name('users')->where('id',$flows['uid'])->value('username');
                        
                        $neirong = $info['start'].' 至 ';
                        $neirong .= $info['end'].' ，从 ';
                        $neirong .= $info['chufa'].' 至 ';
                        $neirong .= $info['mudi'].' ';
                        
                        if($info['cctype'] == 1){
                            $neirong .= '，单程';
                        }else{
                            $neirong .= '，往返';
                        }
                        $neirong = str_replace('>','',$neirong);
                        
                        $shijian = date('m-d H:i',$flows['create_time']);
                    }else if($flows['flow_type'] == 'burujia'){
                        
                        $uname = Db::name('users')->where('id',$flows['uid'])->value('username');
                        
                        $neirong = $info['start'].' 至 '.$info['end'];
                        if($info['leixing'] == 1){
                            $neirong .= '，延后上班1小时';
                        }else if($info['leixing'] == 2){
                            $neirong .= '，提前下班1小时';
                        }
                        
                       
                        $shijian = date('m-d H:i',$flows['create_time']);
                    }
                    
                    $openlist = Db::name('weixin')->field('uid,openid')->where('uid','in',$infoz['shenqing_uid'])->select();
                    foreach ($openlist as $k => $v){
                        if($v['openid']){
                             //所有字段都可为空
                            $dataq['uname'] = Db::name('users')->where('id',$v['uid'])->value('username');
                            $dataq['neirong'] = $neirong;
                            $dataq['shijian'] = $shijian;
                            $dataq['openid'] = $v['openid'];
                            $dataq['type'] = 3;
                            Db::name('wxnotice')->insert($dataq);
                            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                            $url=$http_type.$_SERVER['HTTP_HOST']."/api/wxnofiy/tongzhi";
                            http_curl($url,'post','json',$dataq);
                        }
                    }
               
                    $dataz['status'] = 4;
                    $dataz['remark'] = $remark;
                    $dataz['app_time'] = time();
                    //会签一个拒绝后其他人设置为无需操作
                    $whrj['unionid'] = $unionid;
                    //$whrj['sort'] = $info['sort'];
                    $whrj['status'] = 1;
                    
                    if(Db::name('flow_apply')->where($whrj)->count() > 0){
                        $datazq['status'] = 4;
                        $datazq['app_time'] = time();
                        Db::name('flow_apply')->where($whrj)->update($datazq);
                    }
                    
                    //修改申请单状态
                    $whrz['parentid'] = $unionid;
                    $datazz['status'] = $status;
                    $datazz['update_time'] = time();
                    Db::name('flow_list')->where($whrz)->update($datazz);
                    // if($info['flow_leixing'] != 4){
                    //     start_apply($unionid,$info['sort']);
                    // }
                    
                    
                }
            }else{
                echo apireturn(201,'当前已审核或无需审核','');die;
            } 
            if(Db::name('flow_apply')->where('unionid',$unionid)->where('sort',$infoz['sort'])->where('status',1)->count() == 0){
                start_apply($unionid,$infoz['sort']);
            }
        }else{
            if($info['status'] == 1){
                $data['status'] = $status;
                $data['remark'] = $remark;
                $data['app_time'] = time();
                Db::name('flow_apply')->where($whr)->update($data);
            
                $dataz['status'] = 4;
                $dataz['remark'] = $remark;
                $dataz['app_time'] = time();
                //或签一个通过或拒绝后其他人设置为无需操作
                $whrj['unionid'] = $unionid;
                $whrj['sort'] = $info['sort'];
                $whrj['status'] = 1;
                Db::name('flow_apply')->where($whrj)->update($dataz);
                
                //修改申请单状态
                $whrz['parentid'] = $unionid;
                $datazz['status'] = $status;
                $datazz['update_time'] = time();
                Db::name('flow_list')->where($whrz)->update($datazz);
                
                if($info['flow_leixing'] != 4){
                    start_apply($unionid,$info['sort']);
                }
            }else{
                echo apireturn(201,'当前已审核或无需审核','');die;
            } 
            
        }
        
        echo apireturn(200,'success','');die;
        
    }
    
    
    //上传文件
    public function uploads(){
        if(Request::isPost()) {
            $time = Request::param('time');
            $unionid = Request::param('unionid');
            //file是传文件的名称，这是webloader插件固定写入的。因为webloader插件会写入一个隐藏input，不信你们可以通过浏览器检查页面
            $file = request()->file('images');
            
            $info = $file->validate(['ext' => 'jpg,png,gif,jpeg,heif'])->move('uploads/flowimg');
            
            $url =  "/uploads/flowimg/".$info->getSaveName();
            $url = str_replace("\\","/",$url);
            
            $imageType = $info->getExtension();
            
            //压缩图片
            if($imageType == 'jpg'){
                // 获取完整路径
                $image =  $_SERVER['DOCUMENT_ROOT'].$url;
                // 加载图片资源
                $src = @imagecreatefromjpeg($image);
                list($width,$height) = getimagesize($image); //获取图片的高度
                $newwidth = $width;   //宽高可以设置, 楼主是想让它的宽高不变才没赋值
                $newheight = $height;
                if($newwidth > 5000){
                    $bili = 50;
                }elseif($newwidth > 2560){
                    $bili = 70;
                }else{
                    $bili = 100;
                }
                $tmp = imagecreatetruecolor($newwidth,$newheight); //生成新的宽高
                imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height); //缩放图像
                $output = imagejpeg($tmp, $image, $bili); //第三个参数(0~100);越大越清晰,图片大小也高;   png格式的为(1~9)
                // imagedestroy($tmp); 
            }elseif($imageType == 'png'){
                $image =  $_SERVER['DOCUMENT_ROOT'].$url;
                $src = @imagecreatefrompng($image);
                list($width,$height) = getimagesize($image);
                $newwidth = $width;
                $newheight = $height;
                if($newwidth > 5000){
                    $bili = 5;
                }elseif($newwidth > 2560){
                    $bili = 7;
                }else{
                    $bili = 9;
                }
                $tmp = imagecreatetruecolor($newwidth,$newheight);
                imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
                $output = imagepng($tmp, $image, $bili);  //这个图片的第三参数(1~9)
                // imagedestroy($tmp);
            }
            
            $data['uid'] = $this->user_id;
            $data['url'] = $url;
            $data['sort'] = 1;
            $data['time'] = $time;
            $data['unionid'] = $unionid;
            
            Db::name('flow_image')->insert($data);
         
            $photo_list = Db::name('flow_image')->where('uid',$this->user_id)->where('unionid',$unionid)->select();
            foreach ($photo_list as $key => $val){
                $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                $photo_list[$key]['url'] = $http_type.$_SERVER['HTTP_HOST'].$val['url'];
            }
            
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = '提交成功';
            $rs_arr['data'] = $photo_list;
            return json_encode($rs_arr,true);
            exit;
            
        }
    }
    //删除图片
    public function uploads_del(){
        if(Request::isPost()) {
            $id = Request::post('id');
            if(empty($id)){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = 'ID不存在';
        		return json_encode($rs_arr,true);
        		exit;
            }
            
            $whr['id'] = $id;
            $path = Db::name('flow_image')->where($whr)->value('url');
            
            $paths = Env::get('root_path').'public'.$path;
         
            if (file_exists($paths)) {
                @unlink($paths);//删除
            }
            
            Db::name('flow_image')->where($whr)->delete();
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] ='success';
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    
    
}
