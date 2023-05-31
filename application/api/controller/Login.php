<?php

namespace app\api\controller;
use think\Controller;
use app\admin\model\Users;
use think\captcha\Captcha;
use think\facade\Request;
use think\facade\Cache;
use think\Db;
use think\cache\driver\Redis;

use app\common\model\Users as M;

class Login extends Controller
{
   public function get_fieldlist(){
       
       $arr = get_fieldlist('tp_ruzhi');
       print_r($arr);
       die;
   }
   public function ceshiqqq11111(){
       $a = 38.901603;
       $b = 115.569456;
       $c = 38.90361;
       $d = 115.566915;
       $jl = getDistance($a,$b,$c,$d,$len_type = 1,$decimal = 2);
       echo $jl;
       //echo date('W',strtotime('2024-01-01'));
       die;
   }
   
    //更新打卡统计
    public function aaa(){
        $list = Db::name('check_count')->select();
        //$error = '';
        foreach ($list as $key => $val){
            $whr['uid'] = $val['uid'];
            $whr['attendance_group_id'] = $val['attendance_group_id'];
            $whr['classesid'] = $val['classesid'];
            $whr['riqi'] = $val['day'];
            $zcnum = Db::name('check_log')->where('status',1)->where($whr)->count();
            $cdnum = Db::name('check_log')->where('status',2)->where($whr)->count();
            $ztnum = Db::name('check_log')->where('status',3)->where($whr)->count();
            // if($zcnum != $val['zcnum'] || $cdnum != $val['cdnum'] || $ztnum != $val['ztnum']){
            //     $error .= $val['id'].'-'; 
            // }
            
            $data['zcnum'] = $zcnum;
            $data['cdnum'] = $cdnum;
            $data['ztnum'] = $ztnum;
            Db::name('check_count')->where('id',$val['id'])->update($data);
            
        }
        // echo $error;
        // die;
    }
    //批量删除人员组织
    public function ceshi_tzs(){
        //69,191,295,492,570,609,746,875,908
        $openlist = Db::name('weixin')->field('uid,openid')->where('uid','in','875')->select();
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $url=$http_type.$_SERVER['HTTP_HOST']."/api/wxnofiy/tongzhi";
                
        foreach ($openlist as $k => $v){
            if($v['openid']){
                 //所有字段都可为空---审批拒绝
                $dataq['uname'] = Db::name('users')->where('id',$v['uid'])->value('username');
                $dataq['neirong'] = '测试速度';
                $dataq['shijian'] = '测试时间';
                $dataq['openid'] = $v['openid'];
                $dataq['type'] = 3;
                Db::name('wxnotice')->insert($dataq);
                
                http_curl($url,'post','json',$dataq);
            }
        }
    }
    public function copys(){
        $list = Db::name('recordcache')->where('testid',10347)->select();
        foreach($list as $key => $val){
            $ti = Db::name('recordcache')->where('testid',10219)->where('tid',$val['tid'])->find();
            $data['json'] = $ti['json'];
            $data['result'] = $ti['result'];
            Db::name('recordcache')->where('id',$val['id'])->update($data);
        }
    }
    
    public function tlist(){
        $a = Db::name('tiku')->select();
        return json_encode($a);
    }

    //校验登录
    public function checkLogin(){
        $m = new Users();
        return $m->checkLogins();
    }

    //校验登录
    public function checkLogins(){
        $m = new Users();
        return $m->checkLoginss();
    }

    //发送验证码
    public function sendCode(){
        
        extract(input());
        
        //判断手机号不为空
        if(empty($phone)) {
       
            $data_rt['status'] = 201;
            $data_rt['msg'] = '请输入手机号';
            return json_encode($data_rt,true);
        
        }

        $uinfo = Db::name('users')->where('mobile',$phone)->find();
        if($uinfo){
            if(empty($timer)) {

                $data_rt['status'] = 201;
                $data_rt['msg'] = '请输入时间';
                return json_encode($data_rt,true);

            }

            if(empty($ticket)) {

                $data_rt['status'] = 201;
                $data_rt['msg'] = '请输入签名';
                return json_encode($data_rt,true);

            }

            if(empty($sign)) {

                $data_rt['status'] = 201;
                $data_rt['msg'] = '请输入签名';
                return json_encode($data_rt,true);

            }

            $ss =  substr($phone,0,3).$timer.substr($phone,7,4).'baoyitong2022';
            $tickets =  md5($ss);
            //$tickets = hash('sha512', $sss);

            if($ticket != $tickets){
                return json(['status'=>201,'msg'=>'ticket签名不正确']);
            }

            $signs = md5($phone.'baoyitong2022');

            if($sign != $signs){
                return json(['status'=>201,'msg'=>'签名不正确']);
            }
            // 生成4位验证码
            $code = mt_rand(1000, 9999);
            //redis存储手机验证码
            $options['select'] = 3;
            $Redis = new Redis($options);

            //判断是否过期 未过期重新获取删除
            $phonecode = $Redis->has('phone_' . $phone);

            if($phonecode == 1){
                $Redis->rm('phone_' . $phone);
            }

            $Redis->set('phone_' . $phone, $code, 300);

            if(empty($code)){
                return json(['status'=>201,'msg'=>'验证码获取失败']);
            }else{

                $res = saiyouSms($phone,$code);
                $ress = json_decode($res,true);
                if($ress['status'] == 'success'){
                    return json(['status'=>200,'msg'=>'发送成功']);
                }else{
                    return json(['status'=>500,'msg'=>$ress['msg']]);
                }

            }
        }else{
            $data_rt['status'] = 201;
            $data_rt['msg'] = '该账户不存在';
            return json_encode($data_rt,true);
        }

    }
    
    static public function MakeToken(){
		$str = md5(uniqid(md5(microtime(true)), true)); //创建唯一token
		$str = sha1($str);
		return $str;
	}
	
    public function resetVefity(){

        $phone = $this->request->param('phone');
        $code = $this->request->param('code');
        //判断手机号不为空
        if(!empty($phone)) {
            $where = [
                'mobile' => $phone,
            ];
            $data['mobile'] = $phone;
            //验证手机验证码
            $options['select'] = 3;
            $Redis = new Redis($options);
            $pcode = $Redis->get('phone_' . $phone);
            if($code != $pcode){
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '验证码不正确';
                return json_encode($rs_arr,true);
                exit;
            }else{
                $token = $this->MakeToken();
               
                //更新登录IP和登录时间
                Db::name('users')->where('mobile', $phone)->update(['access_token' => $token]);

                 
                $uinfo = Db::name('users')->where(['mobile'=>$phone])->find();
                
                $admin['uinfo'] = $uinfo;
                
                $rs_arr['status'] = 200;
                $rs_arr['msg'] = 'success';
                $rs_arr['data'] = $admin;
                return json_encode($rs_arr,true);
                exit;
                
            }
        }else{
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请输入手机号';
            return json_encode($rs_arr,true);
            exit;
        }
       
    }
 
 
	
 
    
    //退出登录
    public function logout(){
        $access_token = Request::param('access_token');
        $user_id = Db::name('users')->where('access_token',$access_token)->value('id');
        
        if($user_id){
            $where['id'] = $user_id;
            $data['access_token'] = '';
            if(M::update($data,$where)){
                $data_rt['status'] = 200;
                $data_rt['msg'] = '退出成功';
            }else{
                $data_rt['status'] = 500;
                $data_rt['msg'] = '退出失败';
            }
        }else{
            $data_rt['status'] = 500;
            $data_rt['msg'] = '用户不存在';
        }
        
        return json_encode($data_rt,true);
        
    }
    
    //发送提醒短信
    public function sendsms(){
        
        $time = date('Y-m-d',time());
        $list = Db::name('remind')->where('remind_time',$time)->where('status',1)->select();
        
        foreach($list as $key => $val){
            $phonelist = explode('^',$val['phone']);
            foreach ($phonelist as $keys => $vals){
                $res = saiyounotice($vals,$val['neirong']);
                $ress = json_decode($res,true);
                if($ress['status'] == 'success'){
                    
                    $data['status'] = 2;
                    Db::name('remind')->where('id',$val['id'])->where('status',1)->update($data);
                    
                    echo $val['id'].'执行成功';
                    
                }else{
                    
                    $data['status'] = 3;
                    Db::name('remind')->where('id',$val['id'])->where('status',1)->update($data);
                    
                    echo $val['id'].'发送失败';
                    
                    saiyounotice('18601366183',$val['id'].'消息提醒发送失败');
                    saiyounotice('18331088335',$val['id'].'消息提醒发送失败');
                }
            }
        }
        
    }
    
    public function ceshiduanxin(){
        saiyounotice('18331088335','消息提醒发送失败');
    }

      /*获取access_token,不能用于获取用户信息的token*/
    public  function getAccessToken()
    {
        $appid = 'wx491d0319de706ff1';  //企业appid
        $secret = '6d82af90c4ab30acc08aa588a7f951dc';  //企业secret

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
        exit();
    }
    //图片合法性验证
    public function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);

        return $output;
        exit();

    }
    //  获取手机号
    public function getPhoneNumber(){
        $tmp = $this->getAccessToken();
        $tmptoken = json_decode($tmp);
        $token = $tmptoken->access_token;
        $data['code'] = $_GET['code'];//前端获取code

        $url = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=$token";
        $info = $this->http_request($url,json_encode($data),'json');
        // 一定要注意转json，否则汇报47001错误
        $tmpinfo = json_decode($info,true);

        print_r($tmpinfo);
        die;
        
        $code = $tmpinfo->errcode;
        $phone_info = $tmpinfo->phone_info;
        
        //手机号
        $phoneNumber = $phone_info->phoneNumber;
        if($code == '0'){
            echo json_encode(['code'=>1,'msg'=>'请求成功','phoneNumber'=>$phoneNumber]);
            die();
        }else{
            echo json_encode(['code'=>2,'msg'=>'请求失败']);
            die();
        }

    }

    //查看重复
    public function chongfu(){
        $list = Db::name('product_relation')->select();
        
        foreach ($list as $key => $val){
            $id = '4,8,14,40,46';
            $ids = explode(',',$id);
            if(in_array($val['spec_id'],$ids)){
                $whr = [];
                $whr[] = ['product_id','<>',$val['product_id']];
                $whr[] = ['result','=',$val['result']];
                $cf = Db::name('product_relation')->where($whr)->select();
                if(count($cf) > 0){
                    echo count($cf).'-'.$val['result'].'-'.$val['product_id'].'-'.Db::name('product_relation')->where($whr)->value('product_id').' | ';
                }
            }
            
        }
    }

}
