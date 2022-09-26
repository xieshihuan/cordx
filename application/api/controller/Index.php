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
        

class Index
{
    public function assess_name(){
        
        $text = '差,可,中,良,优';
        $tlist = explode(',',$text);
        echo apireturn(200,'success',$tlist);die;
        
    }  
    public function ceshiaaa(){
        $dataupd['is_tijiao'] = 1;
         Db::name('assess')->where('uid',191)->where('qid',1)->update($dataupd);
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

            $tlist = Db::name('users')->field('id')->where('is_delete',1)->where('id','exp','not In '.$uuid)->select();

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
}
