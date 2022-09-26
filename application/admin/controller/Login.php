<?php
/**
 * +----------------------------------------------------------------------
 * | 登录制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use app\common\model\System;
use think\Controller;
use app\admin\model\Users;
use think\captcha\Captcha;
use think\facade\Session;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

use app\common\model\Users as M;

use think\cache\driver\Redis;

class Login extends Controller
{
    
    //获取城市列表
    public function get_country(){
        $list = Db::name('country')->select();
        return json_encode(['status'=>200,'msg'=>'success','data'=>$list]);
    }
    
    
    //校验登录
    public function checkLogin(){
        $m = new Users();
        return $m->checkLogin();
    }
    
      
    //验证码
    public function captcha(){
        
        $config =    [
            // 验证码字体大小
            'fontSize'    =>    30,
            // 验证码位数
            'length'      =>    4,
            // 关闭验证码杂点
            'useNoise'    =>    true,
            // 是否画混淆曲线
            'useCurve' => false,
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }
    
    //退出登录
    public function sendCode(){
        
        $phone = Request::param('mobile');
        if(empty($phone)){
            return json_encode(['status'=>500,'msg'=>'mobile not found']);
        }
        $info = Db::name('users')->where('mobile',$phone)->find();
        
        if($info){
            
            $country = $info['country'];
            // 生成4位验证码
            $code = mt_rand(1000, 9999);
            //redis存储手机验证码
            $options['select'] = 2;
            $Redis = new Redis($options);
            
            //判断是否过期 未过期重新获取删除
            $phonecode = $Redis->has('phone_' . $phone);
            
            if($phonecode == 1){
                $Redis->rm('phone_' . $phone);
            }
            
            $Redis->set('phone_' . $phone, $code, 300);
            
             //发送用户短信（密码）
            if($country == 86){
        	    $time = '5分钟';
                $res = saiyouSms($phone,$code,$time);
                $ress = json_decode($res,true);
                if($ress['status'] == 'success'){
                    return json_encode(['status'=>200,'msg'=>'Send a success']);
                }else{
                    return json_encode(['status'=>500,'msg'=>$ress['msg']]);
                }
            }else{
                $res = YzxSms($code,'00'.$country.$phone);
                if($res['code'] == 000000){
                    return json_encode(['status'=>200,'msg'=>'Send a success']);
                }else{
                    return json_encode(['status'=>500,'msg'=>$res['msg']]);
                }
            }
        
        }else{
            $data_rt['status'] = 201;
            $data_rt['msg'] = '用户不存在';
        }
        
        return json_encode($data_rt,true);
        
    }
    
    public function resetPassword(){
        $phone = Request::param('mobile');
        $code = Request::param('code');
        $password = Request::param('password');
        $password2 = Request::param('password2');
        if(empty($phone)){
            return json_encode(['status'=>500,'msg'=>'请输入手机号']);
        }
        if(empty($code)){
            return json_encode(['status'=>500,'msg'=>'请输入验证码']);
        }
        if(empty($password)){
            return json_encode(['status'=>500,'msg'=>'请输入密码']);
        }
        if(empty($password2)){
            return json_encode(['status'=>500,'msg'=>'请再次输入密码']);
        }
        if($password != $password2){
            return json_encode(['status'=>500,'msg'=>'两次输入的密码不一致']);
        }
        
        $info = Db::name('users')->where('mobile',$phone)->find();
        
        if($info){
            
            //验证手机验证码
            $options['select'] = 2;
            $Redis = new Redis($options);
            $pcode = $Redis->get('phone_' . $phone);
            if($code != $pcode){
                $data_rt['status'] = 500;
                $data_rt['msg'] = '验证码不正确';
                return json($data_rt);
            }
            
            $data['password'] = md5($password.'core2022');
            $data['id'] = $info['id'];
            
            $m = new M();
            $result =  $m->editPost($data);
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
        }else{
            $data_rt['status'] = 500;
            $data_rt['msg'] = '用户不存在';
        }
        
        return json_encode($data_rt,true);
        
    }
    
    
    //退出登录
    public function logout(){
        $authtoken = Request::param('authtoken');
        $admin_id = Db::name('users')->where('token',$authtoken)->value('id');
        
        if($admin_id){
            $where['id'] = $admin_id;
            $data['token'] = '';
            if(Users::update($data,$where)){
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
