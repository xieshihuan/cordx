<?php
/**
 * +----------------------------------------------------------------------
 * | 登录制器
 * +----------------------------------------------------------------------
 */
namespace app\api\controller;
use think\Controller;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

use app\common\model\Test as T;
use app\common\model\Assess as M;

class Test extends Base
{
    //同意
    public function agree(){
        
        $info = Db::name('users')->where('id',$this->user_id)->setInc('is_agree',1);
        if($info){
            echo apireturn(200,'success','');die;
        }else{
            echo apireturn(201,'faile','');die;
        }
        
    }
    //获取考试题库
    public function get_tiku(){
        
        //查询是否在考试时间
        //  $sc = Db::name('country')->where('code',$this->country)->value('sc');
        //  if($sc > 0){
        //      $sctime = $sc*3600;
        //      $time = time()-$sctime;
        //  }else{
        //      $time = time();
        //  }
         
        $whr[] = ['start','lt',time()];
        $whr[] = ['end','gt',time()];
        $list = Db::name('daxuetang')->where($whr)->select();
        foreach ($list as $key => $val){
            if($val['is_restrict'] == 2){
                $member = explode(',',$val['member']);
                $organize = explode(',',$val['organize']);

                $group = Db::name('cateuser')->field('catid')->where('uid',$this->user_id)->where('leixing',1)->select();

                $groups = array_column($group, 'catid');

                $chongfu = array_intersect($groups,$organize);

                $groupnum = count($chongfu);

                if(!in_array($this->user_id,$member) && $groupnum == 0){
                    $list[$key]['is_kaohe'] = 2;
                }else{
                    $list[$key]['is_kaohe'] = 1;
                }
            }else{
                $list[$key]['is_kaohe'] = 1;
            }

            $list[$key]['tnum'] = Db::name('tiku')->where('tiku_id',$val['tiku_id'])->count();
        }
        if($list){
            $list = array_filter($list,function($element){

                return $element['is_kaohe'] == 1;  //只保留$arr数组中的is_kaohe元素为1的数组元素

            });
            
            $list = array_values($list);
            echo apireturn(200,'success',$list);die;
        }else{
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = 'success';
            $rs_arr['data'] = array();
            return json_encode($rs_arr,true);
            exit;
        }

    }
    //获取须知
    public function get_note(){
        $id = $this->request->param('id');
        $type = $this->request->param('type');
        if($type == 1){
            $info = Db::name('daxuetang')->where('id',$id)->find();
            if($info){
                $tinfo = Db::name('tikus')->where('id',$info['tiku_id'])->find();
                if($tinfo){
                    $tinfo['testnotes'] = explode("\n", $tinfo['testnotes']);
                    $tinfo['lxnotes'] = explode("\n", $tinfo['lxnotes']);
                    echo apireturn(200,'success',$tinfo);die;
                }else{
                    echo apireturn(201,'信息有误','');die;
                }
            }else{
                echo apireturn(201,'id不存在','');die;
            }
        }else{

            $tinfo = Db::name('tikus')->where('id',$id)->find();
            if($tinfo){
                $tinfo['testnotes'] = explode("\n", $tinfo['testnotes']);
                $tinfo['lxnotes'] = explode("\n", $tinfo['lxnotes']);
                echo apireturn(200,'success',$tinfo);die;
            }else{
                echo apireturn(201,'信息有误','');die;
            }

        }
    }
    //获取练兵场题库
    public function get_practice(){
        $list = Db::name('tikus')->where('is_practice',0)->where('is_delete',0)->select();
        foreach ($list as $key => $val){
            $list[$key]['tnum'] = Db::name('tiku')->where('tiku_id',$val['id'])->count();
        }
        
        if(count($list) > 0){
            echo apireturn(200,'success',$list);die;
        }else{
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = 'success';
            $rs_arr['data'] = array();
            return json_encode($rs_arr,true);
            exit;
        }
    }
    //开始考试
    public function start(){
        $id = $this->request->param('id');

         if(empty($id)){
             echo apireturn(201,'请选择考试','');
             die;
         }else{
             $whr = [];
             //查询是否在考试时间
            //  $sc = Db::name('country')->where('code',$this->country)->value('sc');
            //  if($sc > 0){
            //      $sctime = $sc*3600;
            //      $time = time()-$sctime;
            //  }else{
            //      $time = time();
            //  }
             
             $whr[] = ['start','lt',time()];
             $whr[] = ['end','gt',time()];
             $whr[] = ['id','=', $id];
             $info = Db::name('daxuetang')->where($whr)->find();
             
             if($info){
                 //查询是否需要考试
                 if($info['is_restrict'] == 2){
                    $member = explode(',',$info['member']);
                    $organize = explode(',',$info['organize']);

                    $group = Db::name('cateuser')->field('catid')->where('uid',$this->user_id)->where('leixing',1)->select();

                    $groups = array_column($group, 'catid');

                    $chongfu = array_intersect($groups,$organize);

                    $groupnum = count($chongfu);

                    if(!in_array($this->user_id,$member) && $groupnum == 0){
                        echo apireturn(201,'您不是本次考核对象','');die;
                    }
                 }

                //查询题库
                 $whrqs['id'] = $info['tiku_id'];
                 $dd = Db::name('tikus')->where($whrqs)->find();
                 
                 if($dd){

                    //判断有没有生成考核记录
                    $whrs['qid'] = $info['id'];
                    $whrs['tid'] = $info['tiku_id'];
                    $whrs['uid'] = $this->user_id;
                    $tinfo =  Db::name('test')->where($whrs)->find();
                    
            //   print_r($whrs);      
            //  print_r($tinfo);
            //  die;
             
                    if($tinfo){
                        
                        if($tinfo['is_tijiao'] == 1){
                            echo apireturn(201,'本周考试已交卷,无法答题','');die;
                        }
                        $tlist =  Db::name('tiku')->where('tiku_id',$info['tiku_id'])->order('sort asc')->select();

                        foreach($tlist as $key => $val){
                            $re = Db::name('recordcache')->where('tid',$val['id'])->where('testid',$tinfo['id'])->value('result');
                            if($re){
                                $tlist[$key]['results'] = $re;
                                $tlist[$key]['is_answer'] = Db::name('recordcache')->where('tid',$val['id'])->where('testid',$tinfo['id'])->value('is_answer');
                                $json = Db::name('recordcache')->where('tid',$val['id'])->where('testid',$tinfo['id'])->value('json');
                                
                                $tlist[$key]['hang'] = json_decode($json,true);
                            }else{
                                $tlist[$key]['results'] = '';
                                $tlist[$key]['is_answer'] = 0;

                                if($val['type_id'] != 4){
                                    $hlist = explode("\n", $val['z_result']);
                                }else{
                                    $hlist = fg($val['question']);
                                    $llist = explode("\n", $val['result']);
                                }

                                $arr = [];
                                $i = 0;
                                foreach ($hlist as $k=>$v) {
                                    $arr[$k]['key'] = $v;
                                    $arr[$k]['value'] = '';
                                    $arr[$k]['is_checked'] = 0;
                                    if($v == '_'){
                                        $lllist = explode("^", $llist[$i]);
                                        $arr[$k]['length'] = strlen(preg_replace("#[^\x{00}-\x{ff}]#u", '*', $lllist[0])) + 1;
                                        $i = $i + 1;
                                    }
                                }
                                
                                $tlist[$key]['hang']  = $arr;
                                
                            }
                            $alist = fg($val['question']);
                                
                            $arrs = [];
                            foreach ($alist as $k=>$v) {
                                $arrs[$k]['key'] = $v;
                            }
                        
                            $tlist[$key]['title']  = $arrs;
                        }
                        
                        $tinfo['is_assess'] = $info['is_assess'];
                        
                        $data_rt['tinfo'] = $tinfo;
                        $data_rt['tlist'] = $tlist;
                        echo apireturn(200,/*$dd['title']*/'daxuetang',$data_rt);die;
                        
                    }else{
                        
                        $zscore = Db::name('tiku')->where('tiku_id',$info['tiku_id'])->sum('score');
                        
                        $data['qid'] = $info['id'];
                        $data['uid'] = $this->user_id;
                        $data['score'] = 0;
                        $data['mscore'] = $zscore;
                        $data['status'] = 1;
                        $data['tid'] = $info['tiku_id'];
                        $data['total_time'] = $info['exam_time']*60;
                        $data['surplus_time'] = $info['exam_time']*60;
                        $data['is_agree'] = 0;
                        $data['create_time'] = time();
                        $data['update_time'] = time();
                        $id = Db::name('test')->insertGetId($data);
                        $data['id'] = $id;
                        
                        $tlist =  Db::name('tiku')->where('tiku_id',$info['tiku_id'])->order('sort asc')->select();
                        foreach($tlist as $key => $val){
                            $tlist[$key]['score'] = floatval($val['score']);

                            if($val['type_id'] != 4){
                                $hlist = explode("\n", $val['z_result']);
                            }else{
                                $hlist = fg($val['question']);
                                $llist = explode("\n", $val['result']);
                            }

                            $arr = [];
                            $i = 0;
                            foreach ($hlist as $k=>$v) {
                                $arr[$k]['key'] = $v;
                                $arr[$k]['value'] = '';
                                $arr[$k]['is_checked'] = 0;
                                if($v == '_'){
                                    $lllist = explode("^", $llist[$i]);
                                    $arr[$k]['length'] = strlen(preg_replace("#[^\x{00}-\x{ff}]#u", '*', $lllist[0])) + 1;
                                    $i = $i + 1;
                                }
                            }
                                
                            
                            $tlist[$key]['hang']  = $arr;
                            $tlist[$key]['hangnum']  = count($arr);
                            $tlist[$key]['is_answer'] = 0;
                            
                            $alist = fg($val['question']);
                            
                            $arrs = [];
                            foreach ($alist as $k=>$v) {
                                $arrs[$k]['key'] = $v;
                            }
                            
                            
                        
                            $tlist[$key]['title']  = $arrs;
                        }
                        
                        $data['is_assess'] = $info['is_assess'];
                        $data_rt['tinfo'] = $data;
                        $data_rt['tlist'] = $tlist;
                        echo apireturn(200,/*$dd['title']*/'daxuetang',$data_rt);die;
                    }
                    
                 }else{
                     echo apireturn(201,'您选择的题库不存在','');
                     die;
                 }
                
             }else{
                echo apireturn(201,'非考核时间,无法进入','');die;
             }
        }
    }
    //保存
    public function savecache(){

        $testid = $this->request->param('testid');
        
        $surplus_time = $this->request->param('surplus_time');
        
        $json = $this->request->param('json');
         
        $json = json_decode($json,true);
        
        // if(empty($surplus_time)){
        //     echo apireturn(201,'剩余时间不能为空','');
        //     die;
        // }
        
        if(empty($testid)){
            echo apireturn(201,'testid不能为空','');
            die;
        }else{
            $whrq['id'] = $testid;
            $whrq['uid'] = $this->user_id;
            $tinfos = Db::name('test')->where($whrq)->find();
            if(empty($tinfos)){
                echo apireturn(201,'请先开始答题','');
                die;
            }else{
                
                $dataz['surplus_time'] = $surplus_time;
                Db::name('test')->where($whrq)->update($dataz);
                
                $dinfo = Db::name('daxuetang')->where('id',$tinfos['qid'])->find();
                if(empty($dinfo)){
                    echo apireturn(201,'大学堂不存在','');
                    die;
                }else{

                    $sc = Db::name('country')->where('code',$this->country)->value('sc');
                    if($sc > 0){
                        $sctime = $sc*3600;
                        $time = time()-$sctime;
                    }else{
                        $time = time();
                    }

                    if($dinfo['end'] < $time){
                        echo apireturn(201,'本次考试已结束','');
                        die;
                    }
                }
            }
            
        }
    
        $whrq['id'] = $testid;
        $whrq['uid'] = $this->user_id;
        $status = Db::name('test')->where($whrq)->value('status');
        if($status == 2){
            echo apireturn(201,'已交卷，请勿重复提交','');
            die;
        }else{
            Db::name('test')->where($whrq)->setInc('is_agree',1);
        }
        
        
        foreach ($json as $key => $val){
            $whr['testid'] = $testid;
            $whr['tid'] = $val['id'];
            
            $hang = $val['hang'];

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
            $tinfo = Db::name('recordcache')->where($whr)->find();
            if($tinfo){
                $data['json'] = json_encode($hang);
                $data['is_answer'] = $val['is_answer'];
                $data['result'] = htmlspecialchars($reslut);
                $data['update_time'] = time();
                Db::name('recordcache')->where($whr)->update($data);
            }else{
                $data['testid'] = $testid;
                $data['tid'] = $val['id'];
                $data['is_answer'] = $val['is_answer'];
                $data['json'] = json_encode($hang);
                $data['result'] = $reslut;
                $data['create_time'] = time();
                $data['update_time'] = time();
                Db::name('recordcache')->insert($data);
            }
        }
        echo apireturn(200,'保存成功','');
        die;
    }

    

    //交卷
    public function subpaper(){
        
        $testid = $this->request->param('testid');
        $json = $this->request->param('json');
        
        
        // $mobile = Db::name('users')->where('id',$this->user_id)->value('mobile');
        // if($mobile == 18331088335 || $mobile == 13611211956){
        //     $tlist = Db::name('tiku')->field('id,result')->select();
        //     foreach ($tlist as $key => $val){
        //         $tlist[$key]['results'] = $val['result'];
        //     }
        //     $json = $tlist;
        // }
        
        if(empty($testid)){
            echo apireturn(201,'testid不能为空','');
            die;
        }else{
            $whrq['id'] = $testid;
            $whrq['uid'] = $this->user_id;
            $tinfos = Db::name('test')->where($whrq)->find();
            if(empty($tinfos)){
                echo apireturn(201,'请先开始答题','');
                die;
            }
            $dinfo = Db::name('daxuetang')->where('id',$tinfos['qid'])->find();
            if(empty($dinfo)){
                echo apireturn(201,'大学堂不存在','');
                die;
            }else{

                // $sc = Db::name('country')->where('code',$this->country)->value('sc');
                // if($sc > 0){
                //     $sctime = $sc*3600;
                //     $time = time()-$sctime;
                // }else{
                //     $time = time();
                // }

                if($dinfo['end'] < time()){
                    echo apireturn(201,'本次考试已结束','');
                    die;
                }
            }
        }
        
        if(empty($json)){
            echo apireturn(201,'答案不能为空','');
            die;
        }
        
        
        
        $whrq['id'] = $testid;
        $whrq['uid'] = $this->user_id;
        $status = Db::name('test')->where($whrq)->value('status');
        if($status == 2){
            echo apireturn(201,'已交卷，请勿重复提交','');
            die;
        }

        $json = json_decode($json,true);

        foreach ($json as $key => $val){
            
            $hang = $val['hang'];
               
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
                }else{
                    $data['score'] = $tinfo['score'];
                    $repeat = 100;
                }
            }else{
                if($aa == $bb){
                    $data['score'] = $tinfo['score'];
                }else{
                    $data['score'] = 0;
                }
            }
            similar_text($aa,$bb,$repeat);

            $data['testid'] = $testid;
            $data['tid'] = $val['id'];
            $data['result'] = $reslut;
            $data['json'] = json_encode($hang);
            $data['repeat'] = $repeat;
            $data['create_time'] = time();
            $data['update_time'] = time();
            Db::name('record')->insert($data);
            
        }
        //
        $score = Db::name('record')->where('testid',$testid)->sum('score');
        //错题数量
        $cnum = Db::name('record')->where('testid',$testid)->where('score',0)->count();
        
        $whrz['id'] = $testid;
        $datas['score'] = $score;
        $datas['status'] = 2;
        $datas['cnum'] = $cnum;
        $datas['is_tijiao'] = 1;
        
        if(Db::name('test')->where($whrz)->update($datas)){
            
            //站点数据更新
            $alist = Db::name('cateuser')->field('catid')->where('uid',$this->user_id)->where('leixing',1)->select();
            if(!empty($alist)){
                foreach ($alist as $keys => $vals){
                    updzhandian($tinfos['qid'],$this->user_id,$vals['catid'],$score);
                }
            }
            
            $data_rt['score'] = $score;
            $data_rt['time'] = $dinfo['title'];
            $data_rt['update_time'] = time();
            echo apireturn(200,'交卷成功',$data_rt);
            die;
        }else{
            echo apireturn(201,'交卷失败','');
            die;
        }
        
    }
    
    //开启评价交卷接口
    public function assess_subpaper(){
        
        $testid = $this->request->param('testid');
       
        if(empty($testid)){
            echo apireturn(201,'testid不能为空','');
            die;
        }else{
            $whrq['id'] = $testid;
            $whrq['uid'] = $this->user_id;
            $tinfos = Db::name('test')->where($whrq)->find();
            if(empty($tinfos)){
                echo apireturn(201,'请先开始答题','');
                die;
            }else{
                if($tinfos['status'] == 2){
                    echo apireturn(201,'已交卷，请勿重复提交','');
                    die;
                }
            }
            $dinfo = Db::name('daxuetang')->where('id',$tinfos['qid'])->find();
            if(empty($dinfo)){
                echo apireturn(201,'大学堂不存在','');
                die;
            }else{

                // $sc = Db::name('country')->where('code',$this->country)->value('sc');
                // if($sc > 0){
                //     $sctime = $sc*3600;
                //     $time = time()-$sctime;
                // }else{
                //     $time = time();
                // }

                if($dinfo['end'] < time()){
                    echo apireturn(201,'本次考试已结束','');
                    die;
                }
            }
        }
        
        $tlist = Db::name('tiku')->where('tiku_id',$tinfos['tid'])->order('id asc')->select();
        
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
                    }else{
                        $data['score'] = $tinfo['score'];
                        $repeat = 100;
                    }
                }else{
                    if($aa == $bb){
                        $data['score'] = $tinfo['score'];
                        $scores = $scores + $tinfo['score'];
                    }else{
                        $data['score'] = 0;
                    }
                }
            }else{
                echo apireturn(200,'请先保存','');
                die;
            }
            
            similar_text($aa,$bb,$repeat);

            $data['testid'] = $testid;
            $data['tid'] = $val['id'];
            $data['result'] = $reslut;
            $data['json'] = json_encode($hang);
            $data['repeat'] = $repeat;
            $data['create_time'] = time();
            $data['update_time'] = time();
            
            if(Db::name('record')->where('testid',$testid)->where('tid',$val['id'])->count() == 0){
                Db::name('record')->insert($data);
            }
        }
        
        //
        $score = Db::name('record')->where('testid',$testid)->sum('score');
        //错题数量
        $cnum = Db::name('record')->where('testid',$testid)->where('score',0)->count();
        
        $whrz['id'] = $testid;
        $datas['score'] = $score;
        $datas['status'] = 2;
        $datas['cnum'] = $cnum;
        $datas['is_tijiao'] = 1;
        
        if(Db::name('test')->where($whrz)->update($datas)){
            //站点数据更新
            $alist = Db::name('cateuser')->field('catid')->where('uid',$this->user_id)->where('leixing',1)->select();
            if(!empty($alist)){
                foreach ($alist as $keys => $vals){
                    updzhandian($tinfos['qid'],$this->user_id,$vals['catid'],$score);
                }
            }
            
            $dataupd['is_tijiao'] = 1;
            Db::name('assess')->where('uid',$this->user_id)->where('qid',$tinfos['qid'])->update($dataupd);
            
            
            $data_rt['score'] = $score;
            $data_rt['time'] = $dinfo['title'];
            $data_rt['update_time'] = time();
            echo apireturn(200,'交卷成功',$data_rt);
            die;
        }else{
            echo apireturn(201,'交卷失败','');
            die;
        }
    }
    
    //考核记录
    public function testlist(){

        $mfnum = Db::name('test')->where('uid',$this->user_id)->whereColumn('score','=','mscore')->where('is_tijiao',1)->count();
        $znum = Db::name('test')->where('uid',$this->user_id)->where('is_tijiao',1)->count();
        if($znum > 0){
            $wmfnum = $znum - $mfnum;
        }else{
            $wmfnum = 0;
        }
        $data['mfnum'] = $mfnum;
        $data['znum'] = $znum;
        $data['wmfnum'] = $wmfnum;
        
        $list = Db::name('test')
            ->where('uid',$this->user_id)
            ->where('is_tijiao',1)
            ->order('id desc')
            ->select();
        foreach ($list as $key => $val){
            $list[$key]['score'] = floatval($val['score']);
            $list[$key]['mscore'] = floatval($val['mscore']);
            $list[$key]['exam_name'] = Db::name('daxuetang')->where('id',$val['qid'])->value('exam_name');
        }
        
        $data['tlist'] = $list;
        
        
        //查询待审批数量
        $count = Db::name('flow_apply')->where('apply_uid',$this->user_id)->where('flow_leixing',2)->where('is_send',2)->where('is_read',1)->where('status',1)->count();
        
        $count2 = Db::name('flow_apply')->where('apply_uid',$this->user_id)->where('flow_leixing',3)->where('is_send',2)->where('is_read',1)->where('status',1)->count();
        
        $data['dspnum'] = $count + $count2;
        $data['spnum'] = $count;
        $data['csnum'] = $count2;
        
        $wxinfo = Db::name('weixin')->where('uid',$this->user_id)->find();
        if($wxinfo){
            $data['openid'] = $wxinfo['openid'];
        }else{
            $data['openid'] = '';
        }
        
        $data_rt['status'] = 200;
        $data_rt['msg'] = 'success';
        $data_rt['data'] = $data;
        
        return json_encode($data_rt);
        exit;
        
    }
    
    public function testrecord(){
        
        $where=[];

        $where[]=['t.uid', '=', $this->user_id];
        $where[]=['t.is_tijiao', '=', 1];
        
        //调取列表
        $list = Db::name('test')
            ->alias('t')
            ->leftJoin('daxuetang d','t.qid = d.id')
            ->field('t.*,d.exam_name as name')
            ->order('t.create_time DESC')
            ->where($where)
            ->select();
        
        foreach ($list as $key => $val){
            $list[$key]['score'] = floatval($val['score']);
            $list[$key]['mscore'] = floatval($val['mscore']);
            $list[$key]['update_time'] =  date('Y-m-d H:i:s',$val['update_time']);
        }
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $list;
        return json_encode($rs_arr,true);
        exit;
        
    }
    
    public function testdetail(){
        
        $where=[];
        
        $id = Request::param('id');
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = 'id不存在';
            return json_encode($rs_arr,true);
            exit;
        }else{
            
            $whrq['id'] = $id;
            $whrq['uid'] = $this->user_id;
            $tinfos = Db::name('test')->where($whrq)->find();
            if(empty($tinfos)){
                echo apireturn(201,'请先开始答题','');
                die;
            }
            if($tinfos['status'] != 2){
                echo apireturn(201,'请先交卷','');
                die;
            }
            $dinfo = Db::name('daxuetang')->where('id',$tinfos['qid'])->find();
            
            if(empty($dinfo)){
                echo apireturn(201,'大学堂不存在','');
                die;
            }else{
                
                $where[]=['t.id', '=', $id];
                $where[]=['t.uid', '=', $this->user_id];
                
                $info = Db::name('test')
                    ->alias('t')
                    ->leftJoin('daxuetang d','t.qid = d.id')
                    ->field('t.*,d.title as name')
                    ->where($where)
                    ->find();
                
                if($info){
                    $aa = Db::name('record')
                    ->alias('r')
                    ->leftJoin('tiku t','r.tid = t.id')
                    ->field('r.*')
                    ->where('r.testid',$id)
                    ->order('t.sort asc')
                    ->select();
                    foreach ($aa as $key => $val){
                        $aa[$key]['score'] = floatval($val['score']);
                    }
                    $info['list'] = $aa;
                    
                    $info['score'] = floatval($info['score']);
                    $info['mscore'] = floatval($info['mscore']);
                    $rs_arr['status'] = 200;
                    $rs_arr['msg'] = 'success';
                    $rs_arr['data'] = $info;
                    return json_encode($rs_arr,true);
                    exit;
                }else{
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = '无信息';
                    return json_encode($rs_arr,true);
                    exit;
                }  
            }
        }
    }
    
    //错题卡
    public function answer(){
        $id = $this->request->param('id');
        if(empty($id)){
            echo apireturn(201,'id不能为空','');
            die;
        }else{
            $whrq['id'] = $id;
            $whrq['uid'] = $this->user_id;
            $tinfos = Db::name('test')->where($whrq)->find();
            if(empty($tinfos)){
                echo apireturn(201,'请先开始答题','');
                die;
            }
            $dinfo = Db::name('daxuetang')->where('id',$tinfos['qid'])->find();
            if(empty($dinfo)){
                echo apireturn(201,'大学堂不存在','');
                die;
            }else{
                
                $tlist =  Db::name('record')->where('testid',$id)->where('score',0)->select();
                foreach ($tlist as $key =>$val){
                    $whr['id'] = $val['tid'];
                    $question = Db::name('tiku')->where($whr)->value('question');
                    $tlist[$key]['title'] = $question;
                    $tlist[$key]['type_id'] = Db::name('tiku')->where($whr)->value('type_id');
                    $tlist[$key]['type_name'] = Db::name('tiku')->where($whr)->value('type_name');
                    $tlist[$key]['results'] = Db::name('tiku')->where($whr)->value('result');
                    $tlist[$key]['resultr'] = Db::name('tiku')->where($whr)->value('answers');
                    $tlist[$key]['scores'] = floatval(Db::name('tiku')->where($whr)->value('score'));
                    $tlist[$key]['name'] = $dinfo['title'];
                    
                    $alist = fg($question);
                        
                    $arrs = [];
                    foreach ($alist as $k=>$v) {
                        $arrs[$k]['key'] = $v;
                    }
                
                    $tlist[$key]['titles']  = $arrs;
                }
                echo apireturn(200,'success',$tlist);
                die;
            }
        }
    }
    
    
    //练兵场开始答题
    public function starts(){
        $tid = $this->request->param('tid');
        if(empty($tid)){
            echo apireturn(201,'请选择题库','');
            die;
        }else{
        
            $whrt['id'] = $tid;
            //$whr['tid'] = $type_id;
            $info = Db::name('tikus')->where($whrt)->find();
            if($info){
                //判断有没有生成考核记录
                $whrs['tid'] = $tid;
                $whrs['uid'] = $this->user_id;
                $whrs['is_tijiao'] = 0;
                $tinfo =  Db::name('tests')->where($whrs)->find();
                if($tinfo){
                    
                    $tlist =  Db::name('tiku')->where('tiku_id',$tid)->order('sort asc')->select();
                    foreach($tlist as $key => $val){
                        
                        $tlist[$key]['results'] = '';
                        $tlist[$key]['is_answer'] = 0;
                        
                        if($val['type_id'] != 4){
                            $hlist = explode("\n", $val['z_result']);
                        }else{
                            $hlist = fg($val['question']);
                            
                            $llist = explode("\n", $val['result']);
                        }
                        
                        $arr = [];
                        $i = 0;
                        foreach ($hlist as $k=>$v) {
                            $arr[$k]['key'] = $v;
                            $arr[$k]['value'] = '';
                            $arr[$k]['is_checked'] = 0;
                            if($v == '_'){
                                $lllist = explode("^", $llist[$i]);
                                $arr[$k]['length'] = strlen(preg_replace("#[^\x{00}-\x{ff}]#u", '*', $lllist[0])) + 1;
                                $i = $i + 1;
                            }
                        }
                        
                        
                        $tlist[$key]['hang']  = $arr;
                        $alist = fg($val['question']);
                        
                        $arrs = [];
                        foreach ($alist as $k=>$v) {
                            $arrs[$k]['key'] = $v;
                        }
                    
                        $tlist[$key]['title']  = $arrs;
                    }
                    
                    $data_rt['tinfo'] = $tinfo;
                    $data_rt['tlist'] = $tlist;
                    echo apireturn(200,/*$dd['title']*/'daxuetang',$data_rt);die;
                    
                    
                }else{
                    
                    $zscore = Db::name('tiku')->where('tiku_id',$tid)->sum('score');
                    
                    $data['tid'] = $tid;
                    $data['uid'] = $this->user_id;
                    $data['score'] = 0;
                    $data['mscore'] = $zscore;
                    $data['status'] = 1;
                    $data['total_time'] = $info['exam_time']*60;
                    $data['surplus_time'] = $info['exam_time']*60;
                    $data['create_time'] = time();
                    $data['update_time'] = time();
                    $id = Db::name('tests')->insertGetId($data);
                    $data['id'] = $id;
                    
                    $tlist =  Db::name('tiku')->where('tiku_id',$tid)->order('sort asc')->select();
                    foreach($tlist as $key => $val){
                        $tlist[$key]['score'] = floatval($val['score']);

                        if($val['type_id'] != 4){
                            $hlist = explode("\n", $val['z_result']);
                        }else{
                            $hlist = fg($val['question']);
                            $llist = explode("\n", $val['result']);
                        }
                        
                        $arr = [];
                        $i = 0;
                        foreach ($hlist as $k=>$v) {
                            $arr[$k]['key'] = $v;
                            $arr[$k]['value'] = '';
                            $arr[$k]['is_checked'] = 0;
                            if($v == '_'){
                                $lllist = explode("^", $llist[$i]);
                                $arr[$k]['length'] = strlen(preg_replace("#[^\x{00}-\x{ff}]#u", '*', $lllist[0])) + 1;
                                $i = $i + 1;
                            }
                        }
                        
                        $tlist[$key]['hang']  = $arr;
                        $tlist[$key]['hangnum']  = count($arr);

                        $tlist[$key]['is_answer'] = 0;
                        $alist = fg($val['question']);
                        
                        $arrs = [];
                        foreach ($alist as $k=>$v) {
                            $arrs[$k]['key'] = $v;
                        }
                    
                        $tlist[$key]['title']  = $arrs;
                    }
                    
                    $data_rt['tinfo'] = $data;
                    $data_rt['tlist'] = $tlist;
                    echo apireturn(200,'success',$data_rt);die;
                }
            }else{
                 echo apireturn(201,'您选择的题库不正确','');die;
            }
        }
    }
    
    
    //练兵场交卷
    public function subpapers(){
        
        $testid = $this->request->param('testid');
        $json = $this->request->param('json');
        
    //   $fp = fopen('ceshi.txt', 'w');
    //   fwrite($fp, $testid);
    //   fwrite($fp, $json);
    //   fclose($fp);
        
        if(empty($testid)){
            echo apireturn(201,'testid不能为空','');
            die;
        }else{
            $whrq['id'] = $testid;
            $whrq['uid'] = $this->user_id;
            $tinfos = Db::name('tests')->where($whrq)->find();
            if(empty($tinfos)){
                echo apireturn(201,'请先开始答题','');
                die;
            }
            $dinfo = Db::name('tikus')->where('id',$tinfos['tid'])->find();
            if(empty($dinfo)){
                echo apireturn(201,'题库不存在','');
                die;
            }
        }
        
        if(empty($json)){
            echo apireturn(201,'答案不能为空','');
            die;
        }
        
        $whrq['id'] = $testid;
        $whrq['uid'] = $this->user_id;
        $status = Db::name('tests')->where($whrq)->value('status');
        if($status == 2){
            echo apireturn(201,'已交卷，请勿重复提交','');
            die;
        }
        
        $json = json_decode($json,true);
        
        foreach ($json as $key => $val){

            $hang = $val['hang'];

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
                }else{
                    $data['score'] = $tinfo['score'];
                    $repeat = 100;
                }
            }else{
                if($aa == $bb){
                    $data['score'] = $tinfo['score'];
                }else{
                    $data['score'] = 0;
                }
            }
            similar_text($aa,$bb,$repeat);

            $data['testid'] = $testid;
            $data['tid'] = $val['id'];
            $data['result'] = $reslut;
            $data['json'] = json_encode($hang);
            $data['repeat'] = $repeat;
            $data['create_time'] = time();
            $data['update_time'] = time();
            
            if(Db::name('records')->where('testid',$testid)->where('tid',$val['id'])->count() == 0){
                Db::name('records')->insert($data);
            }
            
        }
        //
        $score = Db::name('records')->where('testid',$testid)->sum('score');
        //错题数量
        $cnum = Db::name('records')->where('testid',$testid)->where('score',0)->count();
        
        $whrz['id'] = $testid;
        $datas['score'] = $score;
        $datas['status'] = 2;
        $datas['cnum'] = $cnum;
        $datas['is_tijiao'] = 1;
        $datas['update_time'] = time();
        
        if(Db::name('tests')->where($whrz)->update($datas)){
            
            $data_rt['score'] = $score;
            $data_rt['time'] = $dinfo['title'];
            $data_rt['update_time'] = time();
            echo apireturn(200,'交卷成功',$data_rt);
            die;
        }else{
            echo apireturn(201,'交卷失败','');
            die;
        }
        
    }
    
    //考核记录（练兵场）
    public function testlists(){
        
        $score = Db::name('tiku')->sum('score');
        
        $mfnum = Db::name('tests')->where('uid',$this->user_id)->where('score',$score)->where('is_tijiao',1)->count();
        $znum = Db::name('tests')->where('uid',$this->user_id)->where('is_tijiao',1)->count();
        if($znum > 0){
            $wmfnum = $znum - $mfnum;
        }else{
            $wmfnum = 0;
        }
        $data['mfnum'] = $mfnum;
        $data['znum'] = $znum;
        $data['wmfnum'] = $wmfnum;
        
        $list = Db::name('tests')
            ->where('uid',$this->user_id)
            ->where('is_tijiao',1)
            ->order('id desc')
            ->select();
        foreach ($list as $key => $val){
            $list[$key]['score'] = floatval($val['score']);
            $list[$key]['mscore'] = floatval($val['mscore']);
        }
        
        $data['tlist'] = $list;
        
        $data_rt['status'] = 200;
        $data_rt['msg'] = 'success';
        $data_rt['data'] = $data;
        
        return json_encode($data_rt);
        exit;
        
    }
    
    //考试记录（练兵场）
    public function testrecords(){
        
        $where=[];

        $where[]=['t.uid', '=', $this->user_id];
        $where[]=['t.is_tijiao', '=', 1];
        
        //调取列表
        $list = Db::name('tests')
            ->alias('t')
            ->leftJoin('tikus tks','t.tid = tks.id')
            ->field('t.*,tks.title as name')
            ->order('t.create_time DESC')
            ->where($where)
            ->select();
        
        foreach ($list as $key => $val){
            $list[$key]['score'] = floatval($val['score']);
            $list[$key]['mscore'] = floatval($val['mscore']);
            $list[$key]['update_time'] = date('Y-m-d H:i:s',$val['update_time']);
        }
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $list;
        return json_encode($rs_arr,true);
        exit;
        
    }
    
    public function testdetails(){
        
        $where=[];
        
        $id = Request::param('id');
        if(empty($id)){
            
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = 'id不存在';
            return json_encode($rs_arr,true);
            exit;
            
        }else{
            
            $whrq['id'] = $id;
            $whrq['uid'] = $this->user_id;
            $tinfos = Db::name('tests')->where($whrq)->find();
            if(empty($tinfos)){
                echo apireturn(201,'请先开始答题','');
                die;
            }
            if($tinfos['status'] != 2){
                echo apireturn(201,'请先交卷','');
                die;
            }
            $dinfo = Db::name('tikus')->where('id',$tinfos['tid'])->find();
            
            if(empty($dinfo)){
                echo apireturn(201,'你选择的题库不正确','');
                die;
            }else{
                
                $where[]=['t.id', '=', $id];
                $where[]=['t.uid', '=', $this->user_id];
                
                $info = Db::name('tests')
                    ->alias('t')
                    ->leftJoin('tikus tks','t.tid = tks.id')
                    ->field('t.*,tks.title as name')
                    ->where($where)
                    ->find();
                
                if($info){
                    $aa = Db::name('records')
                    ->where('testid',$id)
                    ->select();
                    foreach ($aa as $key => $val){
                        $aa[$key]['score'] = floatval($val['score']);
                    }
                    $info['list'] = $aa;
                    
                    $info['score'] = floatval($info['score']);
                    $info['mscore'] = floatval($info['mscore']);
                    $rs_arr['status'] = 200;
                    $rs_arr['msg'] = 'success';
                    $rs_arr['data'] = $info;
                    return json_encode($rs_arr,true);
                    exit;
                }else{
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = '无信息';
                    return json_encode($rs_arr,true);
                    exit;
                }  
            }
        }
    }
    
    
    //错题卡（练兵场）
    public function answers(){
        $id = $this->request->param('id');
        if(empty($id)){
            echo apireturn(201,'id不能为空','');
            die;
        }else{
            $whrq['id'] = $id;
            $whrq['uid'] = $this->user_id;
            $tinfos = Db::name('tests')->where($whrq)->find();
            if(empty($tinfos)){
                echo apireturn(201,'请先开始答题','');
                die;
            }
            $dinfo = Db::name('tikus')->where('id',$tinfos['tid'])->find();
            if(empty($dinfo)){
                echo apireturn(201,'您选择的题库不正确','');
                die;
            }else{
                
                $tlist =  Db::name('records')->where('testid',$id)->where('score',0)->select();
                foreach ($tlist as $key =>$val){
                    $whr['id'] = $val['tid'];
                    $question = Db::name('tiku')->where($whr)->value('question');
                    $tlist[$key]['title'] = $question;
                    $tlist[$key]['type_id'] = Db::name('tiku')->where($whr)->value('type_id');
                    $tlist[$key]['type_name'] = Db::name('tiku')->where($whr)->value('type_name');
                    $tlist[$key]['results'] = Db::name('tiku')->where($whr)->value('result');
                    $tlist[$key]['resultr'] = Db::name('tiku')->where($whr)->value('answers');
                    $tlist[$key]['scores'] = floatval(Db::name('tiku')->where($whr)->value('score'));
                    $tlist[$key]['name'] = $dinfo['title'];
                    $alist = fg($question);
                        
                    $arrs = [];
                    foreach ($alist as $k=>$v) {
                        $arrs[$k]['key'] = $v;
                    }
                
                    $tlist[$key]['titles']  = $arrs;
                }
                echo apireturn(200,'success',$tlist);
                die;
            }
        }
    }
    
    //自我测评
    public function assessment(){
        $id = $this->request->param('id');
        $answer = $this->request->param('answer');

        if(empty($id)){
            echo apireturn(201,'id不能为空','');
            die;
        }else{
        
            $datas['id'] = $id;
            $datas['answer'] = $answer;
            $datas['status'] = 1;
            $datas['update_time'] = time();
            $m = new M();
            $result = $m->editPost($datas);
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
    
    //获取自我测评结果
    public function get_assessment(){
        $qid = $this->request->param('qid');

        if(empty($qid)){
            echo apireturn(201,'qid不能为空','');
            die;
        }else{
            
            $tid = Db::name('daxuetang')->where('id',$qid)->value('tiku_id');
            //全局查询条件
            $where=[];
            if(!empty($tid)){
                $where[]=['tiku_id', '=', $tid];
            }else{
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '题库id不存在';
                return json_encode($rs_arr,true);
                exit;
            }
            //调取列表
            $list = Db::name('assess_type')
                ->order('id ASC')
                ->where($where)
                ->where('parent_id',0)
                ->select();
            foreach ($list as $key => $val){
                if($val['score'] == 0){
                    $lists = Db::name('assess_type')
                            ->order('id ASC')
                            ->where($where)
                            ->where('parent_id',$val['id'])
                            ->select();
                    foreach ($lists as $keys => $vals){
                        $lists[$keys]['assess_score'] = 0;
                    }
                    
                }else{
                    $list[$key]['assess_score'] = 0;
                    $lists = array();
                }
                
                $list[$key]['beizhu'] = '';
                $list[$key]['erji'] = $lists;
            }
            
            $whrz['type'] = 1;
            $whrz['qid'] = $qid;
            $whrz['uid'] = $this->user_id;
            $whrz['otherid'] = $this->user_id;
            $zlist = Db::name('assess')->where($whrz)->find();
            if(count($zlist) > 0){
                
                
                $answers = json_decode($zlist['answer'],true);
                $zlist['answers'] = $answers;
                
           
                $zongnum = 1;
                $onenum = 0;
                foreach($answers as $key =>$val){
                    
                    if($val['score'] == 0){
                        
                        $twonum = 0;
                        foreach ($val['erji'] as $keys => $vals){
                            //echo $vals['assess_score'].'-';
                            if($vals['assess_score'] > 0){
                                $twonum = $twonum + 1;
                            }
                            
                            //echo $twonum.' ';
                        }
                        if($twonum == 0){
                            $zongnum = 0;
                            $onenum = 0;
                        }else{
                            $onenum = $onenum + 1;
                        }
                        //echo '!'.$twonum.'!';
                       
                    }else{
                        if($val['assess_score'] > 0 ){
                            $onenum = $onenum + 1;
                        }
                    }
                }
                //die;
                if($onenum == 0){
                    $zongnum = 0;
                }
                if($zongnum > 0){
                    $zlist['is_submit'] = 1;
                }else{
                    $zlist['is_submit'] = 0;
                }
                
                
                echo apireturn(200,'success',$zlist);
                die;
                
            }else{
                
                $dataadd['type'] = 1;
                $dataadd['qid'] = $qid;
                $dataadd['uid'] = $this->user_id;
                $dataadd['username'] = Db::name('users')->where('id',$this->user_id)->value('username');
                $dataadd['otherid'] = $this->user_id;
                $dataadd['usernames'] = Db::name('users')->where('id',$this->user_id)->value('username');
                $dataadd['answer'] = json_encode($list);
                $dataadd['status'] = 1;
                $dataadd['create_time'] = time();
                $dataadd['update_time'] = time();
                $id = Db::name('assess')->insertGetId($dataadd);
                $dataadd['id'] = $id;
                $dataadd['answers'] = $list;
                
                if($id) {
                    echo apireturn(200,'提交成功',$dataadd);
                    die;
                }else{
                    echo apireturn(201,'测评失败','');
                    die;
                }
                
            }
        
        }
    }
    
}