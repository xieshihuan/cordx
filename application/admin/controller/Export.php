<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\facade\Request;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Export extends Controller
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
            $where[] = ['day','<',$endDate];
        }
        if(isset($startDate)&&$startDate!=""&&isset($endDate)&&$endDate!="")
        {
            $endDate = date('Y-m-d',strtotime($endDate-'1day'));
            $where[] = ['day','between',[$startDate,$endDate]];
        }
        if(isset($startDate)&&$startDate==""&&isset($endDate)&&$endDate==""){
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
        
        //查询选中的人
        if(empty($a.$b)){
            $where[] = ['uid','>',0];
        }else{
            $where[] = ['uid','in',$a.$b];			
        }
        
        if(!empty($one)){
            $list = Db::name('check_count')
                    ->where($one)
                    ->where($where)
                    ->order('uid asc,day desc')
                    ->select();
        }else{
            $list = Db::name('check_count')
                    ->where($where)
                    ->order('uid asc,day desc')
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
            $group_names = '';
            foreach ($clist as $keys => $vals){
                $group_name = self::select_name($vals['catid']);
                $arr = explode('/',$group_name);
                $arrs = array_reverse($arr);
                $group_list = implode('/',$arrs);
                $group_list = ltrim($group_list,'/');
                
                $group_names = $group_names.' | '.$group_list;
                
                $group_list = ltrim($group_list,' | ');
            }
            //所属站点
            $list[$key]['clist'] = $group_list;
            
            $list[$key]['attendance_group_name'] = Db::name('attendance_group')->where('id',$val['attendance_group_id'])->value('title');
            $list[$key]['mobile'] = $uinfo['mobile'];
            $list[$key]['days'] =  days(date('N',strtotime($val['day'])));
            
            //查询班次
            $classinfo = Db::name('classes')->field('title,commuting_num,one_in,one_out,two_in,two_out')->where('id',$val['classesid'])->find();
            if($classinfo['commuting_num'] == 1){
                $list[$key]['class'] = $classinfo['title'].' '.$classinfo['one_in'].'~'.$classinfo['one_out'];
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
                    $list[$key]['one_in_status_name'] = '';
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
                    $list[$key]['one_out_status_name'] = '';
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
                $list[$key]['two_in_time'] = '';
                $list[$key]['two_in_status'] = '缺卡';
                $list[$key]['two_in_status_name'] = '';
                
                $list[$key]['two_out_time'] = '';
                $list[$key]['two_out_status'] = '缺卡';
                $list[$key]['two_out_status_name'] = '';
            }else{
                $list[$key]['class'] = $classinfo['title'].' '.$classinfo['one_in'].'~'.$classinfo['one_out'].';'.$classinfo['two_in'].'~'.$classinfo['two_out'];
                
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
                    $list[$key]['one_in_status_name'] = '';
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
                    $list[$key]['one_out_status_name'] = '';
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
                    $list[$key]['two_in_status_name'] = '';
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
                    $list[$key]['two_out_status_name'] = '';
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
        
        $fileName = '(每日统计 '.date("Y-m-d",time()) .'导出）';
        //实例化spreadsheet对象
        $spreadsheet = new Spreadsheet();
        //获取活动工作簿
        $sheet = $spreadsheet->getActiveSheet();
        //设置单元格表头
        $sheet->setCellValue('A1', '序号');
        $sheet->setCellValue('B1', '姓名');
        $sheet->setCellValue('C1', '所属站点');
        $sheet->setCellValue('D1', '账号');
        $sheet->setCellValue('E1', '考勤组');
        $sheet->setCellValue('F1', '日期');
        $sheet->setCellValue('G1', '班次');
        $sheet->setCellValue('H1', '第一次打卡时间');
        $sheet->setCellValue('I1', '结果');
        $sheet->setCellValue('J1', '第二次打卡时间');
        $sheet->setCellValue('K1', '结果');
        $sheet->setCellValue('L1', '第三次打卡时间');
        $sheet->setCellValue('M1', '结果');
        $sheet->setCellValue('N1', '第四次打卡时间');
        $sheet->setCellValue('O1', '结果');
        
        

        $i = 2;
        foreach($list as $key => $val){
            
            if(empty($val['one_in_time'])){
                $one_in_time = '/';
            }else{
                $one_in_time = $val['one_in_time'];
            }
            if(empty($val['one_out_time'])){
                $one_out_time = '/';
            }else{
                $one_out_time = $val['one_out_time'];
            }
            if(empty($val['two_in_time'])){
                $two_in_time = '/';
            }else{
                $two_in_time = $val['two_in_time'];
            }
            if(empty($val['two_out_time'])){
                $two_out_time = '/';
            }else{
                $two_out_time = $val['two_out_time'];
            }
            
            $sheet->SetCellValueByColumnAndRow('1',$i,$i-1);
            $sheet->SetCellValueByColumnAndRow('2',$i,$val['username']);
            $sheet->SetCellValueByColumnAndRow('3',$i,$val['clist']);
            $sheet->SetCellValueByColumnAndRow('4',$i,$val['mobile']);
            $sheet->SetCellValueByColumnAndRow('5',$i,$val['attendance_group_name']);
            $sheet->SetCellValueByColumnAndRow('6',$i,$val['day']);
            $sheet->SetCellValueByColumnAndRow('7',$i,$val['class']);
            $sheet->SetCellValueByColumnAndRow('8',$i,$one_in_time);
            if($val['one_in_status_name'] != ''){
                if($val['one_in_status_name'] == '国内出差' || $val['one_in_status_name'] == '国外出差'){
                    $sheet->SetCellValueByColumnAndRow('9',$i,$val['one_in_status_name'].' (无需打卡)');
                }else{
                    $sheet->SetCellValueByColumnAndRow('9',$i,$val['one_in_status_name']);
                }
            }else{
                $sheet->SetCellValueByColumnAndRow('9',$i,$val['one_in_status']);
            }
            $sheet->SetCellValueByColumnAndRow('10',$i,$one_out_time);
            if($val['one_out_status_name'] != ''){
                if($val['one_out_status_name'] == '国内出差' || $val['one_out_status_name'] == '国外出差'){
                    $sheet->SetCellValueByColumnAndRow('11',$i,$val['one_out_status_name'].' (无需打卡)');
                }else{
                    $sheet->SetCellValueByColumnAndRow('11',$i,$val['one_out_status_name']);
                }
                
            }else{
                $sheet->SetCellValueByColumnAndRow('11',$i,$val['one_out_status']);
            }
            $sheet->SetCellValueByColumnAndRow('12',$i,$two_in_time);
            if($val['two_in_status_name'] != ''){
                if($val['two_in_status_name'] == '国内出差' || $val['two_in_status_name'] == '国外出差'){
                    $sheet->SetCellValueByColumnAndRow('13',$i,$val['two_in_status_name'].' (无需打卡)');
                }else{
                    $sheet->SetCellValueByColumnAndRow('13',$i,$val['two_in_status_name']);
                }
            }else{
                $sheet->SetCellValueByColumnAndRow('13',$i,$val['two_in_status']);
            }
            
            $sheet->SetCellValueByColumnAndRow('14',$i,$two_out_time);
            if($val['two_out_status_name'] != ''){
                if($val['two_out_status_name'] == '国内出差' || $val['two_out_status_name'] == '国外出差'){
                    $sheet->SetCellValueByColumnAndRow('15',$i,$val['two_out_status_name'].' (无需打卡)');
                }else{
                    $sheet->SetCellValueByColumnAndRow('15',$i,$val['two_out_status_name']);
                }
            }else{
                $sheet->SetCellValueByColumnAndRow('15',$i,$val['two_out_status']);
            }
            
            $color1 = 'ffffff';
            if($val['one_in_status'] == '迟到')
               $color1 = 'FFFF33';
            if($val['one_in_status'] == '早退')
               $color1 = 'FFFF33';
            if($val['one_in_status'] == '缺卡' || $val['one_in_status'] == '半天缺卡')
               $color1 = 'FF0000';
            if($val['one_in_status_name'] == '国内出差' || $val['one_in_status_name'] == '国外出差')
               $color1 = 'E61ABD';
            if($val['one_in_status_name'] == '请假')
               $color1 = '22DDDD';
            
            $cell1 = 'I'.$i;
            //$spreadsheet->getActiveSheet()->getStyle($cell1)->getFont()->getColor()->setRGB($color1);
            $spreadsheet->getActiveSheet()->getStyle($cell1)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($color1);
            
            $color2 = 'ffffff';
            if($val['one_out_status'] == '迟到')
               $color2 = 'FFFF33';
            if($val['one_out_status'] == '早退')
               $color2 = 'FFFF33';
            if($val['one_out_status'] == '缺卡' || $val['one_out_status'] == '半天缺卡')
               $color2 = 'FF0000';
            if($val['one_out_status_name'] == '国内出差' || $val['one_out_status_name'] == '国外出差')
               $color2 = 'E61ABD';
            if($val['one_out_status_name'] == '请假')
               $color2 = '22DDDD';
            $cell2 = 'K'.$i;
            //$spreadsheet->getActiveSheet()->getStyle($cell2)->getFont()->getColor()->setRGB($color2);
            $spreadsheet->getActiveSheet()->getStyle($cell2)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($color2);
            
            $color3 = 'ffffff';
            if($val['two_in_status'] == '迟到')
               $color3 = 'FFFF33';
            if($val['two_in_status'] == '早退')
               $color3 = 'FFFF33';
            if($val['two_in_status'] == '缺卡' || $val['two_in_status'] == '半天缺卡')
               $color3 = 'FF0000';
            if($val['two_in_status_name'] == '国内出差' || $val['two_in_status_name'] == '国外出差')
               $color3 = 'E61ABD';
            if($val['two_in_status_name'] == '请假')
               $color3 = '22DDDD';
            $cell3 = 'M'.$i;
            //$spreadsheet->getActiveSheet()->getStyle($cell3)->getFont()->getColor()->setRGB($color3);
            $spreadsheet->getActiveSheet()->getStyle($cell3)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($color3);
            
            $color4 = 'ffffff';
            if($val['two_out_status'] == '迟到')
               $color4 = 'FFFF33';
            if($val['two_out_status'] == '早退')
               $color4 = 'FFFF33';
            if($val['two_out_status'] == '缺卡' || $val['two_out_status'] == '半天缺卡')
               $color4 = 'FF0000';
            if($val['two_out_status_name'] == '国内出差' || $val['two_out_status_name'] == '国外出差')
               $color4 = 'E61ABD';
            if($val['two_out_status_name'] == '请假')
               $color4 = '22DDDD';
            $cell4 = 'O'.$i;
            //$spreadsheet->getActiveSheet()->getStyle($cell4)->getFont()->getColor()->setRGB($color4);
            $spreadsheet->getActiveSheet()->getStyle($cell4)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($color4);
            $i++;
        
        }
        
        //设置自动列宽
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(5);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(10);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(10);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(10);
        
        // //MIME协议，文件的类型，不设置描绘默认html
        // header('Content-Type:application/vnd.openxmlformats-officedoument.spreadsheetml.sheet');
        // //MIME 协议的扩展
        // header("Content-Disposition:attachment;filename={$fileName}.xlsx");
        // //缓存控制
        // header('Cache-Control:max-age=0');
        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet,'Xlsx');
        
        $writer->save('./uploads/'.$fileName.'.Xlsx');
        
        $url = Request::domain().'/uploads/'.$fileName.'.Xlsx';
        
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $url;
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
        
    public function index_ceshi(){
        //接收前端参数 查询数据出来 目前演示为测试数据
        $data = [
            [
                "id" => 1,
                "name" => "小黄",
                "age" => "10"
            ],
            [
                "id" => 2,
                'name' => "小红",
                "age" => "11",
            ],
            [
                "id" => 3,
                "name" => "小黑",
                "age" => "12"
            ]
        ];
        
        $fileName = '('.date("Y-m-d",time()) .'导出）';
        //实例化spreadsheet对象
        $spreadsheet = new Spreadsheet();
        //获取活动工作簿
        $sheet = $spreadsheet->getActiveSheet();
        //设置单元格表头
        $sheet->setCellValue('A1', 'id');
        $sheet->setCellValue('B1', '姓名');
        $sheet->setCellValue('C1', '年龄');
      
 
        $i=2;
        foreach($data as $key => $val){
           
            $sheet->SetCellValueByColumnAndRow('1',$i,$val['id']);
            $sheet->SetCellValueByColumnAndRow('2',$i,$val['name']);
            $sheet->SetCellValueByColumnAndRow('3',$i,$val['age']);
            
            $color = '000000';
            if($val['name'] == '小黄')
               $color = 'CCFF33';
            if($val['name'] == '小红')
               $color = 'B8002E';
            if($val['name'] == '小黑')
               $color = '000000';
            
            $data[$key]['color'] = $color;
            
            $cell = 'B'.$i;
            $spreadsheet->getActiveSheet()->getStyle($cell)->getFont()->getColor()->setRGB($color);
            
            $i++;
        
        }
        
        //MIME协议，文件的类型，不设置描绘默认html
        header('Content-Type:application/vnd.openxmlformats-officedoument.spreadsheetml.sheet');
        //MIME 协议的扩展
        header("Content-Disposition:attachment;filename={$fileName}.xlsx");
        //缓存控制
        header('Cache-Control:max-age=0');
        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet,'Xlsx');
        $writer->save('php://output');

    }

}