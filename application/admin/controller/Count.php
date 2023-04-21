<?php
/**
 * +----------------------------------------------------------------------
 * | 考勤统计控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use app\common\model\AdType;
use think\Db;
use think\facade\Request;

class Count extends Base
{
    
    public function index(){
        
        $startDate = Request::param('start');
        $endDate = Request::param('end');
        $keyword = Request::param('keyword');
        $organizeid = Request::param('organizeid');
        $attendance_group_id = Request::param('attendance_group_id');
        $status = Request::param('status');
        
        $where=[];
        
        $a = '';
        if(!empty($keyword)){
            
            $whr1=[];
            $whr1[]=['username|mobile', 'like', '%'.$keyword.'%'];
            
            $ids = Db::name('users')
                ->where($whr1)
                ->field('id')
                ->select();

            foreach($ids as $key => $val){
                $a .= $val['id'].',';
            }
            
        }
        
        $b = '';
        if(!empty($organizeid)){
            
            $whr2=[];
            $whr2[]=['catid', '=', $organizeid];

            $uids = Db::name('cateuser')
                ->where($whr2)
                ->field('uid')
                ->select();

            foreach($uids as $key => $val){
                $b .= $val['uid'].',';
            }
        }
        
        if(isset($startDate)&&$startDate!=""&&isset($endDate)&&$endDate=="")
        {
            $where[] = ['day','>=',$startDate];
        }
        if(isset($endDate)&&$endDate!=""&&isset($startDate)&&$startDate=="")
        {
            $where[] = ['day','<=',$endDate];
        }
        if(isset($startDate)&&$startDate!=""&&isset($endDate)&&$endDate!="")
        {
            //$endDate = date('Y-m-d',strtotime($endDate-'1day'));
            $where[] = ['day','between',[$startDate,$endDate]];
        }
        if(isset($endDate)&&$endDate==""){
            $endDate = date('Y-m-d',time());
            $where[] = ['day','<',$endDate];
        }
        
        if(!empty($attendance_group_id)){
            $where[] = ['attendance_group_id','=',$attendance_group_id];
        }
        
        if(!empty($status)){
            if($status == 1){
                $one = 'num = zcnum';
            }else if($status == 2){
                $where[] = ['cdnum','>',0];
                $one = '';
            }else if($status == 3){
                $where[] = ['ztnum','>',0];
                $one = '';
            }else if($status == 4){
                $one = 'znum != num';
            }else{
                $one = '';
            }
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $aa = $pageSize*($page-1);
        
        //查询选中的人
        if(empty($a.$b)){
            $where[] = ['uid','>',0];
        }else{
            $where[] = ['uid','in',$a.$b];			
        }
        
        if(!empty($one)){
            $lists = Db::name('check_count')
                    ->where($one)
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->select();
                        
            $list = Db::name('check_count')
                    ->where($one)
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->limit($aa.','.$pageSize)
                    ->select();
        }else{
            $lists = Db::name('check_count')
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->select();
                        
            $list = Db::name('check_count')
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->limit($aa.','.$pageSize)
                    ->select();
        }
        
        foreach ($list as $key => $val){
            //查询会员信息
            $uinfo = Db::name('users')->field('username,mobile')->where('id',$val['uid'])->find();
            $list[$key]['username'] = $uinfo['username'];
            $whra['uid'] = $val['uid'];
            $whra['leixing'] = 1;
            $clist = Db::name('cateuser')
                ->field('catid')
                ->where($whra)
                ->select();
            
            foreach ($clist as $keys => $vals){
                $group_name = self::select_name($vals['catid']);
                $arr = explode('/',$group_name);
                $arrs = array_reverse($arr);
                $group_list = implode('/',$arrs);
                $group_list = ltrim($group_list,'/');
                $clist[$keys]['group_name'] = $group_list;
            }
            //所属站点
            $list[$key]['clist'] = $clist;
            $list[$key]['attendance_group_name'] = Db::name('attendance_group')->where('id',$val['attendance_group_id'])->value('title');
            $list[$key]['mobile'] = $uinfo['mobile'];
            $list[$key]['days'] =  days(date('N',strtotime($val['day'])));
            
            //查询班次
            $classinfo = Db::name('classes')->field('title,commuting_num,one_in,one_out,two_in,two_out')->where('id',$val['classesid'])->find();
            if($classinfo['commuting_num'] == 1){
                $list[$key]['class'] = $classinfo['title'].' '.$classinfo['one_in'].'~'.$classinfo['one_out'];
            }else{
                $list[$key]['class'] = $classinfo['title'].' '.$classinfo['one_in'].'~'.$classinfo['one_out'].';'.$classinfo['two_in'].'~'.$classinfo['two_out'];
            }
            //查询打卡结果
            $where1['riqi'] = $val['day'];
            $where1['attendance_group_id'] = $val['attendance_group_id'];
            $where1['classesid'] = $val['classesid'];
            $where1['uid'] = $val['uid'];
            $where1['check_num'] = 1;
            $one_in = Db::name('check_log')->field('shijian,status')->where($where1)->find();
            if($one_in){
                $list[$key]['one_in_time'] = $one_in['shijian'];
                $list[$key]['one_in_status'] = status($one_in['status']);
            }else{
                $status_name = '';
                $one_in_time = date('Y-m-d H:i:s',strtotime($val['day'].$classinfo['one_in']));
                //查询是否请假
                $whereqj = [];
                $whereqj[] = ['start','<',$one_in_time];
                $whereqj[] = ['end','>',$one_in_time];
                $whereqj[] = ['qj.uid','=',$val['uid']];
                $whereqj[] = ['fl.flow_type','=','qingjia'];
                $whereqj[] = ['fl.status','=',2];
                $qjinfo = Db::name('qingjia')
                        ->alias('qj')
                        ->leftJoin('flow_list fl','fl.flow_id = qj.id')
                        ->field('qj.*,fl.*')
                        ->where($whereqj)
                        ->find();
                if($qjinfo){
                    $status_name = qingjia($qjinfo['leixing']);
                }
                //查询是否国内出差
                $one_in_date = $val['day'];
                $wheregn = [];
                $wheregn[] = ['start','<=',$one_in_date];
                $wheregn[] = ['end','>=',$one_in_date];
                $wheregn[] = ['gn.uid','=',$val['uid']];
                $wheregn[] = ['fl.flow_type','=','gnchuchai'];
                $wheregn[] = ['fl.status','=',2];
               
                $gninfo = Db::name('gnchuchai')
                        ->alias('gn')
                        ->leftJoin('flow_list fl','fl.flow_id = gn.id')
                        ->field('gn.*,fl.*')
                        ->where($wheregn)
                        ->find();
                 
                if($gninfo){
                    $status_name = '国内出差';
                }
                //查询是否国际出差
                $wheregj = [];
                $wheregj[] = ['start','<=',$one_in_date];
                $wheregj[] = ['end','>=',$one_in_date];
                $wheregj[] = ['gj.uid','=',$val['uid']];
                $wheregj[] = ['fl.flow_type','=','gjchuchai'];
                $wheregj[] = ['fl.status','=',2];
                $gjinfo = Db::name('gjchuchai')
                        ->alias('gj')
                        ->leftJoin('flow_list fl','fl.flow_id = gj.id')
                        ->field('gj.*,fl.*')
                        ->where($wheregj)
                        ->find();
                if($gjinfo){
                    $status_name = '国际出差';
                }
            
                $list[$key]['one_in_time'] = '';
                $list[$key]['one_in_status'] = '缺卡';
                $list[$key]['one_in_status_name'] = $status_name;
                
            }
            
            $where2['riqi'] = $val['day'];
            $where2['attendance_group_id'] = $val['attendance_group_id'];
            $where2['classesid'] = $val['classesid'];
            $where2['uid'] = $val['uid'];
            $where2['check_num'] = 2;
            $one_out = Db::name('check_log')->field('shijian,status')->where($where2)->find();
            if($one_out){
                $list[$key]['one_out_time'] = $one_out['shijian'];
                $list[$key]['one_out_status'] = status($one_out['status']);
            }else{
                $status_name = '';
                $one_out_time = date('Y-m-d H:i:s',strtotime($val['day'].$classinfo['one_out']));
                //查询是否请假
                $whereqj = [];
                $whereqj[] = ['start','<',$one_out_time];
                $whereqj[] = ['end','>',$one_out_time];
                $whereqj[] = ['qj.uid','=',$val['uid']];
                $whereqj[] = ['fl.flow_type','=','qingjia'];
                $whereqj[] = ['fl.status','=',2];
                $qjinfo = Db::name('qingjia')
                        ->alias('qj')
                        ->leftJoin('flow_list fl','fl.flow_id = qj.id')
                        ->field('qj.*,fl.*')
                        ->where($whereqj)
                        ->find();
                if($qjinfo){
                    $status_name = qingjia($qjinfo['leixing']);
                }
                //查询是否国内出差
                $one_out_date = $val['day'];
                $wheregn = [];
                $wheregn[] = ['start','<=',$one_out_date];
                $wheregn[] = ['end','>=',$one_out_date];
                $wheregn[] = ['gn.uid','=',$val['uid']];
                $wheregn[] = ['fl.flow_type','=','gnchuchai'];
                $wheregn[] = ['fl.status','=',2];
                $gninfo = Db::name('gnchuchai')
                        ->alias('gn')
                        ->leftJoin('flow_list fl','fl.flow_id = gn.id')
                        ->field('gn.*,fl.*')
                        ->where($wheregn)
                        ->find();
                if($gninfo){
                    $status_name = '国内出差';
                }
                //查询是否国际出差
                $wheregj = [];
                $wheregj[] = ['start','<=',$one_out_date];
                $wheregj[] = ['end','>=',$one_out_date];
                $wheregj[] = ['gj.uid','=',$val['uid']];
                $wheregj[] = ['fl.flow_type','=','gjchuchai'];
                $wheregj[] = ['fl.status','=',2];
                $gjinfo = Db::name('gjchuchai')
                        ->alias('gj')
                        ->leftJoin('flow_list fl','fl.flow_id = gj.id')
                        ->field('gj.*,fl.*')
                        ->where($wheregj)
                        ->find();
                if($gjinfo){
                    $status_name = '国际出差';
                }
                
                    $list[$key]['one_out_time'] = '';
                    $list[$key]['one_out_status'] = '缺卡';
                    $list[$key]['one_out_status_name'] = $status_name;
                
            }
            
            $where3['riqi'] = $val['day'];
            $where3['attendance_group_id'] = $val['attendance_group_id'];
            $where3['classesid'] = $val['classesid'];
            $where3['uid'] = $val['uid'];
            $where3['check_num'] = 3;
            $two_in = Db::name('check_log')->field('shijian,status')->where($where3)->find();
            if($two_in){
                $list[$key]['two_in_time'] = $two_in['shijian'];
                $list[$key]['two_in_status'] = status($two_in['status']);
            }else{
                $status_name = '';
                $two_in_time = date('Y-m-d H:i:s',strtotime($val['day'].$classinfo['two_in']));
                //查询是否请假
                $whereqj = [];
                $whereqj[] = ['start','<',$two_in_time];
                $whereqj[] = ['end','>',$two_in_time];
                $whereqj[] = ['qj.uid','=',$val['uid']];
                $whereqj[] = ['fl.flow_type','=','qingjia'];
                $whereqj[] = ['fl.status','=',2];
                $qjinfo = Db::name('qingjia')
                        ->alias('qj')
                        ->leftJoin('flow_list fl','fl.flow_id = qj.id')
                        ->field('qj.*,fl.*')
                        ->where($whereqj)
                        ->find();
                if($qjinfo){
                    $status_name = qingjia($qjinfo['leixing']);
                }
                //查询是否国内出差
                $two_in_date = $val['day'];
                $wheregn = [];
                $wheregn[] = ['start','<=',$two_in_date];
                $wheregn[] = ['end','>=',$two_in_date];
                $wheregn[] = ['gn.uid','=',$val['uid']];
                $wheregn[] = ['fl.flow_type','=','gnchuchai'];
                $wheregn[] = ['fl.status','=',2];
                $gninfo = Db::name('gnchuchai')
                        ->alias('gn')
                        ->leftJoin('flow_list fl','fl.flow_id = gn.id')
                        ->field('gn.*,fl.*')
                        ->where($wheregn)
                        ->find();
                if($gninfo){
                    $status_name = '国内出差';
                }
                //查询是否国际出差
                $wheregj = [];
                $wheregj[] = ['start','<=',$two_in_date];
                $wheregj[] = ['end','>=',$two_in_date];
                $wheregj[] = ['gj.uid','=',$val['uid']];
                $wheregj[] = ['fl.flow_type','=','gjchuchai'];
                $wheregj[] = ['fl.status','=',2];
                $gjinfo = Db::name('gjchuchai')
                        ->alias('gj')
                        ->leftJoin('flow_list fl','fl.flow_id = gj.id')
                        ->field('gj.*,fl.*')
                        ->where($wheregj)
                        ->find();
                if($gjinfo){
                    $status_name = '国际出差';
                }
                
                    $list[$key]['two_in_time'] = '';
                    $list[$key]['two_in_status'] = '缺卡';
                    $list[$key]['two_in_status_name'] = $status_name;
               
            }
            
            $where4['riqi'] = $val['day'];
            $where4['attendance_group_id'] = $val['attendance_group_id'];
            $where4['classesid'] = $val['classesid'];
            $where4['uid'] = $val['uid'];
            $where4['check_num'] = 4;
            $two_out = Db::name('check_log')->field('shijian,status')->where($where4)->find();
            if($two_out){
                $list[$key]['two_out_time'] = $two_out['shijian'];
                $list[$key]['two_out_status'] = status($two_out['status']);
            }else{
                $status_name = '';
                $two_out_time = date('Y-m-d H:i:s',strtotime($val['day'].$classinfo['two_out']));
                //查询是否请假
                $whereqj = [];
                $whereqj[] = ['start','<',$two_out_time];
                $whereqj[] = ['end','>',$two_out_time];
                $whereqj[] = ['qj.uid','=',$val['uid']];
                $whereqj[] = ['fl.flow_type','=','qingjia'];
                $whereqj[] = ['fl.status','=',2];
                $qjinfo = Db::name('qingjia')
                        ->alias('qj')
                        ->leftJoin('flow_list fl','fl.flow_id = qj.id')
                        ->field('qj.*,fl.*')
                        ->where($whereqj)
                        ->find();
                if($qjinfo){
                    $status_name = qingjia($qjinfo['leixing']);
                }
                //查询是否国内出差
                $two_out_date = $val['day'];
                $wheregn = [];
                $wheregn[] = ['start','<=',$two_out_date];
                $wheregn[] = ['end','>=',$two_out_date];
                $wheregn[] = ['gn.uid','=',$val['uid']];
                $wheregn[] = ['fl.flow_type','=','gnchuchai'];
                $wheregn[] = ['fl.status','=',2];
                $gninfo = Db::name('gnchuchai')
                        ->alias('gn')
                        ->leftJoin('flow_list fl','fl.flow_id = gn.id')
                        ->field('gn.*,fl.*')
                        ->where($wheregn)
                        ->find();
                if($gninfo){
                    $status_name = '国内出差';
                }
                //查询是否国际出差
                $wheregj = [];
                $wheregj[] = ['start','<=',$two_out_date];
                $wheregj[] = ['end','>=',$two_out_date];
                $wheregj[] = ['gj.uid','=',$val['uid']];
                $wheregj[] = ['fl.flow_type','=','gjchuchai'];
                $wheregj[] = ['fl.status','=',2];
                $gjinfo = Db::name('gjchuchai')
                        ->alias('gj')
                        ->leftJoin('flow_list fl','fl.flow_id = gj.id')
                        ->field('gj.*,fl.*')
                        ->where($wheregj)
                        ->find();
                if($gjinfo){
                    $status_name = '国际出差';
                }
                
                    $list[$key]['two_out_time'] = '';
                    $list[$key]['two_out_status'] = '缺卡';
                    $list[$key]['two_out_status_name'] = $status_name;
                
            }
            
            //当天打卡状态统计
            if($classinfo['commuting_num'] == 1){
                if($one_in['status'] == 1 && $one_out['status'] == 1){
                    $list[$key]['day_status'] = '正常';
                }else{
                    if(empty($one_in) || empty($one_out)){
                        $list[$key]['day_status'] = '缺卡';
                    }else{
                        $list[$key]['day_status'] = '异常';
                    }
                }
            }else{
                if($one_in['status'] == 1 && $one_out['status'] == 1 && $two_in['status'] == 1 && $two_out['status'] == 1){
                    $list[$key]['day_status'] = '正常';
                }else{
                    if(empty($one_in) || empty($one_out) || empty($two_in) || empty($two_out)){
                        $list[$key]['day_status'] = '缺卡';
                    }else{
                        $list[$key]['day_status'] = '异常';
                    }
                        
                }
            }
        }	
        
        $data_rt['total'] = count($lists);
        $data_rt['data'] = $list;

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

        if($id != 1){
            $str .= self::select_name($info['parentid']);
        }

        return $str;
    }
    
    public function year——bf(){
        
        $startDate = Request::param('start');
        $endDate = Request::param('end');
        $keyword = Request::param('keyword');
        $organizeid = Request::param('organizeid');
        $attendance_group_id = Request::param('attendance_group_id');
        $status = Request::param('status');
        
        $where=[];
        
        $a = '';
        if(!empty($keyword)){
            
            $whr1=[];
            $whr1[]=['username|mobile', 'like', '%'.$keyword.'%'];
            
            $ids = Db::name('users')
                ->where($whr1)
                ->field('id')
                ->select();

            foreach($ids as $key => $val){
                $a .= $val['id'].',';
            }
            
        }
        
        $b = '';
        if(!empty($organizeid)){
            
            $whr2=[];
            $whr2[]=['catid', '=', $organizeid];

            $uids = Db::name('cateuser')
                ->where($whr2)
                ->field('uid')
                ->select();

            foreach($uids as $key => $val){
                $b .= $val['uid'].',';
            }
        }
        
        
        if(isset($startDate)&&$startDate!=""&&isset($endDate)&&$endDate!="")
        {
            $start = strtotime($startDate);
            $end = strtotime($endDate);
        }else{
            $end = time();
            $start = time()-864000;
        }
        
        if(!empty($attendance_group_id)){
            $where[] = ['attendance_group_id','=',$attendance_group_id];
        }
        
        if(!empty($status)){
            if($status == 1){
                $one = 'num = zcnum';
            }else if($status == 2){
                $where[] = ['cdnum','>',0];
                $one = '';
            }else if($status == 3){
                $where[] = ['ztnum','>',0];
                $one = '';
            }else if($status == 4){
                $one = 'znum != num';
            }else{
                $one = '';
            }
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $pageSize*($page-1);
        
        //查询选中的人
        if(empty($a.$b)){
            $where[] = ['uid','>',0];
        }else{
            $where[] = ['uid','in',$a.$b];			
        }
        
        if(!empty($one)){
            $lists = Db::name('check_count')
                    ->where($one)
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->select();
                        
            $list = Db::name('check_count')
                    ->where($one)
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->limit($a.','.$pageSize)
                    ->select();
        }else{
            $lists = Db::name('check_count')
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->select();
                        
            $list = Db::name('check_count')
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->limit($a.','.$pageSize)
                    ->select();
        }
        
        
        foreach ($list as $key => $val){
            //查询会员信息
            $uinfo = Db::name('users')->field('username,mobile')->where('id',$val['uid'])->find();
            $list[$key]['username'] = $uinfo['username'];
            $whra['uid'] = $val['uid'];
            $whra['leixing'] = 1;
            $clist = Db::name('cateuser')
                ->field('catid')
                ->where($whra)
                ->select();
            
            foreach ($clist as $keys => $vals){
                $group_name = self::select_name($vals['catid']);
                $arr = explode('/',$group_name);
                $arrs = array_reverse($arr);
                $group_list = implode('/',$arrs);
                $group_list = ltrim($group_list,'/');
                $clist[$keys]['group_name'] = $group_list;
            }
            //所属站点
            $list[$key]['clist'] = $clist;
            $list[$key]['attendance_group_name'] = Db::name('attendance_group')->where('id',$val['attendance_group_id'])->value('title');
            $list[$key]['mobile'] = $uinfo['mobile'];
            $list[$key]['days'] =  days(date('N',strtotime($val['day'])));
            
            //查询班次
            $classinfo = Db::name('classes')->field('title,commuting_num,one_in,one_out,two_in,two_out')->where('id',$val['classesid'])->find();
            if($classinfo['commuting_num'] == 1){
                $list[$key]['class'] = $classinfo['title'].' '.$classinfo['one_in'].'~'.$classinfo['one_out'];
            }else{
                $list[$key]['class'] = $classinfo['title'].' '.$classinfo['one_in'].'~'.$classinfo['one_out'].';'.$classinfo['two_in'].'~'.$classinfo['two_out'];
            }
            
            //出勤天数
            $wherecq = [];
            $wherecq[] = ['day','between',[$startDate,$endDate]];
            $wherecq[] = ['uid','=',$val['uid']];
            $list[$key]['chuqin'] = Db::name('check_count')->where($wherecq)->count();
            
            //查询迟到 早退 缺卡
            $wherenum = [];
            $wherenum[] = ['day','between',[$startDate,$endDate]];
            $wherenum[] = ['uid','=',$val['uid']];
            $num = Db::name('check_count')->where($wherenum)->sum('num');
            $znum = Db::name('check_count')->where($wherenum)->sum('znum');
            $cdnum = Db::name('check_count')->where($wherenum)->sum('cdnum');
            $ztnum = Db::name('check_count')->where($wherenum)->sum('ztnum');
            
            $list[$key]['chidao'] = $cdnum;
            $list[$key]['zaotui'] = $ztnum;
            $list[$key]['queka'] = $num - $znum;
          
            //查询打卡结果
           
            $datasj = array();
            $checklist = array();
            
            $i = $start;
            $o = $end;
            $k = 0;
            
            while($i <= $o)
            {
                
                $checklog = '';
                $checklog2 = '';
                
                $starts = date('Y-m-d',$i);
                
                $where1['riqi'] = $starts;
                $where1['attendance_group_id'] = $val['attendance_group_id'];
                $where1['classesid'] = $val['classesid'];
                $where1['uid'] = $val['uid'];
                $where1['check_num'] = 1;
                $one_in = Db::name('check_log')->field('shijian,status')->where($where1)->find();
                if($one_in){
                    $checklog = $checklog.status($one_in['status'])."(".$one_in['shijian'].")";
                    $checklog2 =$checklog2.status($one_in['status'])."(".$one_in['shijian'].")";
                }else{
                    $checklog = $checklog."缺卡()";
                    $checklog2 = $checklog2."缺卡()";
                }
                
                $where2['riqi'] = $starts;
                $where2['attendance_group_id'] = $val['attendance_group_id'];
                $where2['classesid'] = $val['classesid'];
                $where2['uid'] = $val['uid'];
                $where2['check_num'] = 2;
                $one_out = Db::name('check_log')->field('shijian,status')->where($where2)->find();
                if($one_out){
                    $checklog = $checklog.status($one_out['status'])."(".$one_out['shijian'].")";
                    $checklog2 = $checklog2.status($one_out['status'])."(".$one_out['shijian'].")";
                }else{
                    $checklog = $checklog."缺卡()";
                    $checklog2 = $checklog2."缺卡()";
                }
                
                $where3['riqi'] = $starts;
                $where3['attendance_group_id'] = $val['attendance_group_id'];
                $where3['classesid'] = $val['classesid'];
                $where3['uid'] = $val['uid'];
                $where3['check_num'] = 3;
                $two_in = Db::name('check_log')->field('shijian,status')->where($where3)->find();
                if($two_in){
                    $checklog = $checklog.status($two_in['status'])."(".$two_in['shijian'].")";
                }else{
                    $checklog = $checklog."缺卡()";
                }
                
                $where4['riqi'] = $starts;
                $where4['attendance_group_id'] = $val['attendance_group_id'];
                $where4['classesid'] = $val['classesid'];
                $where4['uid'] = $val['uid'];
                $where4['check_num'] = 4;
                $two_out = Db::name('check_log')->field('shijian,status')->where($where4)->find();
                if($two_out){
                    $checklog = $checklog.status($two_out['status'])."(".$two_out['shijian'].")";
                }else{
                    $checklog = $checklog."缺卡()";
                }
                
                $datasj[$k]['title'] = $starts;
                
                // $checklist[$k]['date'] = $starts;
                // if($classinfo['commuting_num'] == 1){
                //     $checklist[$k]['text'] = $checklog2;
                // }else{
                //     $checklist[$k]['text'] = $checklog;
                // }
                
                //$list[$key]['date'] = $starts;
                if($classinfo['commuting_num'] == 1){
                    $list[$key][$starts] = $checklog2;
                }else{
                    $list[$key][$starts] = $checklog;
                }
                
                $i = $i+86400;
                $k = $k + 1;
            }
            
            //$list[$key]['checklist'] = $checklist;
        }	
        
        $data_rt['total'] = count($lists);
        $data_rt['data'] = $list;
        $data_rt['title'] = $datasj;

        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $data_rt;
        return json_encode($rs_arr,true);
        exit;
    }
    
    
    public function year(){
        
        $startDate = Request::param('start');
        $endDate = Request::param('end');
        $keyword = Request::param('keyword');
        $organizeid = Request::param('organizeid');
        $attendance_group_id = Request::param('attendance_group_id');
        $status = Request::param('status');
        
        $where=[];
        
        $a = '';
        if(!empty($keyword)){
            
            $whr1=[];
            $whr1[]=['username|mobile', 'like', '%'.$keyword.'%'];
            
            $ids = Db::name('users')
                ->where($whr1)
                ->field('id')
                ->select();

            foreach($ids as $key => $val){
                $a .= $val['id'].',';
            }
            
        }
        
        $b = '';
        if(!empty($organizeid)){
            
            $whr2=[];
            $whr2[]=['catid', '=', $organizeid];

            $uids = Db::name('cateuser')
                ->where($whr2)
                ->field('uid')
                ->select();

            foreach($uids as $key => $val){
                $b .= $val['uid'].',';
            }
        }
        
        
        if(isset($startDate)&&$startDate!=""&&isset($endDate)&&$endDate!="")
        {
            $start = strtotime($startDate);
            $end = strtotime($endDate);
            
            $startDates = date('Y-m-d H:i:s',$start);
            $endDates = date('Y-m-d',$end).' 23:59:59';
        }else{
            $end = time();
            $start = time()-864000;
            $startDate = date('Y-m-d',$start);
            $endDate = date('Y-m-d',$end);
            
            $startDates = date('Y-m-d H:i:s',$start);
            $endDates = date('Y-m-d H:i:s',$end);
        }
        
        if(!empty($attendance_group_id)){
            $where[] = ['attendance_group_id','=',$attendance_group_id];
        }
        
        if(!empty($status)){
            if($status == 1){
                $one = 'num = zcnum';
            }else if($status == 2){
                $where[] = ['cdnum','>',0];
                $one = '';
            }else if($status == 3){
                $where[] = ['ztnum','>',0];
                $one = '';
            }else if($status == 4){
                $one = 'znum != num';
            }else{
                $one = '';
            }
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $aa = $pageSize*($page-1);
        
        //查询选中的人
        if(empty($a.$b)){
            $where[] = ['uid','>',0];
        }else{
            $where[] = ['uid','in',$a.$b];			
        }
        
        if(!empty($one)){
            $lists = Db::name('check_count')
                    ->where($one)
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->group('uid')
                    ->select();
                        
            $list = Db::name('check_count')
                    ->where($one)
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->limit($aa.','.$pageSize)
                    ->group('uid')
                    ->select();
        }else{
            $lists = Db::name('check_count')
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->group('uid')
                    ->select();
                        
            $list = Db::name('check_count')
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->limit($aa.','.$pageSize)
                    ->group('uid')
                    ->select();
        }
        
        
        $datasj = array();
        
        $num = 0;
        $znum = 0;
        $cdnum = 0;
        $ztnum = 0;
        foreach ($list as $key => $val){
            //查询出差时长
            $whereccgn = [];
            $whereccgn[] = ['gn.start','between',[$startDate,$endDate]];
            
            $gnchuchai = Db::name('gnchuchai')
            ->alias('gn')
            ->leftJoin('flow_list fl','fl.flow_id = gn.id')
            ->where('flow_type','gnchuchai')->where($whereccgn)->where('gn.uid',$val['uid'])->where('fl.status',2)->sum('shichang');
            
            $whereccgj = [];
            $whereccgj[] = ['gj.start','between',[$startDate,$endDate]];
            
            $gjchuchai = Db::name('gjchuchai')
            ->alias('gj')
            ->leftJoin('flow_list fl','fl.flow_id = gj.id')
            ->where('flow_type','gjchuchai')->where($whereccgj)->where('gj.uid',$val['uid'])->where('fl.status',2)->sum('shichang');
            $list[$key]['chuchai_time'] = $gnchuchai + $gjchuchai;
            
            //查询补卡次数
            $whereq = [];
            $whereq[] = ['bk.riqi','between',[$startDate,$endDate]];
            
            $list[$key]['bknum'] = Db::name('buka')
            ->alias('bk')
            ->leftJoin('flow_list fl','fl.flow_id = bk.id')
            ->where('flow_type','buka')->where($whereq)->where('bk.uid',$val['uid'])->where('fl.status',2)->count();
            
            //查询加班时长
            $wherejb = [];
            $wherejb[] = ['jb.start','between',[$startDates,$endDates]];
            
            $list[$key]['jiaban_time'] = Db::name('jiaban')
            ->alias('jb')
            ->leftJoin('flow_list fl','fl.flow_id = jb.id')
            ->where('flow_type','jiaban')->where($wherejb)->where('jb.uid',$val['uid'])->where('fl.status',2)->sum('shichang');
            
            //查询会员信息
            $uinfo = Db::name('users')->field('username,mobile')->where('id',$val['uid'])->find();
            $list[$key]['username'] = $uinfo['username'];
            $whra['uid'] = $val['uid'];
            $whra['leixing'] = 1;
            $clist = Db::name('cateuser')
                ->field('catid')
                ->where($whra)
                ->select();
            
            foreach ($clist as $keys => $vals){
                $group_name = self::select_name($vals['catid']);
                $arr = explode('/',$group_name);
                $arrs = array_reverse($arr);
                $group_list = implode('/',$arrs);
                $group_list = ltrim($group_list,'/');
                $clist[$keys]['group_name'] = $group_list;
            }
            //所属站点
            $list[$key]['clist'] = $clist;
            //考勤组
            $list[$key]['attendance_group_name'] = Db::name('attendance_group')->where('id',$val['attendance_group_id'])->value('title');
            //手机号
            $list[$key]['mobile'] = $uinfo['mobile'];
            //星期
            $list[$key]['days'] =  days(date('N',strtotime($val['day'])));
            
            
            //出勤天数
            $wherecq = [];
            $wherecq[] = ['day','between',[$startDate,$endDate]];
            $wherecq[] = ['uid','=',$val['uid']];
            $list[$key]['chuqin'] = Db::name('check_count')->where($wherecq)->count();
            
            //查询迟到 早退 缺卡
            $wherenum = [];
            $wherenum[] = ['day','between',[$startDate,$endDate]];
            $wherenum[] = ['uid','=',$val['uid']];
            $num = Db::name('check_count')->where($wherenum)->sum('num');
            $znum = Db::name('check_count')->where($wherenum)->sum('znum');
            $zcnum = Db::name('check_count')->where($wherenum)->sum('zcnum');
            $cdnum = Db::name('check_count')->where($wherenum)->sum('cdnum');
            $ztnum = Db::name('check_count')->where($wherenum)->sum('ztnum');
            
            $list[$key]['chidao'] = $cdnum;
            $list[$key]['zaotui'] = $ztnum;
            $list[$key]['queka'] = $num-$zcnum-$cdnum-$ztnum;
          
            //查询打卡结果
            $checklist = array();
            
            $i = $start;
            $o = $end;
            $k = 0;
            
            //实际出勤天数
            $shiji = 0;
            
            $classinfos = '';
            
            while($i <= $o)
            {
                
                $checklog = '';
                $checklog2 = '';
                
                $starts = date('Y-m-d',$i);
                
                //查询班次
                $classlist = Db::name('check_count')
                        ->where($where)
                        ->where('day',$starts)
                        ->where('uid',$val['uid'])
                        ->select();
            
                $one = 0;
                $two = 0;
                $three = 0;
                $four = 0;
                foreach($classlist as $keyz => $valz){
                    $classinfoz = Db::name('classes')->field('title,commuting_num,one_in,one_out,two_in,two_out')->where('id',$valz['classesid'])->find();
                    if($classinfoz['commuting_num'] == 1){
                        $classinfos .= '「'.$starts.':'.$classinfoz['title'].' '.$classinfoz['one_in'].'~'.$classinfoz['one_out'].'」';
                        
                        $where1['riqi'] = $valz['day'];
                        $where1['attendance_group_id'] = $valz['attendance_group_id'];
                        $where1['classesid'] = $valz['classesid'];
                        $where1['uid'] = $valz['uid'];
                        $where1['check_num'] = 1;
                        $one_in = Db::name('check_log')->field('shijian,status')->where($where1)->find();
                        if($one_in){
                            $checklog = $checklog.status($one_in['status'])."(".$one_in['shijian'].")";
                            if($one_in['status'] != 4){
                                $one = 1;
                            }
                        }else{
                            $checklog = $checklog."缺卡()";
                        }
                        
                        $where2['riqi'] = $valz['day'];;
                        $where2['attendance_group_id'] = $valz['attendance_group_id'];
                        $where2['classesid'] = $valz['classesid'];
                        $where2['uid'] = $valz['uid'];
                        $where2['check_num'] = 2;
                        $one_out = Db::name('check_log')->field('shijian,status')->where($where2)->find();
                        if($one_out){
                            $checklog = $checklog.status($one_out['status'])."(".$one_out['shijian'].")";
                            if($one_out['status'] != 4){
                                $two = 1;
                            }
                        }else{
                            $checklog = $checklog."缺卡()";
                        }
                        
                        if($one == 1 && $two == 1){
                            $shiji = $shiji + 1;
                        }
                    }else{
                        $classinfos .= '「'.$starts.':'.$classinfoz['title'].' '.$classinfoz['one_in'].'~'.$classinfoz['one_out'].';'.$classinfoz['two_in'].'~'.$classinfoz['two_out'].'」';
                        
                        $where1['riqi'] = $valz['day'];;
                        $where1['attendance_group_id'] = $valz['attendance_group_id'];
                        $where1['classesid'] = $valz['classesid'];
                        $where1['uid'] = $valz['uid'];
                        $where1['check_num'] = 1;
                        $one_in = Db::name('check_log')->field('shijian,status')->where($where1)->find();
                        if($one_in){
                            $checklog = $checklog.status($one_in['status'])."(".$one_in['shijian'].")";
                            if($one_in['status'] != 4){
                                $one = 1;
                            }
                        }else{
                            $checklog = $checklog."缺卡()";
                        }
                        
                        $where2['riqi'] = $valz['day'];;
                        $where2['attendance_group_id'] = $valz['attendance_group_id'];
                        $where2['classesid'] = $valz['classesid'];
                        $where2['uid'] = $valz['uid'];
                        $where2['check_num'] = 2;
                        $one_out = Db::name('check_log')->field('shijian,status')->where($where2)->find();
                        if($one_out){
                            $checklog = $checklog.status($one_out['status'])."(".$one_out['shijian'].")";
                            if($one_out['status'] != 4){
                                $two = 1;
                            }
                        }else{
                            $checklog = $checklog."缺卡()";
                        }
                        
                        if($one == 1 && $two == 1){
                            $shiji = $shiji + 0.5;
                        }
                        
                        $where3['riqi'] = $valz['day'];;
                        $where3['attendance_group_id'] = $valz['attendance_group_id'];
                        $where3['classesid'] = $valz['classesid'];
                        $where3['uid'] = $valz['uid'];
                        $where3['check_num'] = 3;
                        $two_in = Db::name('check_log')->field('shijian,status')->where($where3)->find();
                        if($two_in){
                            $checklog = $checklog.status($two_in['status'])."(".$two_in['shijian'].")";
                            if($two_in['status'] != 4){
                                $three = 1;
                            }
                        }else{
                            $checklog = $checklog."缺卡()";
                        }
                        
                        $where4['riqi'] = $valz['day'];;
                        $where4['attendance_group_id'] = $valz['attendance_group_id'];
                        $where4['classesid'] = $valz['classesid'];
                        $where4['uid'] = $valz['uid'];
                        $where4['check_num'] = 4;
                        $two_out = Db::name('check_log')->field('shijian,status')->where($where4)->find();
                        if($two_out){
                            $checklog = $checklog.status($two_out['status'])."(".$two_out['shijian'].")";
                            if($two_out['status'] != 4){
                                $four = 1;
                            }
                        }else{
                            $checklog = $checklog."缺卡()";
                        }
                        
                        if($three == 1 && $four == 1){
                            $shiji = $shiji + 0.5;
                        }
                    }
                }        
                
                $datasj[$k]['title'] = $starts;
                
                // $checklist[$k]['date'] = $starts;
                // if($classinfo['commuting_num'] == 1){
                //     $checklist[$k]['text'] = $checklog2;
                // }else{
                //     $checklist[$k]['text'] = $checklog;
                // }
                
                //$list[$key]['date'] = $starts;
                //if($classinfo['commuting_num'] == 1){
                    //$list[$key][$starts] = $checklog2;
                //}else{
                    
                //}
                $list[$key][$starts] = $checklog;
                
                $i = $i+86400;
                $k = $k + 1;
            }
            
            $list[$key]['class'] = $classinfos;
            
            //实际出勤天数
            $list[$key]['shijichuqin'] = $shiji;
            
            //$list[$key]['checklist'] = $checklist;
        }	
        
        $data_rt['total'] = count($lists);
        $data_rt['data'] = $list;
        $data_rt['title'] = $datasj;

        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $data_rt;
        return json_encode($rs_arr,true);
        exit;
    }
    
    //原始记录
    public function original(){
        
        $startDate = Request::param('start');
        $endDate = Request::param('end');
        $keyword = Request::param('keyword');
        $organizeid = Request::param('organizeid');
        $attendance_group_id = Request::param('attendance_group_id');
        $status = Request::param('status');
        
        $where=[];
        
        $a = '';
        if(!empty($keyword)){
            
            $whr1=[];
            $whr1[]=['username|mobile', 'like', '%'.$keyword.'%'];
            
            $ids = Db::name('users')
                ->where($whr1)
                ->field('id')
                ->select();

            foreach($ids as $key => $val){
                $a .= $val['id'].',';
            }
            
        }
        
        $b = '';
        if(!empty($organizeid)){
            
            $whr2=[];
            $whr2[]=['catid', '=', $organizeid];

            $uids = Db::name('cateuser')
                ->where($whr2)
                ->field('uid')
                ->select();

            foreach($uids as $key => $val){
                $b .= $val['uid'].',';
            }
        }
        
        
        if(isset($startDate)&&$startDate!=""&&isset($endDate)&&$endDate=="")
        {
            $where[] = ['riqi','>=',$startDate];
        }
        if(isset($endDate)&&$endDate!=""&&isset($startDate)&&$startDate=="")
        {
            $where[] = ['riqi','<=',$endDate];
        }
        if(isset($startDate)&&$startDate!=""&&isset($endDate)&&$endDate!="")
        {
            $where[] = ['riqi','between',[$startDate,$endDate]];
        }
        
        if(!empty($attendance_group_id)){
            $where[] = ['attendance_group_id','=',$attendance_group_id];
        }
        
        if(!empty($status)){
            $where[] = ['status','=',$status];
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $aa = $pageSize*($page-1);
        
        //查询选中的人
        if(empty($a.$b)){
            $where[] = ['uid','>',0];
        }else{
            $where[] = ['uid','in',$a.$b];			
        }
        
        if(!empty($one)){
            $lists = Db::name('check_log')
                    ->where($one)
                    ->where($where)
                    ->order('riqi desc,shijian desc')
                    ->select();
                        
            $list = Db::name('check_log')
                    ->where($one)
                    ->where($where)
                    ->order('riqi desc,shijian desc')
                    ->limit($aa.','.$pageSize)
                    ->select();
        }else{
            $lists = Db::name('check_log')
                    ->where($where)
                    ->order('riqi desc,shijian desc')
                    ->select();
                        
            $list = Db::name('check_log')
                    ->where($where)
                    ->order('riqi desc,shijian desc')
                    ->limit($aa.','.$pageSize)
                    ->select();
        }
        
        foreach ($list as $key => $val){
            //查询会员信息
            $uinfo = Db::name('users')->field('username,mobile')->where('id',$val['uid'])->find();
            $list[$key]['username'] = $uinfo['username'];
            $whra['uid'] = $val['uid'];
            $whra['leixing'] = 1;
            $clist = Db::name('cateuser')
                ->field('catid')
                ->where($whra)
                ->select();
            
            foreach ($clist as $keys => $vals){
                $group_name = self::select_name($vals['catid']);
                $arr = explode('/',$group_name);
                $arrs = array_reverse($arr);
                $group_list = implode('/',$arrs);
                $group_list = ltrim($group_list,'/');
                $clist[$keys]['group_name'] = $group_list;
            }
            //所属站点
            $list[$key]['clist'] = $clist;
            $list[$key]['attendance_group_name'] = Db::name('attendance_group')->where('id',$val['attendance_group_id'])->value('title');
            $list[$key]['mobile'] = $uinfo['mobile'];
            $list[$key]['days'] =  days(date('N',strtotime($val['riqi'])));
            
            //查询班次
            $classinfo = Db::name('classes')->field('title,commuting_num,one_in,one_out,two_in,two_out')->where('id',$val['classesid'])->find();
            if($classinfo['commuting_num'] == 1){
                $list[$key]['class'] = $classinfo['title'].' '.$classinfo['one_in'].'~'.$classinfo['one_out'];
            }else{
                $list[$key]['class'] = $classinfo['title'].' '.$classinfo['one_in'].'~'.$classinfo['one_out'].';'.$classinfo['two_in'].'~'.$classinfo['two_out'];
            }
            
            //当天打卡状态统计
            if($val['status'] == 1){
                $status_name = '正常';
            }else if($val['status'] == 2){
                $status_name = '迟到';
            }else if($val['status'] == 3){
                $status_name = '早退';
            }else if($val['status'] == 4){
                $status_name = '缺卡';
            }
            $list[$key]['status_name'] = $status_name;
            
            
            $one_in = strtotime($classinfo['one_in']);
            $one_out = strtotime($classinfo['one_out']);
            $two_in = strtotime($classinfo['two_in']);
            $two_out = strtotime($classinfo['two_out']);
            
            $one_out_type = 0;
            $two_in_type = 0;
            $two_out_type = 0;
            
            if($one_out < $one_in){
                $one_out_type = 1;
                $two_in_type = 1;
                $two_out_type = 1;
            }else if($two_in < $one_out){
                $two_in_type = 1;
                $two_out_type = 1;
            }else if($two_out < $two_in){
                $two_out_type = 1;
            }
            
            if($val['check_num'] == 2){
                if($one_out_type == 1){
                    $zcshijian = date('Y-m-d H:i:s',strtotime($val['zcshijian']+'1day'));
                }else{
                    $zcshijian = date('Y-m-d H:i:s',strtotime($val['zcshijian']));
                }
            }else if($val['check_num'] == 3){
                if($two_in_type == 1){
                    $zcshijian =  date('Y-m-d H:i:s',strtotime($val['zcshijian']+'1day'));
                }else{
                    $zcshijian = date('Y-m-d H:i:s',strtotime($val['zcshijian']));
                }
            }else if($val['check_num'] == 4){
                if($two_out_type == 1){
                    $zcshijian = date('Y-m-d H:i:s',strtotime($val['zcshijian']+'1day'));
                }else{
                    $zcshijian = date('Y-m-d H:i:s',strtotime($val['zcshijian']));
                }
            }else{
                $zcshijian = date('Y-m-d H:i:s',strtotime($val['zcshijian']));
            }
            
            $list[$key]['zcshijian'] = $zcshijian;
            $list[$key]['subshijian'] = date('Y-m-d H:i:s',$val['sub_time']);
            
            if($val['check_type'] == 1){
                $check_typename = 'GPS打卡';
            }elseif($val['check_type'] == 2){
                $check_typename = 'Wifi打卡';
            }
            $list[$key]['check_typename'] = $check_typename;
        }	
        
        $data_rt['total'] = count($lists);
        $data_rt['data'] = $list;

        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $data_rt;
        return json_encode($rs_arr,true);
        exit;
    }
    
    
    
    //审批人获取审批列表
    public function apply_list(){
        $data = $this->request->param();
        
        $startDate = strtotime($data['start']);
        $endDate = strtotime($data['end']);
        
        $whr = [];
        
        if(!empty($data['keyword'])){
            
            if($data['type'] == 1){
                $whr[]=['u.username', 'like', '%'.$data['keyword'].'%'];
            }else if($data['type'] == 2){
                $whr[]=['u.mobile', 'like', '%'.$data['keyword'].'%'];
            }else if($data['type'] == 3){
                $whr[]=['fa.unionid', 'like', '%'.$data['keyword'].'%'];
            }
        
        }
        
        if(!empty($data['flow_type'])){
            $whr[] = ['fl.flow_type','=',$data['flow_type']];
        }
        if(!empty($data['flow_leixing'])){
            $whr[] = ['fa.flow_leixing','=',$data['flow_leixing']];
        }
        if(!empty($data['status'])){
            $whr[] = ['fl.status','=',$data['status']];
        }
        
        if(!empty($data['leixing'])){
            
            if(isset($startDate)&&$startDate!=""&&isset($endDate)&&$endDate=="")
            {
                $whr[] = ['fl.'.$data['leixing'],'>=',$startDate];
            }
            if(isset($endDate)&&$endDate!=""&&isset($startDate)&&$startDate=="")
            {
                $whr[] = ['fl.'.$data['leixing'],'<',$endDate];
            }
            if(isset($startDate)&&$startDate!=""&&isset($endDate)&&$endDate!="")
            {
                $whr[] = ['fl.'.$data['leixing'],'between',[$startDate,$endDate]];
            }
            
        }
        //$whr[] = ['fa.is_send','=',2];
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        $list = Db::name('flow_list')
            ->distinct(true)
            ->alias('fl')
            ->leftJoin('users u','fl.shenqing_uid = u.id')
            ->leftJoin('users u2','fl.uid = u2.id')
            ->rightJoin('flow_apply fa','fl.parentid = fa.unionid')
            ->field('fl.*,fl.flow_type as flow_type,fl.flow_id as flow_id,u.mobile,u.username,fl.parentid,u2.username as tj_username')
            ->order('fl.id DESC')
            ->where($whr)
            ->select();
        
        foreach($list as $key => $val){
            if($val['flow_type']){
                $infos = Db::name($val['flow_type'])->where('id',$val['flow_id'])->find();
                $username = Db::name('users')->where('id',$infos['uid'])->value('username');
                $list[$key]['username'] = $username;
                $list[$key]['mobile'] = Db::name('users')->where('id',$infos['uid'])->value('mobile');
                
                $apply_text = '';
                $apply_text = $apply_text.$username.'|发起审批|'.date('Y-m-d H:i:s',$val['create_time']).';';
                $app_list = Db::name('flow_apply')->where('unionid',$val['parentid'])->where('flow_leixing',2)->where('is_send',2)->where('status','>',1)->select();
                if(count($app_list) > 0){
                    $statusname = '';
                    foreach ($app_list as $k => $v){
                        if($v['status'] == 2){
                            $statusname = '通过';
                        }else if($v['status'] == 3){
                            $statusname = '驳回';
                        }else{
                            $statusname = '无需审批';
                        }
                        $apply_text = $apply_text.Db::name('users')->where('id',$v['apply_uid'])->value('username').'|'.$statusname.'|'.date('Y-m-d H:i:s',$v['app_time']).';';
                    }
                }
                
                $list[$key]['apply_text'] = $apply_text;
                
                if($val['flow_type'] == 'buka'){
                    $info = Db::name($val['flow_type'])->where('id',$val['flow_id'])->find();
                    $info['user_name'] = Db::name('users')->where('id',$info['uid'])->value('username');
                    $info['group_name'] = Db::name('cate')->where('id',$info['group_id'])->value('title');
                    
                    if($info['leixing'] == 2){
                        $info['lxname'] = '迟到 ，'.$info['shijian'];
                    }else if($info['leixing'] == 3){
                        $info['lxname'] = '早退 ，'.$info['shijian'];
                    }else if($info['leixing'] == 4){
                        $info['lxname'] = '缺卡 ，'.$info['shijian'];
                    }
                    
                    $classinfo = Db::name('classes')->field('title,commuting_num,one_in,one_out,two_in,two_out')->where('id',$info['classid'])->find();
                    if($classinfo['commuting_num'] == 1){
                        $info['class'] = $classinfo['title'].' '.$classinfo['one_in'].'~'.$classinfo['one_out'];
                    }else{
                        $info['class'] = $classinfo['title'].' '.$classinfo['one_in'].'~'.$classinfo['one_out'].';'.$classinfo['two_in'].'~'.$classinfo['two_out'];
                    }
                    $list[$key]['info'] = $info;
                    
                    
                    $whrz['unionid'] = $val['unionid'];
                    //$whrz['uid'] = $this->user_id;
                    $applist = Db::name('flow_apply')->field('sort,flow_way,flow_leixing')->where($whrz)->order('sort asc')->group('sort')->select();
                    foreach($applist as $keys => $vals){
                        $whrzz['sort'] = $vals['sort'];
                        $whrzz['unionid'] = $val['unionid'];
                        
                        $fa = Db::name('flow_apply')->alias('fa')->leftJoin('users u','fa.apply_uid = u.id')->field('u.username as username,fa.status,fa.is_send,fa.remark,fa.app_time as apptime')->where($whrzz)->order('sort asc')->select();
                        
                        foreach ($fa as $k => $v){
                            if(!empty($v['apptime'])){
                                $fa[$k]['apptime'] = date('Y-m-d H:i',$v['apptime']);
                            }
                        }
                        $applist[$keys]['erji'] = $fa;
                    }
                    
                    $list[$key]['applist'] = $applist;
                }else if($val['flow_type'] == 'qingjia'){
                    $info = Db::name($val['flow_type'])->where('id',$val['flow_id'])->find();
                    $info['user_name'] = Db::name('users')->where('id',$info['uid'])->value('username');
                    $info['group_name'] = Db::name('cate')->where('id',$info['group_id'])->value('title');
                    
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
                    
                    if(!empty($info['image'])){
                        $photo_list = Db::name('flow_image')->where('id','in',$info['image'])->select();
                        foreach ($photo_list as $keys => $vals){
                            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                            $photo_list[$keys]['url'] = $http_type.$_SERVER['HTTP_HOST'].$vals['url'];
                        }
                        $info['image_list'] = $photo_list;
                    }
                    $list[$key]['info'] = $info;
                    
                    $whrz['unionid'] = $val['unionid'];
                    //$whrz['uid'] = $this->user_id;
                    $applist = Db::name('flow_apply')->field('sort,flow_way,flow_leixing')->where($whrz)->order('sort asc')->group('sort')->select();
                    foreach($applist as $keys => $vals){
                        $whrzz['sort'] = $vals['sort'];
                        $whrzz['unionid'] = $val['unionid'];
                        
                        $fa = Db::name('flow_apply')->alias('fa')->leftJoin('users u','fa.apply_uid = u.id')->field('u.username as username,fa.status,fa.is_send,fa.remark,fa.app_time as apptime')->where($whrzz)->order('sort asc')->select();
                        
                        foreach ($fa as $k => $v){
                            if(!empty($v['apptime'])){
                                $fa[$k]['apptime'] = date('Y-m-d H:i',$v['apptime']);
                            }
                        }
                        $applist[$keys]['erji'] = $fa;
                    }
                    
                    $list[$key]['applist'] = $applist;
                }else if($val['flow_type'] == 'jiaban'){
                    $info = Db::name($val['flow_type'])->where('id',$val['flow_id'])->find();
                    $info['user_name'] = Db::name('users')->where('id',$info['uid'])->value('username');
                    $info['group_name'] = Db::name('cate')->where('id',$info['group_id'])->value('title');
                    
                    if($info['leixing'] == 1){
                        $info['lxname'] = '日常加班';
                    }else if($info['leixing'] == 2){
                        $info['lxname'] = '周六日加班';
                    }
                    
                    $list[$key]['info'] = $info;
                    
                    $whrz['unionid'] = $val['unionid'];
                    //$whrz['uid'] = $this->user_id;
                    $applist = Db::name('flow_apply')->field('sort,flow_way,flow_leixing')->where($whrz)->order('sort asc')->group('sort')->select();
                    foreach($applist as $keys => $vals){
                        $whrzz['sort'] = $vals['sort'];
                        $whrzz['unionid'] = $val['unionid'];
                        
                        $fa = Db::name('flow_apply')->alias('fa')->leftJoin('users u','fa.apply_uid = u.id')->field('u.username as username,fa.status,fa.is_send,fa.remark,fa.app_time as apptime')->where($whrzz)->order('sort asc')->select();
                        
                        foreach ($fa as $k => $v){
                            if(!empty($v['apptime'])){
                                $fa[$k]['apptime'] = date('Y-m-d H:i',$v['apptime']);
                            }
                        }
                        $applist[$keys]['erji'] = $fa;
                    }
                    
                    $list[$key]['applist'] = $applist;
                }else if($val['flow_type'] == 'burujia'){
                    $info = Db::name($val['flow_type'])->where('id',$val['flow_id'])->find();
                    $info['user_name'] = Db::name('users')->where('id',$info['uid'])->value('username');
                    $info['group_name'] = Db::name('cate')->where('id',$info['group_id'])->value('title');
                    
                    if($info['leixing'] == 1){
                        $info['lxname'] = '延后上班一小时';
                    }else if($info['leixing'] == 2){
                        $info['lxname'] = '提前下班一小时';
                    }
                    
                    if(!empty($info['image'])){
                        $photo_list = Db::name('flow_image')->where('id','in',$info['image'])->select();
                        foreach ($photo_list as $keys => $vals){
                            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                            $photo_list[$keys]['url'] = $http_type.$_SERVER['HTTP_HOST'].$vals['url'];
                        }
                        $info['image_list'] = $photo_list;
                    }
                    $list[$key]['info'] = $info;
                    
                    $whrz['unionid'] = $val['unionid'];
                    //$whrz['uid'] = $this->user_id;
                    $applist = Db::name('flow_apply')->field('sort,flow_way,flow_leixing')->where($whrz)->order('sort asc')->group('sort')->select();
                    foreach($applist as $keys => $vals){
                        $whrzz['sort'] = $vals['sort'];
                        $whrzz['unionid'] = $val['unionid'];
                        
                        $fa = Db::name('flow_apply')->alias('fa')->leftJoin('users u','fa.apply_uid = u.id')->field('u.username as username,fa.status,fa.is_send,fa.remark,fa.app_time as apptime')->where($whrzz)->order('sort asc')->select();
                        
                        foreach ($fa as $k => $v){
                            if(!empty($v['apptime'])){
                                $fa[$k]['apptime'] = date('Y-m-d H:i',$v['apptime']);
                            }
                        }
                        $applist[$keys]['erji'] = $fa;
                    }
                    
                    $list[$key]['applist'] = $applist;
                }else if($val['flow_type'] == 'gnchuchai'){
                    $info = Db::name($val['flow_type'])->where('id',$val['flow_id'])->find();
                    $info['user_name'] = Db::name('users')->where('id',$info['uid'])->value('username');
                    $info['group_name'] = Db::name('cate')->where('id',$info['group_id'])->value('title');
                    
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
                    
                    if(!empty($info['image'])){
                        $photo_list = Db::name('flow_image')->where('id','in',$info['image'])->select();
                        foreach ($photo_list as $keys => $vals){
                            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                            $photo_list[$keys]['url'] = $http_type.$_SERVER['HTTP_HOST'].$vals['url'];
                        }
                        $info['image_list'] = $photo_list;
                    }
                    $list[$key]['info'] = $info;
                    
                    $whrz['unionid'] = $val['unionid'];
                    //$whrz['uid'] = $this->user_id;
                    $applist = Db::name('flow_apply')->field('sort,flow_way,flow_leixing')->where($whrz)->order('sort asc')->group('sort')->select();
                    foreach($applist as $keys => $vals){
                        $whrzz['sort'] = $vals['sort'];
                        $whrzz['unionid'] = $val['unionid'];
                        
                        $fa = Db::name('flow_apply')->alias('fa')->leftJoin('users u','fa.apply_uid = u.id')->field('u.username as username,fa.status,fa.is_send,fa.remark,fa.app_time as apptime')->where($whrzz)->order('sort asc')->select();
                        
                        foreach ($fa as $k => $v){
                            if(!empty($v['apptime'])){
                                $fa[$k]['apptime'] = date('Y-m-d H:i',$v['apptime']);
                            }
                        }
                        $applist[$keys]['erji'] = $fa;
                    }
                    
                    $list[$key]['applist'] = $applist;
                }else if($val['flow_type'] == 'gjchuchai'){
                    $info = Db::name($val['flow_type'])->where('id',$val['flow_id'])->find();
                    $info['user_name'] = Db::name('users')->where('id',$info['uid'])->value('username');
                    $info['group_name'] = Db::name('cate')->where('id',$info['group_id'])->value('title');
                    
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
                    
                    if(!empty($info['image'])){
                        $photo_list = Db::name('flow_image')->where('id','in',$info['image'])->select();
                        foreach ($photo_list as $keys => $vals){
                            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                            $photo_list[$keys]['url'] = $http_type.$_SERVER['HTTP_HOST'].$vals['url'];
                        }
                        $info['image_list'] = $photo_list;
                    }
                    $list[$key]['info'] = $info;
                    
                    $whrz['unionid'] = $val['unionid'];
                    //$whrz['uid'] = $this->user_id;
                    $applist = Db::name('flow_apply')->field('sort,flow_way,flow_leixing')->where($whrz)->order('sort asc')->group('sort')->select();
                    foreach($applist as $keys => $vals){
                        $whrzz['sort'] = $vals['sort'];
                        $whrzz['unionid'] = $val['unionid'];
                        
                        $fa = Db::name('flow_apply')->alias('fa')->leftJoin('users u','fa.apply_uid = u.id')->field('u.username as username,fa.status,fa.is_send,fa.remark,fa.app_time as apptime')->where($whrzz)->order('sort asc')->select();
                        
                        foreach ($fa as $k => $v){
                            if(!empty($v['apptime'])){
                                $fa[$k]['apptime'] = date('Y-m-d H:i',$v['apptime']);
                            }
                        }
                        $applist[$keys]['erji'] = $fa;
                    }
                    
                    $list[$key]['applist'] = $applist;
                }
            }
            
        }
        
       
        
        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;
        
        echo apireturn(200,'success',$data_rt);die;
    }
    
}