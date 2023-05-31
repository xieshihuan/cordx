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
use PHPZxing\PHPZxingDecoder;

use Picqer\Barcode\BarcodeGeneratorDataMatrix;

class Index
{
    
    public function role(){
        $list = Db::name('users')->where('id','>',1)->select();
        
        $role_id = '';
        foreach ($list as $key => $val){
            $juese = Db::name('ceshi')->where('username',$val['username'])->find();
            if($juese['juese'] == '职员'){
                $role_id = 1;
            }else if($juese['juese'] == '职工'){
                $role_id = 2;
            }else if($juese['juese'] == '工人'){
                $role_id = 3;
            }else{
                $role_id = '';
            }
            $list[$key]['role_id'] = $role_id;
            $data['role_id'] = $role_id;
            Db::name('users')->where('id',$val['id'])->update($data);
        }
        
        print_r($list);
        die;
    }
    public function fields(){
        $modle = model('Ruzhi');
        $fields = $modle->getTableFields();
        print_r($fields);
        die;
    }
    public function cate(){
        $list = Db::name('cate')->order('id asc')->select();
        
        $csvOutput = '$csvOutput = $csvOutput + $tm1.DisplayName + "," + "," + $tm1.GroupID + "`n"'.'"<br/>';
        
        foreach ($list as $key => $val){
            $csvOutput.= '$csvOutput = $csvOutput + $tm'.$val['id'].'.DisplayName + "," + $tm'.$val['parentid'].'.DisplayName + "," + $tm'.$val['id'].'.GroupID + "`n"'.'"<br/>';
        }
        
        echo $csvOutput;
    }
    //one
    public function cate_bf(){
        $list = Db::name('cate')->order('id asc')->select();
        
        $a = '';
        foreach ($list as $key => $val){
            $a.= '$tm'.$val['id']. ' = New-Team -DisplayName '.'"'.$val['title'].'"<br/>';
        }
        
        echo $a;
    }
    
    
    public function assess_name(){
        
        $text = '差,可,中,良,优';
        $tlist = explode(',',$text);
        echo apireturn(200,'success',$tlist);die;
        
    }  
    public function ceshiaaa(){
        $dataupd['is_tijiao'] = 1;
         Db::name('assess')->where('uid',191)->where('qid',1)->update($dataupd);
    }  
    
    public function ais(){
        $phone = Request::param('phone');
        
        $uid = Db::name('users')->where('mobile',$phone)->value('id');
        
        $time = strtotime(date('Y-m-d',time()));
        $whr = [];
        $whr[] = ['create_time','>',$time];
        $testid = Db::name('test')->where('uid',$uid)->where($whr)->order('id desc')->value('id');
        $tikuid = Db::name('test')->where('uid',$uid)->where($whr)->order('id desc')->value('tid');
        
        if($testid){
                
            
            $tlist = Db::name('tiku')->where('tiku_id',$tikuid)->order('id asc')->select();
            
            $msg = '';
            
            $scores = 0;
            foreach ($tlist  as $key => $val){
                $whra['tid'] = $val['id'];
                $whra['testid'] = $testid;
                $hang = json_decode(Db::name('recordcache')->where($whra)->value('json'),true);
                
                if($hang){
                    
                    
                    
                    $reslut = '';
                    foreach ($hang as $keys => $vals){
                        if($val['type_id'] < 4){
                            if($vals['is_checked'] == 1){
                                $reslut .= yingshe($keys)."\n";
                            }
                        }else{
                            if($val['type_id'] == 4){
                                if($vals['key'] == '_'){
                                    $reslut .= $vals['value']."\n";
                                }
                            }else{
                                $reslut .= $vals['key'].$vals['value']."\n";
                            }
                        }
                    }
                    $reslut = rtrim($reslut,"\n");
        
                    $tinfo = Db::name('tiku')->where('id',$val['id'])->find();
                    
                    $aa = zhuan($reslut);
        
                    if($tinfo['type_id'] == 2){
                        $arr = explode("\n",$tinfo['result']);
                        array_multisort($arr,SORT_ASC);
                        $bb = implode("\n",$arr);
                    }else{
                        $bb = zhuan($tinfo['result']);
                    }
        
                    $cuo = 0;
                    if($tinfo['type_id'] == 4){
        
                        $arra = explode("\n",$aa);
                        $arrb = explode("\n",$bb);
                        foreach ($arra as $keys => $vals){
                            $zhengque = explode('^',$arrb[$keys]);
                            $zhengque = array_map("trim",$zhengque);
                            if(!in_array($vals,$zhengque)){
                                $cuo = $cuo + 1;
                            }
                        }
                        if($cuo > 0){
                            $data['score'] = 0;
                            
                            $tihao = $key + 1;
                            $msg .= $tihao.'、'.$val['question'].' - 答案有误'.'------';
                        }else{
                            $data['score'] = $tinfo['score'];
                            $repeat = 100;
                            
                            $scores = $scores + $tinfo['score'];
                        }
                    }else{
                        if($aa == $bb){
                            $data['score'] = $tinfo['score'];
                            $scores = $scores + $tinfo['score'];
                        }else{
                            $data['score'] = 0;
                            
                            $tihao = $key + 1;
                            $msg .= $tihao.'、'.$val['question'].' - 答案有误'.'------';
                            
                        }
                    }
                    
                            
                }else{
                    echo apireturn(200,'请先保存','');
                    die;
                }
            }
            
            if(!empty($msg)){
                echo apireturn(200,$msg,'');
                die;
            }
            
                    
            echo apireturn(200,'success - '.$scores,'');
            die;
        }else{
            echo apireturn(200,'请先开始考试','');
            die;
        }
        
    }
    
    public function ai(){
        $phone = Request::param('phone');
        
        $uid = Db::name('users')->where('mobile',$phone)->value('id');
        
        $time = strtotime(date('Y-m-d',time()));
        $whr = [];
        $whr[] = ['create_time','>',$time];
        $testid = Db::name('test')->where('uid',$uid)->where($whr)->order('id desc')->value('id');
        $tikuid = Db::name('test')->where('uid',$uid)->where($whr)->order('id desc')->value('tid');
        
        if($testid){
                
            
            $tlist = Db::name('tiku')->where('tiku_id',$tikuid)->order('id asc')->select();
            
            $scores = 0;
            foreach ($tlist  as $key => $val){
                $whra['tid'] = $val['id'];
                $whra['testid'] = $testid;
                $hang = json_decode(Db::name('recordcache')->where($whra)->value('json'),true);
                
                if($hang){
                    
                    $reslut = '';
                    foreach ($hang as $keys => $vals){
                        if($val['type_id'] < 4){
                            if($vals['is_checked'] == 1){
                                $reslut .= yingshe($keys)."\n";
                            }
                        }else{
                            if($val['type_id'] == 4){
                                if($vals['key'] == '_'){
                                    $reslut .= $vals['value']."\n";
                                }
                            }else{
                                $reslut .= $vals['key'].$vals['value']."\n";
                            }
                        }
                    }
                    $reslut = rtrim($reslut,"\n");
        
                    $tinfo = Db::name('tiku')->where('id',$val['id'])->find();
                    
                    $aa = zhuan($reslut);
        
                    if($tinfo['type_id'] == 2){
                        $arr = explode("\n",$tinfo['result']);
                        array_multisort($arr,SORT_ASC);
                        $bb = implode("\n",$arr);
                    }else{
                        $bb = zhuan($tinfo['result']);
                    }
        
                    $cuo = 0;
                    if($tinfo['type_id'] == 4){
        
                        $arra = explode("\n",$aa);
                        $arrb = explode("\n",$bb);
                        foreach ($arra as $keys => $vals){
                            $zhengque = explode('^',$arrb[$keys]);
                            $zhengque = array_map("trim",$zhengque);
                            if(!in_array($vals,$zhengque)){
                                $cuo = $cuo + 1;
                            }
                        }
                        if($cuo > 0){
                            $data['score'] = 0;
                            
                            $tihao = $key + 1;
                            $msg = $tihao.'、'.$val['question'].' - 答案有误';
                            echo apireturn(200,$msg,'');
                            die;
                        }else{
                            $data['score'] = $tinfo['score'];
                            $repeat = 100;
                            
                            $scores = $scores + $tinfo['score'];
                        }
                    }else{
                        if($aa == $bb){
                            $data['score'] = $tinfo['score'];
                            $scores = $scores + $tinfo['score'];
                        }else{
                            $data['score'] = 0;
                            
                            $tihao = $key + 1;
                            $msg = $tihao.'、'.$val['question'].' - 答案有误';
                            echo apireturn(200,$msg,'');
                            die;
                        }
                    }
                }else{
                    echo apireturn(200,'请先保存','');
                    die;
                }
            }
            echo apireturn(200,'success - '.$scores,'');
            die;
        }else{
            echo apireturn(200,'请先开始考试','');
            die;
        }
        
    }
    //删除会员
    public function shanchu_bf(){

        $list = DB::name('delete')->select();
        foreach ($list as $key => $val){
            $uid = Db::name('users')->where('mobile',$val['mobile'])->value('id');
            Db::name('cateuser')->where('uid',$uid)->delete();
            Db::name('users')->where('id',$uid)->delete();
        }
    }

    //导入到会员表
    public function ceshi_add_bf(){
        $tlist = Db::name('ceshiuser')->select();
        foreach ($tlist as $key =>$val){
            
            $data['mobile'] = $val['phone'];
            $data['country'] = $val['guoji'];
            $data['username'] = $val['name'];
            
            $zhan = explode(',',$val['zhandian']);
            
            $z = '';
            foreach($zhan as $vals){
                $datas = explode('/',$vals);
                
                //echo str_replace('｜',' | ',$datas[count($datas)-1]);
                $whrz['title'] = str_replace('｜',' | ',$datas[count($datas)-1]);
                
                $zid = Db::name('cate')->where($whrz)->value('id');
                $z .= $zid.',';
                
            }
            
            
            $data['rules'] = rtrim($z,',');;
            
            Db::name('users')->insert($data);
            //print_r($data);
        }
        //print_r($tlist);
        //die;
    }
    
    //会员批量加入组织
    public function user_zuzhi_bf(){
        
        $tlist = Db::name('users')->order('id asc')->select();
        
        $zhan = array();
        
        foreach ($tlist as $key =>$val){
            
            $zhan = explode(',',$val['rules']);
            
            foreach($zhan as $key => $vals){
                
                
                $data['uid'] = $val['id'];
                $data['catid'] = $vals;
                $data['level'] = Db::name('cate')->where('id',$vals)->value('level');
                $cuid = Db::name('cateuser')->insertGetId($data);
                if($cuid){
                    
                    //更新会员上级绑定关系
                    $this->updsj($cuid,$cuid);
                    
                }  
            }
        }
    }
    
    //更新会员上级绑定关系
    public function updsj($id,$oneid){
    
        $whr['id'] = $id;
        $cinfo = Db::name('cateuser')->where($whr)->find();
        if($cinfo['level'] > 1){
            //获取上级id
            $whr1['id'] = $cinfo['catid'];
            $sid = Db::name('cate')->where($whr1)->value('parentid');
            
            //获取上级级别
            $whr2['id'] = $sid;
            $level = Db::name('cate')->where($whr2)->value('level');
            
            $data['uid'] = $cinfo['uid'];
            $data['catid'] = $sid;
            $data['level'] = $level;
            $data['leixing'] = 2;
            $data['oneid'] = $oneid;
            $data['create_time'] = time();
            $data['update_time'] = time();
            
            $cuid = Db::name('cateuser')->insertGetId($data);
            
            $this->updsj($cuid,$oneid);
            
        }
        
    }

    //批量删除
    public function clear_20220701_bf(){

        Db::name('test')->where('1=1')->delete();
        Db::name('tests')->where('1=1')->delete();
        Db::name('record')->where('1=1')->delete();
        Db::name('records')->where('1=1')->delete();
        Db::name('zdrecord')->where('1=1')->delete();
        Db::name('recordcache')->where('1=1')->delete();

        echo '清除成功';
        die;
    }

    //权限列表
    public function index(){
        
        
            $parentid = 0;
            
            $where=[];
            if($parentid){
                $where[]=['parentid', '=', $parentid];
            }else{
                $where[] = ['parentid','=','0'];
            }
            
            $list = Db::name('cate')->where($where)->order('sort asc')->select();
            
            foreach ($list as $key => $val){
                
                $list[$key]['value'] = $val['id'];
                $list[$key]['label'] = $val['title'];
                
                $num = Db::name('cate')->where(['parentid'=>$val['id']])->count();
                if($num > 0){
                    $list[$key]['children'] = self::get_trees($val['id']);
                }else{
                    $list[$key]['children'] = '';
                }
                
                $whra['catid'] = $val['id'];
                $whra['leixing'] = 1;
                $ulist = Db::name('cateuser')->field('uid')->where($whra)->select();
                $a = '';
                foreach ($ulist as $keys => $vals){
                    $a .= Db::name('users')->where('id',$vals['uid'])->value('username').'-';
                }
                
                $list[$key]['ulist'] = $a;
                
            }
            
            $data_rt['status'] = 200;
            $data_rt['msg'] = '获取成功';
            $data_rt['data'] = $list;
            
            //print_r($list);
            return json_encode($data_rt);
            exit;
     
    }
    
    public function indexs(){
        
        if(Request::isPost()){
            
            $data = Request::post();
            
            $parentid = $data['parentid'];
            
            $where=[];
            if($parentid){
                $where[]=['parentid', '=', $parentid];
            }else{
                $where[] = ['parentid','=','0'];
            }
            
            $list = Db::name('cate')->where($where)->order('sort asc')->select();
            
            foreach ($list as $key => $val){
                
                $list[$key]['value'] = $val['id'];
                $list[$key]['label'] = $val['title'];
                
                $num = Db::name('cate')->where(['parentid'=>$val['id']])->count();
                if($num > 0){
                    $list[$key]['children'] = self::get_trees($val['id']);
                }else{
                    $list[$key]['children'] = '';
                }
                
            }
            
            $data_rt['status'] = 200;
            $data_rt['msg'] = '获取成功';
            $data_rt['data'] = $list;
            return json_encode($data_rt);
            exit;
        }
    }
    
    public function get_trees($pid = 0){
      
        $list = Db::name('cate')->where(['parentid'=>$pid])->order('sort asc')->select();
        
        foreach ($list as $key => $val){

            $list[$key]['value'] = $val['id'];
            $list[$key]['label'] = $val['title'];
            
            $num = Db::name('cate')->where(['parentid'=>$val['id']])->count();
            
            if($num > 0){
                $list[$key]['children'] = self::get_trees($val['id']);
            }else{
                $list[$key]['children'] = '';
            }
            
            $whra['catid'] = $val['id'];
            $whra['leixing'] = 1;
            $ulist = Db::name('cateuser')->field('uid')->where($whra)->select();
            $a = '';
            foreach ($ulist as $keys => $vals){
                $a .= Db::name('users')->where('id',$vals['uid'])->value('username').'-';
            }
            
            $list[$key]['ulist'] = $a;
        }
        
        return $list;
    }

    //添加缺考记录
    public function aiins(){
        $where[] = ['end','<',time()];
        $info = Db::name('daxuetang')->where($where)->order('id desc')->find();
        
        if($info){
            $whra['qid'] = $info['id'];
            $whra['status'] = 2;
            $uuid= Db::name('test')->field('uid')->where($whra)->buildSql(true);
            
            $tlist = Db::name('users')->field('id')->where('is_delete',1)->where('is_kaohes',1)->where('id','exp','not In '.$uuid)->select();

            foreach ($tlist as $key => $val){
                $whrb['qid'] = $info['id'];
                $whrb['uid'] = $val['id'];
                $num = Db::name('test')->where($whrb)->count();
                if($num == 0){
                    $data['qid'] = $info['id'];
                    $data['uid'] = $val['id'];
                    $data['tid'] = $info['tiku_id'];
                    $data['score'] = 0;
                    $data['mscore'] = Db::name('tiku')->where('tiku_id',$info['tiku_id'])->sum('score');
                    $data['is_tijiao'] = 0;
                    $data['status'] = 3;
                    $data['create_time'] = time();
                    $data['update_time'] = time();
                    Db::name('test')->insert($data);
                }else{
                    $status = Db::name('test')->where($whrb)->value('status');
                    if($status == 1){
                        $datas['status'] = 3;
                        Db::name('test')->where($whrb)->update($datas);
                    }
                }

                $alist = Db::name('cateuser')->field('catid')->where('uid',$val['id'])->where('leixing',1)->select();
                foreach ($alist as $keys => $vals){
                    updzhandian($info['id'],$val['id'],$vals['catid'],0);
                }
            }

            echo date('Y-m-d H:i:s',time()).'执行成功';
        }else{
            echo '昨日不是大学堂';
        }

    }
    
    public function testlist(){
        
        $score = Db::name('tiku')->sum('score');
        
        $mfnum = Db::name('test')->where('uid',$this->user_id)->where('score',$score)->where('is_tijiao',1)->count();
        $znum = Db::name('test')->where('uid',$this->user_id)->where('is_tijiao',1)->count();
        if($znum > 0){
            $wmfnum = $znum - $mfnum;
        }else{
            $wmfnum = 0;
        }
        $list['mfnum'] = $mfnum;
        $list['znum'] = $znum;
        $list['wmfnum'] = $wmfnum;
        
        $data_rt['status'] = 200;
        $data_rt['msg'] = '获取成功';
        $data_rt['data'] = $list;
        
        return json_encode($data_rt);
        exit;
        
    }
    
    public function country(){
        
        $list = Db::name('country')->select();
        
        $data_rt['status'] = 200;
        $data_rt['msg'] = '获取成功';
        $data_rt['data'] = $list;
        
        return json_encode($data_rt);
        exit;
        
    }
    
    //发送考勤异常提醒
    public function check_msg(){
        $day = date('Y-m-d',time()-86400);
       
        $list = Db::name('check_count')->field('uid')->where('day',$day)->where('num<>zcnum')->select();
        
        $openid = '';
        foreach ($list as $key => $val){
            //查询openid
            $openid = Db::name('weixin')->where('uid',$val['uid'])->value('openid');
            //发送考勤异常提醒
            if($openid){
                $uname = Db::name('users')->where('id',$val['uid'])->value('username');
                $dataq['uname'] = $uname;
                $dataq['leixing'] = '考勤异常提醒';
                $dataq['shijian'] = $day;
                $dataq['openid'] = $openid;
                if(Db::name('wxnotice')->where($dataq)->count() == 0){
                    Db::name('wxnotice')->insert($dataq);
                    //$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                    //$url=$http_type.$_SERVER['HTTP_HOST']."/api/wxnofiy/yestday_notice";
                    //调用发送消息接口
                    $wxnofiy = new Wxnofiy;
                    $wxnofiy->yestday_notice($uname,$day,$openid);
                   
                }
            }
        }
        
        echo $day.'执行成功';
    }
    
    //发送月度考勤确认提醒
    public function check_month_msg(){
        
        $start = date('Y-m-01', strtotime('-1 month'));
        $end = date('Y-m-t', strtotime('-1 month'));
        $whr = [];
        $whr[] = ['day','between time',[$start,$end]];
   
        $list = Db::name('check_count')->field('uid')->where($whr)->group('uid')->select();
        
        $openid = '';
        foreach ($list as $key => $val){
            //查询openid
            $openid = Db::name('weixin')->where('uid',$val['uid'])->value('openid');
            //发送考勤异常提醒
            if($openid){
                $uname = Db::name('users')->where('id',$val['uid'])->value('username');
                
                $userid = $val['uid'];
                $arrs = getDateOfMonth($start);
                $wherenum = [];
                $wherenum[] = ['day','in',$arrs];
                $wherenum[] = ['uid','=',$userid];
                $num = Db::name('check_count')->where($wherenum)->sum('num');
                $znum = Db::name('check_count')->where($wherenum)->sum('znum');
                $zcnum = Db::name('check_count')->where($wherenum)->sum('zcnum');
                $cdnum = Db::name('check_count')->where($wherenum)->sum('cdnum');
                $ztnum = Db::name('check_count')->where($wherenum)->sum('ztnum');
                //$cqnum = Db::name('check_count')->where($wherenum)->group('day')->count();
                $qknum = $num - $zcnum - $cdnum - $ztnum;
                
                //查询打卡结果
                $checklist = array();
                
                $i = strtotime($start);
                $o = strtotime($end);
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
                
                
                //查询补卡记录
                $wherebk = [];
                $wherebk[] = ['riqi','in',$arrs];
                $wherebk[] = ['fl.uid','=',$userid];
                $wherebk[] = ['fl.flow_type','=','buka'];
                $wherebk[] = ['fl.status','=',2];
                $bknum = Db::name('buka')
                            ->alias('bk')
                            ->leftJoin('flow_list fl','fl.flow_id = bk.id')
                            ->field('bk.*,fl.*')
                            ->where($wherebk)
                            ->count();
                
                //查询加班记录
                $wherejb = [];
                //$wherejb[] = ['addtime','in',$arrs];
                $wherejb[] = ['jb.uid','=',$userid];
                $wherejb[] = ['fl.flow_type','=','jiaban'];
                $wherejb[] = ['fl.status','=',2];
                
                $jiaban_list = Db::name('jiaban')
                            ->alias('jb')
                            ->leftJoin('flow_list fl','fl.flow_id = jb.id')
                            ->field('jb.*,jb.id as jbid,fl.*,FROM_UNIXTIME(UNIX_TIMESTAMP(start),"%Y-%m-%d") as riqi')
                            ->where($wherejb)
                            ->select();
                $ids = '';
                foreach($jiaban_list as $keys => $vals){
                    if(in_array($vals['riqi'],$arrs)){
                        $ids .= $vals['jbid'].',';
                    }
                }
                $ids = rtrim($ids,',');
                //$idss = explode(',',$ids);
                $whrjbs = [];
                $whrjbs[] = ['jb.id','in',$ids];
                $whrjbs[] = ['fl.flow_type','=','jiaban'];
                $whrjbs[] = ['fl.status','=',2];
                $jbnum = Db::name('jiaban')
                            ->alias('jb')
                            ->leftJoin('flow_list fl','fl.flow_id = jb.id')
                            ->field('jb.*,fl.*')
                            ->where($whrjbs)
                            ->sum('shichang');
                // $jiaban_lists = Db::name('jiaban')
                //             ->alias('jb')
                //             ->leftJoin('flow_list fl','fl.flow_id = jb.id')
                //             ->field('jb.*,fl.*,FROM_UNIXTIME(fl.create_time,"%Y-%m-%d %H:%i:%s") as addtime')
                //             ->where($whrjbs)
                //             ->select();
                
                //$data['jiaban_list'] = $jiaban_lists;
                
                //查询请假次数
                $whereqj = [];
                //$wherejb[] = ['addtime','in',$arrs];
                $whereqj[] = ['qj.uid','=',$userid];
                $whereqj[] = ['fl.flow_type','=','qingjia'];
                $whereqj[] = ['fl.status','=',2];
                
                $jiaban_list = Db::name('qingjia')
                            ->alias('qj')
                            ->leftJoin('flow_list fl','fl.flow_id = qj.id')
                            ->field('qj.*,qj.id as qjid,fl.*,FROM_UNIXTIME(UNIX_TIMESTAMP(start),"%Y-%m-%d") as riqi')
                            ->where($whereqj)
                            ->select();
               
                $ids = '';
                foreach($jiaban_list as $keys => $vals){
                    if(in_array($vals['riqi'],$arrs)){
                        $ids .= $vals['qjid'].',';
                    }
                }
                $ids = rtrim($ids,',');
                $whrqjs = [];
                $whrqjs[] = ['qj.id','in',$ids];
                $whrqjs[] = ['fl.flow_type','=','qingjia'];
                $whrqjs[] = ['fl.status','=',2];
                $qjnum = Db::name('qingjia')
                            ->alias('qj')
                            ->leftJoin('flow_list fl','fl.flow_id = qj.id')
                            ->field('qj.*,fl.*')
                            ->where($whrqjs)
                            ->count();
               
                // $qingjia_lists = Db::name('qingjia')
                //             ->alias('qj')
                //             ->leftJoin('flow_list fl','fl.flow_id = qj.id')
                //             ->field('qj.*,fl.*,FROM_UNIXTIME(fl.create_time,"%Y-%m-%d %H:%i:%s") as addtime')
                //             ->where($whrqjs)
                //             ->select();
              
                //$data['qingjia_list'] = $qingjia_lists;
                
                $title = date('Y年m月',strtotime('-1 month')).'考勤如下';
                $neirong = "实际出勤".$shiji."天，迟到".$cdnum."次，早退".$ztnum."次，缺卡".$qknum."次，补卡".$bknum."次，请假".$qjnum."次，加班".$jbnum."小时";
                
                $shijian = date('Y-m',strtotime('-1 month'));
                
                $dataq['uname'] = $uname;
                $dataq['title'] = $title;
                $dataq['leixing'] = '月度考核结果通知';
                $dataq['neirong'] = $neirong;
                $dataq['shijian'] = $shijian;
                $dataq['openid'] = $openid;
                if(Db::name('wxnotice')->where($dataq)->count() == 0){
                    Db::name('wxnotice')->insert($dataq);
                    //$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                    //$url=$http_type.$_SERVER['HTTP_HOST']."/api/wxnofiy/yestday_notice";
                    //调用发送消息接口
                    $wxnofiy = new Wxnofiy;
                    $wxnofiy->month_notice($uname,$title,$neirong,$shijian,$openid);
                   
                }
            }
        }
        
        echo $start.'-'.$end.'执行成功';
    }
    
}
