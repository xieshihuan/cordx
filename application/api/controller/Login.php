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
    
    public function copys(){
        $list = Db::name('recordcache')->where('testid',2375)->select();
        foreach($list as $key => $val){
            $ti = Db::name('recordcache')->where('testid',2378)->where('tid',$val['tid'])->find();
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




}
