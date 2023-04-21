<?php
namespace app\api\controller;

use think\Db;

use think\Request;

use wxlogin\WXBizDataCrypt;

class Wechat{
    
    public function index(){
        $code = input('param.code');
        $signature = input('param.signature');
        $rawData = input('param.rawData');
        $encryptedData = input('param.encryptedData');
        $iv = input('param.iv');
        $params = [
            'appid'=>'wx491d0319de706ff1',
            'secret'=>'6d82af90c4ab30acc08aa588a7f951dc',
            'js_code'=>$code,
            'grant_type'=>'authorization_code'
        ];
        $url ="https://api.weixin.qq.com/sns/jscode2session?".http_build_query($params);
        $wx_result = curl($url);
        $result = json_decode(json_encode($wx_result),true);
        
        if (isset($result['errcode']) AND $result['errcode']) {
            echo apireturn(201,'用户登录信息异常','');die;
        }
        
        $openid = $result['openid'];
        $session_key = $result['session_key'];
        $signature2 = sha1($rawData . $session_key);
        if ($signature != $signature2) {
            echo apireturn(201,'数据签名验证失败','');die;
        }
        $pc = new WXBizDataCrypt(config('app.program.appid'), $session_key);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            $data = json_decode($data,true);
            
            echo apireturn(200,'登录成功',$data);die;
        } else {
            echo apireturn(3002,'登录失败错误码：'+$errCode,'');die;
        }
    }


}