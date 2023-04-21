<?php
/**
 * +----------------------------------------------------------------------
 * | 首页控制器
 * +----------------------------------------------------------------------
 */
namespace app\api\controller;
use app\api\model\Admin;
use app\api\model\AuthRule;
use app\common\model\Users;
use think\Db;
use think\facade\Env;
use think\facade\Session;
use think\facade\Request;
use think\facade\validate; 

class Check extends Base
{
    
    /* 获取打卡地点 计算两组经纬度坐标之间的距离 查询是否在范围内(自由班制、上下班交替打卡 不限制次数)
     * @param $lat 纬度
     * @param $lng 经度
     */
    public function index_bf(){
        
        $data = Request::param();
        
        $today=strtotime(date('Y-m-d 00:00:00'));
        
        $map = [];
        $map[] = ['uid','=',$this->user_id];
        $map[] = ['sub_time','>=',$today];
        
        //打卡记录
        $check_log = Db::name('check_log')->where($map)->order('sub_time asc')->select();
        if(!empty($check_log)){
            foreach($check_log as $key => $val){
                $check_log[$key]['seconds'] = time()-$val['sub_time'];
                $check_log[$key]['sub_time'] = date('H:i',$val['sub_time']);
            }
        }
        
        //获取地点列表循环查询当前最近地点
        $list = Db::name('location')->order('id asc')->select();
         
        $title = '';
        $address = '';
        $is_distance = 0;
        foreach($list as $key => $val){
             
            //获取实际距离
            $jl = getDistance($data['lat'],$data['lng'],$val['lat'],$val['lng'],$len_type = 1,$decimal = 2);
          
            if($jl <= $val['distance']){
                $title = $val['title'];
                $address = $val['address'];
                $is_distance++;
            }
        }
        
        
        $data_z['status'] = 200;
        $data_z['msg'] = 'success';
        
        $a['is_distance'] = $is_distance;
        $a['title'] = $title;
        $a['address'] = $address;
        $a['check_log'] = $check_log;
        
        $data_z['data'] = $a;
        
        return json_encode($data_z,true);
         
    }
    
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
                        $list[$key]['one_out_name'] = '次日 '.$v['one_out'];
                        $list[$key]['two_in_name'] = '次日 '.$v['two_in'];
                        $list[$key]['two_out_name'] = '次日 '.$v['two_out'];
                    }else if($two_in < $one_out){
                        $list[$key]['one_out_name'] = $v['one_out'];
                        $list[$key]['two_in_name'] = '次日 '.$v['two_in'];
                        $list[$key]['two_out_name'] = '次日 '.$v['two_out'];
                    }else if($two_out < $two_in){
                        $list[$key]['one_out_name'] = $v['one_out'];
                        $list[$key]['two_in_name'] = $v['two_in'];
                        $list[$key]['two_out_name'] = '次日 '.$v['two_out'];
                    }else{
                        $list[$key]['one_out_name'] = $v['one_out'];
                        $list[$key]['two_in_name'] = $v['two_in'];
                        $list[$key]['two_out_name'] = $v['two_out'];
                    }
                    
                    $infos = Db::name('check_log')->where('uid',$this->user_id)->where('attendance_group_id',$info['attendance_group_id'])->order('sub_time desc')->find();
                    
                    if($infos){
                        if($infos['classesid'] == $v['id']){
                            $list[$key]['is_checked'] = 1;
                        }else{
                            $list[$key]['is_checked'] = 0;
                        }
                    }else{
                        if($key == 0){
                            $list[$key]['is_checked'] = 1;
                        }else{
                            $list[$key]['is_checked'] = 0;
                        }
                    }
                    
                }
                $data['classeslist'] = $list;
                
                $data['attendance_group_title'] = Db::name('attendance_group')->where('id',$info['attendance_group_id'])->value('title');
                $data['attendance_group_id'] = $info['attendance_group_id'];
                
                $location_list = Db::name('location')->where('id','in',Db::name('attendance_group')->where('id',$info['attendance_group_id'])->value('location_ids'))->select();
                $data['location_list'] = $location_list;
                echo apireturn(200,'success',$data);die;
            }else{
                echo apireturn(201,'当前考勤组无配置班次','');die;
            }
        }else{
            echo apireturn(201,'请联系管理员设置考勤组','');die;
        }
    }
    
    
    /* 获取打卡地点 计算两组经纬度坐标之间的距离 查询是否在范围内(排班制)
     * @param $lat 纬度
     * @param $lng 经度
     * @param $attendance_group_id 考勤组id
     * @param $classesid 班次id
     */
    public function indexs(){
        
        $data = Request::param();
        
        //获取考勤组信息
        if(empty($data['attendance_group_id'])){
            echo apireturn(201,'请选择考勤组','');die;
        }else{
            $attendance_group_info = Db::name('attendance_group')->where('id',$data['attendance_group_id'])->find();
        }
        
        
        //获取班次信息
        if(empty($data['classesid'])){
            echo apireturn(201,'请选择班次','');die;
        }else{
            $classesinfo = Db::name('classes')->where('id',$data['classesid'])->find();
        }
        
        $disable = 0;
        
        $one_in = strtotime($classesinfo['one_in']);
        $one_out = strtotime($classesinfo['one_out']);
        $two_in = strtotime($classesinfo['two_in']);
        $two_out = strtotime($classesinfo['two_out']);
        
    
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
        
        //判断第几次打卡时间
        $start1 = strtotime($classesinfo['one_in'].'-'.$classesinfo['one_in3']."min");
        $end1 = strtotime($classesinfo['one_in'].'+'.$classesinfo['one_in2']."min");
        $start2 = strtotime($classesinfo['one_out'].'-'.$classesinfo['one_out2']."min");
        $end2 = strtotime($classesinfo['one_out'].'+'.$classesinfo['one_out3']."min");
        $start3 = strtotime($classesinfo['two_in'].'-'.$classesinfo['two_in3']."min");
        $end3 = strtotime($classesinfo['two_in'].'+'.$classesinfo['two_in2']."min");
        $start4 = strtotime($classesinfo['two_out'].'-'.$classesinfo['two_out2']."min");
        $end4 = strtotime($classesinfo['two_out'].'+'.$classesinfo['two_out3']."min");
        
        $time = time();
        if($time > $start1 && $time < $end1){
            $check_num = 1;
        }else if($time > $start2 && $time < $end2){
            $check_num = 2;
        }else if($time > $start3 && $time < $end3){
            $check_num = 3;
        }else if($time > $start4 && $time < $end4){
            $check_num = 4;
        }else{
            $check_num = 0;
            $disable = 1;
        }
        
        
        //当前天数
        $dtime = date('d',time());
    
        $day = '';
        
        $arr = array();
        
        if($classesinfo['commuting_num'] == 1){
            //判断查询哪天的打卡记录
            $out_later = $classesinfo['one_out'].'+'.$classesinfo['one_out3']."min";
            $out_later_day = date('d',strtotime($out_later));
            $today = date('d',time());
            
            if(strtotime($classesinfo['one_out']) < strtotime($classesinfo['one_in'])){
                if($today != $out_later_day){
                    if(time()<strtotime($classesinfo['one_out'].'+'.$classesinfo['one_out3']."min")){
                        $day = date('Y-m-d',time()-86400);
                    }else{
                        $day = date('Y-m-d',time());
                    }
                }else{
                    if(time()<strtotime($classesinfo['one_out'].'+'.$classesinfo['one_out3']."min")){
                        $day = date('Y-m-d',time()-86400);
                    }else{
                        $day = date('Y-m-d',time());
                    }
                }
            }else{
                $day = date('Y-m-d',time());
            }
            
            //第一次上班
            $arr[0]['check_num'] = 1;
            $arr[0]['name'] = '上班';
            //查询有无打卡记录
            $one_in = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('attendance_group_id',$data['attendance_group_id'])->where('classesid',$data['classesid'])->where('check_num',1)->find();
            if($one_in){
                $arr[0]['is_check'] = 1;
                $arr[0]['zctime'] = $one_in['zcshijian'];
                $arr[0]['sjtime'] = $one_in['shijian'];
                $arr[0]['address'] = $one_in['title'];
                $arr[0]['status'] = $one_in['status'];
                if($check_num == 1){
                    $disable = 1;
                }
            }else{
                if($check_num == 1){
                    $arr[0]['is_check'] = 2;
                    $arr[0]['is_checks'] = 1;
                    $arr[0]['zctime'] = $classesinfo['one_in'];
                }else{
                    if($time < strtotime($day.date('H:i:s',$start1))){
                        $arr[0]['is_check'] = 2;
                        $arr[0]['is_checks'] = 2;
                        $arr[0]['zctime'] = $classesinfo['one_in'];
                    }else if($time > strtotime($day.date('H:i:s',$end1))){
                        $arr[0]['is_check'] = 2;
                        $arr[0]['is_checks'] = 3;
                        $arr[0]['zctime'] = $classesinfo['one_in'];
                    }
                }
            }
            
            //第一次下班
            $arr[1]['check_num'] = 2;
            $arr[1]['name'] = '下班';
            //查询有无打卡记录
            $one_out = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('attendance_group_id',$data['attendance_group_id'])->where('classesid',$data['classesid'])->where('check_num',2)->find();
            if($one_out){
                $arr[1]['is_check'] = 1;
                $arr[1]['zctime'] = $one_out['zcshijian'];
                $arr[1]['sjtime'] = $one_out['shijian'];
                $arr[1]['address'] = $one_out['title'];
                $arr[1]['status'] = $one_out['status'];
                if($check_num == 2){
                    $disable = 1;
                }
            }else{
                if($check_num == 2){
                    $arr[1]['is_check'] = 2;
                    $arr[1]['is_checks'] = 1;
                    $arr[1]['zctime'] = $classesinfo['one_out'];
                }else{
                    if($one_out_type == 1){
                        if($time < strtotime($day.date('H:i:s',$start2))+86400){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 2;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }else if($time > strtotime($day.date('H:i:s',$end2))+86400){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 3;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }
                    }else{
                        if($time < strtotime($day.date('H:i:s',$start2))){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 2;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }else if($time > strtotime($day.date('H:i:s',$end2))){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 3;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }
                    }
                }
            }
        }else{
            
            //判断查询哪天的打卡记录
            $out_later = $classesinfo['two_out'].'+'.$classesinfo['two_out3']."min";
            $out_later_day = date('d',strtotime($out_later));
            $today = date('d',time());
            if(strtotime($classesinfo['two_out']) < strtotime($classesinfo['one_in'])){
                if($today != $out_later_day){
                    if(time()<strtotime($classesinfo['two_out'].'+'.$classesinfo['two_out3']."min")){
                        $day = date('Y-m-d',time()-86400);
                    }else{
                        $day = date('Y-m-d',time());
                    }
                }else{
                    if(time()<strtotime($classesinfo['two_out'].'+'.$classesinfo['two_out3']."min")){
                        $day = date('Y-m-d',time()-86400);
                    }else{
                        $day = date('Y-m-d',time());
                    }
                }
            }else{
                $day = date('Y-m-d',time());
            }
            
            //第一次上班
            $arr[0]['check_num'] = 1;
            $arr[0]['name'] = '上班';
            //查询有无打卡记录
            $one_in = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('attendance_group_id',$data['attendance_group_id'])->where('classesid',$data['classesid'])->where('check_num',1)->find();
            if($one_in){
                $arr[0]['is_check'] = 1;
                $arr[0]['zctime'] = $one_in['zcshijian'];
                $arr[0]['sjtime'] = $one_in['shijian'];
                $arr[0]['address'] = $one_in['title'];
                $arr[0]['status'] = $one_in['status'];
                if($check_num == 1){
                    $disable = 1;
                }
            }else{
                if($check_num == 1){
                    $arr[0]['is_check'] = 2;
                    $arr[0]['is_checks'] = 1;
                    $arr[0]['zctime'] = $classesinfo['one_in'];
                }else{
                    if($time < strtotime($day.date('H:i:s',$start1))){
                        $arr[0]['is_check'] = 2;
                        $arr[0]['is_checks'] = 2;
                        $arr[0]['zctime'] = $classesinfo['one_in'];
                    }else if($time > strtotime($day.date('H:i:s',$end1))){
                        $arr[0]['is_check'] = 2;
                        $arr[0]['is_checks'] = 3;
                        $arr[0]['zctime'] = $classesinfo['one_in'];
                    }
                }
            }
            
            //第一次下班
            $arr[1]['check_num'] = 2;
            $arr[1]['name'] = '下班';
            //查询有无打卡记录
            $one_out = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('attendance_group_id',$data['attendance_group_id'])->where('classesid',$data['classesid'])->where('check_num',2)->find();
            if($one_out){
                $arr[1]['is_check'] = 1;
                $arr[1]['zctime'] = $one_out['zcshijian'];
                $arr[1]['sjtime'] = $one_out['shijian'];
                $arr[1]['address'] = $one_out['title'];
                $arr[1]['status'] = $one_out['status'];
                if($check_num == 2){
                    $disable = 1;
                }
            }else{
                if($check_num == 2){
                    $arr[1]['is_check'] = 2;
                    $arr[1]['is_checks'] = 1;
                    $arr[1]['zctime'] = $classesinfo['one_out'];
                }else{
                    if($one_out_type == 1){
                        if($time < strtotime($day.date('H:i:s',$start2))+86400){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 2;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }else if($time > strtotime($day.date('H:i:s',$end2))+86400){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 3;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }
                    }else{
                        if($time < strtotime($day.date('H:i:s',$start2))){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 2;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }else if($time > strtotime($day.date('H:i:s',$end2))){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 3;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }
                    }
                }
            }
            
            //第二次上班
            $arr[2]['check_num'] = 3;
            $arr[2]['name'] = '上班';
            //查询有无打卡记录
            $two_in = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('attendance_group_id',$data['attendance_group_id'])->where('classesid',$data['classesid'])->where('check_num',3)->find();
            if($two_in){
                $arr[2]['is_check'] = 1;
                $arr[2]['zctime'] = $two_in['zcshijian'];
                $arr[2]['sjtime'] = $two_in['shijian'];
                $arr[2]['address'] = $two_in['title'];
                $arr[2]['status'] = $two_in['status'];
                if($check_num == 3){
                    $disable = 1;
                }
            }else{
                if($check_num == 3){
                    $arr[2]['is_check'] = 2;
                    $arr[2]['is_checks'] = 1;
                    $arr[2]['zctime'] = $classesinfo['two_in'];
                }else{
                    
                    if($two_in_type == 1){
                        
                        if($time < strtotime($day.date('H:i:s',$start3))+86400){
                            $arr[2]['is_check'] = 2;
                            $arr[2]['is_checks'] = 2;
                            $arr[2]['zctime'] = $classesinfo['two_in'];
                        }else if($time > strtotime($day.date('H:i:s',$end3))+86400){
                            $arr[2]['is_check'] = 2;
                            $arr[2]['is_checks'] = 3;
                            $arr[2]['zctime'] = $classesinfo['two_in'];
                        }
                    }else{
                        if($time < strtotime($day.date('H:i:s',$start3))){
                            $arr[2]['is_check'] = 2;
                            $arr[2]['is_checks'] = 2;
                            $arr[2]['zctime'] = $classesinfo['two_in'];
                        }else if($time > strtotime($day.date('H:i:s',$end3))){
                            $arr[2]['is_check'] = 2;
                            $arr[2]['is_checks'] = 3;
                            $arr[2]['zctime'] = $classesinfo['two_in'];
                        }
                    }
                }
            }
            
            //第二次下班
            $arr[3]['check_num'] = 4;
            $arr[3]['name'] = '下班';
            //查询有无打卡记录
            $two_out = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('attendance_group_id',$data['attendance_group_id'])->where('classesid',$data['classesid'])->where('check_num',4)->find();
            if($two_out){
                $arr[3]['is_check'] = 1;
                $arr[3]['zctime'] = $two_out['zcshijian'];
                $arr[3]['sjtime'] = $two_out['shijian'];
                $arr[3]['address'] = $two_out['title'];
                $arr[3]['status'] = $two_out['status'];
                if($check_num == 4){
                    $disable = 1;
                }
            }else{
                if($check_num == 4){
                    $arr[3]['is_check'] = 2;
                    $arr[3]['is_checks'] = 1;
                    $arr[3]['zctime'] = $classesinfo['two_out'];
                }else{
                    
                    if($two_out_type == 1){
                            
                        if($time < strtotime($day.date('H:i:s',$start4))+86400){
                            $arr[3]['is_check'] = 2;
                            $arr[3]['is_checks'] = 2;
                            $arr[3]['zctime'] = $classesinfo['two_out'];
                        }else if($time > strtotime($day.date('H:i:s',$end4))+86400){
                            $arr[3]['is_check'] = 2;
                            $arr[3]['is_checks'] = 3;
                            $arr[3]['zctime'] = $classesinfo['two_out'];
                        }
                    }else{
                        
                        if($time < strtotime($day.date('H:i:s',$start4))){
                            $arr[3]['is_check'] = 2;
                            $arr[3]['is_checks'] = 2;
                            $arr[3]['zctime'] = $classesinfo['two_out'];
                        }else if($time > strtotime($day.date('H:i:s',$end4))){
                            $arr[3]['is_check'] = 2;
                            $arr[3]['is_checks'] = 3;
                            $arr[3]['zctime'] = $classesinfo['two_out'];
                        }
                    }
                }
            }
        }
        
        $today=strtotime(date('Y-m-d 00:00:00'));
        
        $map = [];
        $map[] = ['uid','=',$this->user_id];
        $map[] = ['sub_time','>=',$today];
        
        //打卡记录
        // $check_log = Db::name('check_log')->where($map)->order('sub_time asc')->select();
        // if(!empty($check_log)){
        //     foreach($check_log as $key => $val){
        //         $check_log[$key]['seconds'] = time()-$val['sub_time'];
        //         $check_log[$key]['sub_time'] = date('H:i',$val['sub_time']);
        //     }
        // }
        
        //获取地点列表循环查询当前最近地点
        $list = Db::name('location')->where('id','in',$attendance_group_info['location_ids'])->order('id asc')->select();
         
        $title = '';
        $address = '';
        $is_distance = 0;
        foreach($list as $key => $val){
             
            //获取实际距离
            $jl = getDistance($data['lat'],$data['lng'],$val['lat'],$val['lng'],$len_type = 1,$decimal = 2);
          
            if($jl <= $val['distance']){
                $title = $val['title'];
                $address = $val['address'];
                $is_distance++;
            }
        }
        
        
        $data_z['status'] = 200;
        $data_z['msg'] = 'success';
        
        
        if($is_distance >= 1){
            $is_distance = 1;
        }
        
        $a['is_distance'] = $is_distance;
        $a['title'] = $title;
        $a['address'] = $address;
        $a['check_log'] = $arr;
        $data_z['data'] = $a;
        
        $data_z['check_num'] = $check_num;
        $data_z['disable'] = $disable;
        
        return json_encode($data_z,true);
         
    }
    
    //打卡
    public function check(){
        
        $data = Request::param();
        //获取考勤组信息
        if(empty($data['attendance_group_id'])){
            echo apireturn(201,'请选择考勤组','');die;
        }else{
            $attendance_group_info = Db::name('attendance_group')->where('id',$data['attendance_group_id'])->find();
        }
        
        //获取班次信息
        if(empty($data['classesid'])){
            echo apireturn(201,'请选择班次','');die;
        }else{
            $classesinfo = Db::name('classes')->where('id',$data['classesid'])->find();
        }
        
        //获取班次信息
        if(empty($data['check_num'])){
            echo apireturn(201,'请选择打卡次数','');die;
        }
        
        $today=strtotime(date('Y-m-d 00:00:00'));
        
        $map = [];
        $map[] = ['uid','=',$this->user_id];
        $map[] = ['sub_time','>=',$today];
       
        $sub_time = Db::name('check_log')->where($map)->order('sub_time desc')->value('sub_time');
        
        if(!empty($sub_time) && time() - $sub_time < 10){
            echo apireturns(200,201,'请求过于频繁，请稍后再试','');die;
        }else{
            
            if($classesinfo['commuting_num'] == 1){
                //判断查询哪天的打卡记录
                $out_later = $classesinfo['one_out'].'+'.$classesinfo['one_out3']."min";
                $out_later_day = date('d',strtotime($out_later));
                $today = date('d',time());
                
                if(strtotime($classesinfo['one_out']) < strtotime($classesinfo['one_in'])){
                    if($today != $out_later_day){
                        if(time()<strtotime($classesinfo['one_out'].'+'.$classesinfo['one_out3']."min")){
                            $day = date('Y-m-d',time()-86400);
                        }else{
                            $day = date('Y-m-d',time());
                        }
                    }else{
                        if(time()<strtotime($classesinfo['one_out'].'+'.$classesinfo['one_out3']."min")){
                            $day = date('Y-m-d',time()-86400);
                        }else{
                            $day = date('Y-m-d',time());
                        }
                    }
                }else{
                    $day = date('Y-m-d',time());
                }
            }else{
                //判断查询哪天的打卡记录
                $out_later = $classesinfo['two_out'].'+'.$classesinfo['two_out3']."min";
                $out_later_day = date('d',strtotime($out_later));
                $today = date('d',time());
                if(strtotime($classesinfo['two_out']) < strtotime($classesinfo['one_in'])){
                    if($today != $out_later_day){
                        if(time()<strtotime($classesinfo['two_out'].'+'.$classesinfo['two_out3']."min")){
                            $day = date('Y-m-d',time()-86400);
                        }else{
                            $day = date('Y-m-d',time());
                        }
                    }else{
                        if(time()<strtotime($classesinfo['two_out'].'+'.$classesinfo['two_out3']."min")){
                            $day = date('Y-m-d',time()-86400);
                        }else{
                            $day = date('Y-m-d',time());
                        }
                    }
                }else{
                    $day = date('Y-m-d',time());
                }
            }
            
            //判断当前时间是否打卡过
            $checkinfo = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('attendance_group_id',$data['attendance_group_id'])->where('classesid',$data['classesid'])->where('check_num',$data['check_num'])->find();
            if($checkinfo){
                echo apireturns(200,201,'已打卡','');die;
            }else{
                //判断打卡是否正常或者迟到早退等状态
                if($data['check_num'] == 1){
                    $onein = strtotime($classesinfo['one_in'].'+'.$classesinfo['one_in1']."min".'+'."1min");
                    $start = strtotime($classesinfo['one_in'].'-'.$classesinfo['one_in3']."min");
                    $end = strtotime($classesinfo['one_in'].'+'.$classesinfo['one_in2']."min");
                    $time = time();
                    $data['zcshijian'] = $classesinfo['one_in'];
                    if($time > $start && $time < $end){
                        if($time < $onein){
                            $status = 1;
                        }else{
                            $status = 2;
                        }
                    }else{
                        echo apireturns(200,203,'未到打卡时间:'.date('H:i',$start).'-'.date('H:i',$end),'');die;
                    }
                }else if($data['check_num'] == 2){
                    $oneout = strtotime($classesinfo['one_out'].'-'.$classesinfo['one_out1']."min");
                    $start = strtotime($classesinfo['one_out'].'-'.$classesinfo['one_out2']."min");
                    $end = strtotime($classesinfo['one_out'].'+'.$classesinfo['one_out3']."min");
                    $time = time();
                    $data['zcshijian'] = $classesinfo['one_out'];
                    if($time > $start && $time < $end){
                        if($time > $oneout){
                            $status = 1;
                        }else{
                            $status = 3;
                        }
                    }else{
                        echo apireturns(200,203,'未到打卡时间:'.date('H:i',$start).'-'.date('H:i',$end),'');die;
                    }
                }else if($data['check_num'] == 3){
                    $twoin = strtotime($classesinfo['two_in'].'+'.$classesinfo['two_in1']."min".'+'."1min");
                    $start = strtotime($classesinfo['two_in'].'-'.$classesinfo['two_in3']."min");
                    $end = strtotime($classesinfo['two_in'].'+'.$classesinfo['two_in2']."min");
                    $time = time();
                    $data['zcshijian'] = $classesinfo['two_in'];
                    if($time > $start && $time < $end){
                        if($time < $twoin){
                            $status = 1;
                        }else{
                            $status = 2;
                        }
                    }else{
                        echo apireturns(200,203,'未到打卡时间:'.date('H:i',$start).'-'.date('H:i',$end),'');die;
                    }
                }else{
                    $twoout = strtotime($classesinfo['two_out'].'-'.$classesinfo['two_out1']."min");
                    $start = strtotime($classesinfo['two_out'].'-'.$classesinfo['two_out2']."min");
                    $end = strtotime($classesinfo['two_out'].'+'.$classesinfo['two_out3']."min");
                    $time = time();
                    $data['zcshijian'] = $classesinfo['two_out'];
                    if($time > $start && $time < $end){
                        if($time > $twoout){
                            $status = 1;
                        }else{
                            $status = 3;
                        }
                    }else{
                        echo apireturns(200,203,'未到打卡时间:'.date('H:i',$start).'-'.date('H:i',$end),'');die;
                    }
                }
                
            }
            
            $data['riqi'] = $day;
            $data['shijian'] = date('H:i',time());
            $data['status'] = $status;
            
            $result = is_distance($data['lat'],$data['lng'],$this->user_id);
            
            if($result['is_distance'] > 0){
                $data['title'] = $result['title'];
                $data['address'] = $result['address'];
            }
            
            $data['uid'] = $this->user_id;
            $data['sub_time'] = time();
            $data['create_time'] = time();
            $data['update_time'] = time();
            if(Db::name('check_log')->insert($data)){
                
                //更新每日考勤统计
                update_check($this->user_id,$data['attendance_group_id'],$data['classesid'],$day,$status);
                
                if($data['check_num']%2 == 0){
                    $data_rt['Hint'] = '工作辛苦了，感谢你的努力～';
                    $text = '下班打卡成功';
                }else{
                    $data_rt['Hint'] = '甭管三七二十一，撸起袖子加油干！';
                    $text = '上班打卡成功';
                }
                
                $data_rt['sub_time'] = date('H:i',time());
                echo apireturns(200,200,$text,$data_rt);
                die;
                
            }else{
                
                echo apireturns(200,202,'打卡失败','');die;
                
            }
            
        }
        
    }
    
    //获取考勤统计
    public function check_log(){
        
        $date = Request::param('date');
        $classesid = Request::param('classesid');
        if(empty($date)){
            $date = date('Y-m-d',time());
        }
        
        if(empty($classesid)){
            echo apireturn(201,'请选择班次','');die;
        }else{
            $classesinfo = Db::name('classes')->where('id',$classesid)->find();
        }
        
        $disable = 0;
        
        $one_in = strtotime($classesinfo['one_in']);
        $one_out = strtotime($classesinfo['one_out']);
        $two_in = strtotime($classesinfo['two_in']);
        $two_out = strtotime($classesinfo['two_out']);
        
    
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
        
        //判断第几次打卡时间
        $start1 = strtotime($date.$classesinfo['one_in'].'-'.$classesinfo['one_in3'].'min');
        $end1 = strtotime($date.$classesinfo['one_in'].'+'.$classesinfo['one_in2']."min");
        $start2 = strtotime($date.$classesinfo['one_out'].'-'.$classesinfo['one_out2']."min");
        $end2 = strtotime($date.$classesinfo['one_out'].'+'.$classesinfo['one_out3']."min");
        $start3 = strtotime($date.$classesinfo['two_in'].'-'.$classesinfo['two_in3']."min");
        $end3 = strtotime($date.$classesinfo['two_in'].'+'.$classesinfo['two_in2']."min");
        $start4 = strtotime($date.$classesinfo['two_out'].'-'.$classesinfo['two_out2']."min");
        $end4 = strtotime($date.$classesinfo['two_out'].'+'.$classesinfo['two_out3']."min");
        
        $time = time();
        if($time > $start1 && $time < $end1){
            $check_num = 1;
        }else if($time > $start2 && $time < $end2){
            $check_num = 2;
        }else if($time > $start3 && $time < $end3){
            $check_num = 3;
        }else if($time > $start4 && $time < $end4){
            $check_num = 4;
        }else{
            $check_num = 0;
            $disable = 1;
        }
        
        $time = strtotime($date);
        
        //当前天数
        $dtime = date('d',time());
    
        $day = '';
        
        $arr = array();
        
        if($classesinfo['commuting_num'] == 1){
            //判断查询哪天的打卡记录
            $out_later = $classesinfo['one_out'].'+'.$classesinfo['one_out3']."min";
            $out_later_day = date('d',strtotime($out_later));
            $today = date('d',$time);
            
            if(strtotime($classesinfo['one_out']) < strtotime($classesinfo['one_in'])){
                if($today != $out_later_day){
                    if(time()<strtotime($classesinfo['one_out'].'+'.$classesinfo['one_out3']."min")){
                        $day = date('Y-m-d',$time-86400);
                    }else{
                        $day = date('Y-m-d',$time);
                    }
                }else{
                    if(time()<strtotime($classesinfo['one_out'].'+'.$classesinfo['one_out3']."min")){
                        $day = date('Y-m-d',$time-86400);
                    }else{
                        $day = date('Y-m-d',$time);
                    }
                }
            }else{
                $day = date('Y-m-d',$time);
            }
            
            //第一次上班
            $arr[0]['check_num'] = 1;
            $arr[0]['name'] = '上班';
            //查询有无打卡记录
            $one_in = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('classesid',$classesid)->where('check_num',1)->find();
            if($one_in){
                $arr[0]['is_check'] = 1;
                $arr[0]['zctime'] = $one_in['zcshijian'];
                $arr[0]['sjtime'] = $one_in['shijian'];
                $arr[0]['address'] = $one_in['title'];
                $arr[0]['status'] = $one_in['status'];
                if($check_num == 1){
                    $disable = 1;
                }
            }else{
                if($check_num == 1){
                    $arr[0]['is_check'] = 2;
                    $arr[0]['is_checks'] = 1;
                    $arr[0]['zctime'] = $classesinfo['one_in'];
                }else{
                    if(time() < strtotime($day.date('H:i:s',$start1))){
                        $arr[0]['is_check'] = 2;
                        $arr[0]['is_checks'] = 2;
                        $arr[0]['zctime'] = $classesinfo['one_in'];
                    }else if(time() > strtotime($day.date('H:i:s',$end1))){
                        $arr[0]['is_check'] = 2;
                        $arr[0]['is_checks'] = 3;
                        $arr[0]['zctime'] = $classesinfo['one_in'];
                    }
                }
            }
            
            //第一次下班
            $arr[1]['check_num'] = 2;
            $arr[1]['name'] = '下班';
            //查询有无打卡记录
            $one_out = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('classesid',$classesid)->where('check_num',2)->find();
            if($one_out){
                $arr[1]['is_check'] = 1;
                $arr[1]['zctime'] = $one_out['zcshijian'];
                $arr[1]['sjtime'] = $one_out['shijian'];
                $arr[1]['address'] = $one_out['title'];
                $arr[1]['status'] = $one_out['status'];
                if($check_num == 2){
                    $disable = 1;
                }
            }else{
                if($check_num == 2){
                    if(time() < strtotime($day.$classesinfo['one_out'])){
                        $arr[1]['is_check'] = 2;
                        $arr[1]['is_checks'] = 1;
                        $arr[1]['zctime'] = $classesinfo['one_out'];
                    }else{
                        $arr[1]['is_check'] = 2;
                        $arr[1]['is_checks'] = 3;
                        $arr[1]['zctime'] = $classesinfo['one_out'];
                    }
                }else{
                    if($one_out_type == 1){
                        if(time() < strtotime($day.date('H:i:s',$start2))+86400){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 2;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }else if(time() > strtotime($day.date('H:i:s',$end2))+86400){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 3;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }
                    }else{
                        if(time() < strtotime($day.date('H:i:s',$start2))){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 2;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }else if(time() > strtotime($day.date('H:i:s',$end2))){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 3;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }
                    }
                }
            }
        }else{
            
            //判断查询哪天的打卡记录
            $out_later = $classesinfo['two_out'].'+'.$classesinfo['two_out3']."min";
            $out_later_day = date('d',strtotime($out_later));
            $today = date('d',$time);
            
            if(strtotime($classesinfo['two_out']) < strtotime($classesinfo['one_in'])){
                if($today != $out_later_day){
                    if(time()<strtotime($classesinfo['two_out'].'+'.$classesinfo['two_out3']."min")){
                        $day = date('Y-m-d',$time-86400);
                    }else{
                        $day = date('Y-m-d',$time);
                    }
                }else{
                    if(time()<strtotime($classesinfo['two_out'].'+'.$classesinfo['two_out3']."min")){
                        $day = date('Y-m-d',$time-86400);
                    }else{
                        $day = date('Y-m-d',$time);
                    }
                }
            }else{
                $day = date('Y-m-d',$time);
            }
            
            //第一次上班
            $arr[0]['check_num'] = 1;
            $arr[0]['name'] = '上班';
            //查询有无打卡记录
            $one_in = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('classesid',$classesid)->where('check_num',1)->find();
            if($one_in){
                $arr[0]['is_check'] = 1;
                $arr[0]['zctime'] = $one_in['zcshijian'];
                $arr[0]['sjtime'] = $one_in['shijian'];
                $arr[0]['address'] = $one_in['title'];
                $arr[0]['status'] = $one_in['status'];
                if($check_num == 1){
                    $disable = 1;
                }
            }else{
                if($check_num == 1){
                    $arr[0]['is_check'] = 2;
                    $arr[0]['is_checks'] = 1;
                    $arr[0]['zctime'] = $classesinfo['one_in'];
                }else{
                    if(time() < strtotime($day.date('H:i:s',$start1))){
                        $arr[0]['is_check'] = 2;
                        $arr[0]['is_checks'] = 2;
                        $arr[0]['zctime'] = $classesinfo['one_in'];
                    }else if(time() > strtotime($day.date('H:i:s',$end1))){
                        $arr[0]['is_check'] = 2;
                        $arr[0]['is_checks'] = 3;
                        $arr[0]['zctime'] = $classesinfo['one_in'];
                    }
                }
            }
            
            //第一次下班
            $arr[1]['check_num'] = 2;
            $arr[1]['name'] = '下班';
            //查询有无打卡记录
            $one_out = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('classesid',$classesid)->where('check_num',2)->find();
            if($one_out){
                $arr[1]['is_check'] = 1;
                $arr[1]['zctime'] = $one_out['zcshijian'];
                $arr[1]['sjtime'] = $one_out['shijian'];
                $arr[1]['address'] = $one_out['title'];
                $arr[1]['status'] = $one_out['status'];
                if($check_num == 2){
                    $disable = 1;
                }
            }else{
                if($check_num == 2){
                    if(time() < strtotime($day.$classesinfo['one_out'])){
                        $arr[1]['is_check'] = 2;
                        $arr[1]['is_checks'] = 1;
                        $arr[1]['zctime'] = $classesinfo['one_out'];
                    }else{
                        $arr[1]['is_check'] = 2;
                        $arr[1]['is_checks'] = 3;
                        $arr[1]['zctime'] = $classesinfo['one_out'];
                    }
                    
                }else{
                    if($one_out_type == 1){
                        if(time() < strtotime($day.date('H:i:s',$start2))+86400){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 2;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }else if(time() > strtotime($day.date('H:i:s',$end2))+86400){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 3;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }
                    }else{
                        if(time() < strtotime($day.date('H:i:s',$start2))){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 2;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }else if(time() > strtotime($day.date('H:i:s',$end2))){
                            $arr[1]['is_check'] = 2;
                            $arr[1]['is_checks'] = 3;
                            $arr[1]['zctime'] = $classesinfo['one_out'];
                        }
                    }
                }
            }
            
            //第二次上班
            $arr[2]['check_num'] = 3;
            $arr[2]['name'] = '上班';
            //查询有无打卡记录
            $two_in = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('classesid',$classesid)->where('check_num',3)->find();
            if($two_in){
                $arr[2]['is_check'] = 1;
                $arr[2]['zctime'] = $two_in['zcshijian'];
                $arr[2]['sjtime'] = $two_in['shijian'];
                $arr[2]['address'] = $two_in['title'];
                $arr[2]['status'] = $two_in['status'];
                if($check_num == 3){
                    $disable = 1;
                }
            }else{
                if($check_num == 3){
                    if(time() < strtotime($day.$classesinfo['two_in'])){
                        $arr[2]['is_check'] = 2;
                        $arr[2]['is_checks'] = 1;
                        $arr[2]['zctime'] = $classesinfo['two_in'];
                    }else{
                        $arr[2]['is_check'] = 2;
                        $arr[2]['is_checks'] = 3;
                        $arr[2]['zctime'] = $classesinfo['two_in'];
                    }
                }else{
                    
                    if($two_in_type == 1){
                        
                        if(time() < strtotime($day.date('H:i:s',$start3))+86400){
                            $arr[2]['is_check'] = 2;
                            $arr[2]['is_checks'] = 2;
                            $arr[2]['zctime'] = $classesinfo['two_in'];
                        }else if(time() > strtotime($day.date('H:i:s',$end3))+86400){
                            $arr[2]['is_check'] = 2;
                            $arr[2]['is_checks'] = 3;
                            $arr[2]['zctime'] = $classesinfo['two_in'];
                        }
                    }else{
                        if(time() < strtotime($day.date('H:i:s',$start3))){
                            $arr[2]['is_check'] = 2;
                            $arr[2]['is_checks'] = 2;
                            $arr[2]['zctime'] = $classesinfo['two_in'];
                        }else if(time() > strtotime($day.date('H:i:s',$end3))){
                            $arr[2]['is_check'] = 2;
                            $arr[2]['is_checks'] = 3;
                            $arr[2]['zctime'] = $classesinfo['two_in'];
                        }
                    }
                }
            }
            
            //第二次下班
            $arr[3]['check_num'] = 4;
            $arr[3]['name'] = '下班';
            //查询有无打卡记录
            $two_out = Db::name('check_log')->where('riqi',$day)->where('uid',$this->user_id)->where('classesid',$classesid)->where('check_num',4)->find();
            if($two_out){
                $arr[3]['is_check'] = 1;
                $arr[3]['zctime'] = $two_out['zcshijian'];
                $arr[3]['sjtime'] = $two_out['shijian'];
                $arr[3]['address'] = $two_out['title'];
                $arr[3]['status'] = $two_out['status'];
                if($check_num == 4){
                    $disable = 1;
                }
            }else{
                
                if($check_num == 4){
                    
                    if(time() < strtotime($day.$classesinfo['two_out'])){
                        $arr[3]['is_check'] = 2;
                        $arr[3]['is_checks'] = 1;
                        $arr[3]['zctime'] = $classesinfo['two_out'];
                    }else{
                        $arr[3]['is_check'] = 2;
                        $arr[3]['is_checks'] = 3;
                        $arr[3]['zctime'] = $classesinfo['two_out'];
                    }
                }else{
                    
                    if($two_out_type == 1){
                            
                        if(time() < strtotime($day.date('H:i:s',$start4))+86400){
                            $arr[3]['is_check'] = 2;
                            $arr[3]['is_checks'] = 2;
                            $arr[3]['zctime'] = $classesinfo['two_out'];
                        }else if(time() > strtotime($day.date('H:i:s',$end4))+86400){
                            $arr[3]['is_check'] = 2;
                            $arr[3]['is_checks'] = 3;
                            $arr[3]['zctime'] = $classesinfo['two_out'];
                        }
                    }else{
                        
                        if(time() < strtotime($day.date('H:i:s',$start4))){
                            $arr[3]['is_check'] = 2;
                            $arr[3]['is_checks'] = 2;
                            $arr[3]['zctime'] = $classesinfo['two_out'];
                        }else if(time() > strtotime($day.date('H:i:s',$end4))){
                            $arr[3]['is_check'] = 2;
                            $arr[3]['is_checks'] = 3;
                            $arr[3]['zctime'] = $classesinfo['two_out'];
                        }
                    }
                }
            }
        }
        
        $arrs = getDateOfMonth($date); // 获取本月
        
        $wherenum = [];
        $wherenum[] = ['day','in',$arrs];
        $wherenum[] = ['uid','=',$this->user_id];
        $num = Db::name('check_count')->where($wherenum)->sum('num');
        $znum = Db::name('check_count')->where($wherenum)->sum('znum');
        $cdnum = Db::name('check_count')->where($wherenum)->sum('cdnum');
        $ztnum = Db::name('check_count')->where($wherenum)->sum('ztnum');
        
        
        $cqnum = Db::name('check_count')->where($wherenum)->group('day')->count();
        
        $data['chidao'] = $cdnum;
        $data['zaotui'] = $ztnum;
        $data['queka'] = $num - $znum;
        $data['chuqin'] = $cqnum;
        
        //查询所选日期的打卡记录
        $check_nums = Db::name('check_count')->where('uid',$this->user_id)->where('day',$date)->where('classesid',$classesid)->count();
        
        if($check_nums > 0){
            $data['check_log'] = $arr;
        }else{
            $data['check_log'] = array(); 
        }
        
        
        echo apireturn(200,'success',$data);die;
          
    }
    
    //月度统计
    public function check_log_month(){
        $date = Request::param('date');
        $classesid = Request::param('classesid');
        if(empty($date)){
            $date = date('Y-m',time());
        }
        
        $arrs = getDateOfMonth($date); // 获取本月
        
        $wherenum = [];
        $wherenum[] = ['day','in',$arrs];
        $wherenum[] = ['uid','=',$this->user_id];
        $num = Db::name('check_count')->where($wherenum)->sum('num');
        $znum = Db::name('check_count')->where($wherenum)->sum('znum');
        $cdnum = Db::name('check_count')->where($wherenum)->sum('cdnum');
        $ztnum = Db::name('check_count')->where($wherenum)->sum('ztnum');
        
        
        $cqnum = Db::name('check_count')->where($wherenum)->group('day')->count();
        
        $data['chidao'] = $cdnum;
        $data['zaotui'] = $ztnum;
        $data['queka'] = $num - $znum;
        $data['chuqin'] = $cqnum;
        
        //查询出勤记录
        $queka_list = array();
        
        $chuqin_list = array();
        
        $day = '';
        
        foreach($arrs as $key => $val){
            $check_list = Db::name('check_count')->where('day',$val)->where('uid',$this->user_id)->select();
            if(count($check_list) > 0){
                array_push($chuqin_list,$val);
                //分班次查询
                foreach($check_list as $keys => $vals){
                    
                    $classesinfo = Db::name('classes')->where('id',$vals['classesid'])->find();
                    
                    $one_in = strtotime($classesinfo['one_in']);
                    $one_out = strtotime($classesinfo['one_out']);
                    $two_in = strtotime($classesinfo['two_in']);
                    $two_out = strtotime($classesinfo['two_out']);
                    
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
                    
                    if($vals['znum'] < $vals['num']){
                       
                        if($classesinfo['commuting_num'] == 1){
                            
                            if(Db::name('check_log')->where('riqi',$vals['day'])->where('uid',$this->user_id)->where('classesid',$vals['classesid'])->where('check_num',1)->count() == 0){
                                $weekarray=array("日","一","二","三","四","五","六");
                                $day = $vals['day'];
                                $week = "星期".$weekarray[date("w",strtotime($day))];
                                array_push($queka_list,$day.' ('.$week.') '.$classesinfo['one_in'].' '.$classesinfo['id'].' 1');
                            }
                            if(Db::name('check_log')->where('riqi',$vals['day'])->where('uid',$this->user_id)->where('classesid',$vals['classesid'])->where('check_num',2)->count() == 0){
                                $weekarray=array("日","一","二","三","四","五","六");
                                if($one_out_type == 1){
                                    $day = date('Y-m-d',strtotime($vals['day'].'+'."1day"));
                                }else{
                                    $day = $vals['day'];
                                }
                                $week = "星期".$weekarray[date("w",strtotime($day))];
                                array_push($queka_list,$day.' ('.$week.') '.$classesinfo['one_out'].' '.$classesinfo['id'].' 2');
                            }
                        }else{
                          
                            if(Db::name('check_log')->where('riqi',$vals['day'])->where('uid',$this->user_id)->where('classesid',$vals['classesid'])->where('check_num',1)->count() == 0){
                                $weekarray=array("日","一","二","三","四","五","六");
                                $day = $vals['day'];
                                $week = "星期".$weekarray[date("w",strtotime($day))];
                                array_push($queka_list,$day.' ('.$week.') '.$classesinfo['one_in'].' '.$classesinfo['id'].' 1');
                            }
                            if(Db::name('check_log')->where('riqi',$vals['day'])->where('uid',$this->user_id)->where('classesid',$vals['classesid'])->where('check_num',2)->count() == 0){
                                $weekarray=array("日","一","二","三","四","五","六");
                                if($one_out_type == 1){
                                    $day = date('Y-m-d',strtotime($vals['day'].'+'."1day"));
                                }else{
                                    $day = $vals['day'];
                                }
                                $week = "星期".$weekarray[date("w",strtotime($day))];
                                array_push($queka_list,$day.' ('.$week.') '.$classesinfo['one_out'].' '.$classesinfo['id'].' 2');
                            }
                            if(Db::name('check_log')->where('riqi',$vals['day'])->where('uid',$this->user_id)->where('classesid',$vals['classesid'])->where('check_num',3)->count() == 0){
                                $weekarray=array("日","一","二","三","四","五","六");
                                if($two_in_type == 1){
                                    $day = date('Y-m-d',strtotime($vals['day'].'+'."1day"));
                                }else{
                                    $day = $vals['day'];
                                }
                                $week = "星期".$weekarray[date("w",strtotime($day))];
                                array_push($queka_list,$day.' ('.$week.') '.$classesinfo['two_in'].' '.$classesinfo['id'].' 3');
                            }
                            if(Db::name('check_log')->where('riqi',$vals['day'])->where('uid',$this->user_id)->where('classesid',$vals['classesid'])->where('check_num',4)->count() == 0){
                                $weekarray=array("日","一","二","三","四","五","六");
                                if($two_out_type == 1){
                                    $day = date('Y-m-d',strtotime($vals['day'].'+'."1day"));
                                }else{
                                    $day = $vals['day'];
                                }
                                $week = "星期".$weekarray[date("w",strtotime($day))];
                                array_push($queka_list,$day.' ('.$week.') '.$classesinfo['two_out'].' '.$classesinfo['id'].' 4');
                            }
                        }
                        
                    }
                }
            }
        }
        
        $queka_list = subOrderSearch($queka_list,'text');
        foreach ($queka_list as $key => $val){
            $item = explode(' ',$val['text']);
            $queka_list[$key]['riqi'] = $item[0];
            $queka_list[$key]['week'] = $item[1];
            $queka_list[$key]['time'] = $item[2];
            $queka_list[$key]['id'] = $item[3];
            $queka_list[$key]['check_num'] = $item[4];
        }
        
        $data['queka_list'] = $queka_list;
        
        $chuqin_lists = subOrderSearch($chuqin_list,'date');
        foreach($chuqin_lists as $key => $val){
            $weekarray=array("日","一","二","三","四","五","六");
            $chuqin_lists[$key]['week'] = "星期".$weekarray[date("w",strtotime($val['date']))];
            $chuqin_lists[$key]['count'] = '1天';
        }
        $data['chuqin_list'] = $chuqin_lists;
        
        //查询迟到记录
        $wherecd = [];
        $wherecd[] = ['riqi','in',$arrs];
        $wherecd[] = ['uid','=',$this->user_id];
        $wherecd[] = ['status','=',2];
        $chidao = Db::name('check_log')->field('riqi,shijian,zcshijian')->where($wherecd)->select();
        foreach ($chidao as $key => $val){
            $weekarray=array("日","一","二","三","四","五","六");
            $chidao[$key]['week'] = "星期".$weekarray[date("w",strtotime($val['riqi']))];
        }
        $data['chidao_list'] = $chidao;
        
        //查询早退记录
        $wherezt = [];
        $wherezt[] = ['riqi','in',$arrs];
        $wherezt[] = ['uid','=',$this->user_id];
        $wherezt[] = ['status','=',3];
        $zaotui = Db::name('check_log')->field('riqi,shijian,zcshijian')->where($wherezt)->select();
        foreach ($zaotui as $key => $val){
            $weekarray=array("日","一","二","三","四","五","六");
            $zaotui[$key]['week'] = "星期".$weekarray[date("w",strtotime($val['riqi']))];
        }
        $data['zaotui_list'] = $zaotui;
        
        
        
      
        echo apireturn(200,'success',$data);die;
    }
    
    
    

}