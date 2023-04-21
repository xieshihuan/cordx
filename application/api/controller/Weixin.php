<?php
namespace app\api\controller;

use think\Db;

use think\Request;

use wxlogin\WXBizDataCrypt;

class Weixin{
    
        /**
     * 用于请求微信接口获取数据
     * @param $url
     * @return bool|string
     */
    public function get_by_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output, true);
    }
    /**
     * snsapi_base授权获取用户信息 入口
     */
    public function getWxForBase()
    {
        $appid = "wx5601175d5dd97b90";//自己的微信公众号 appid
        $redirect_uri = urlencode("https://cordxapi.baoyitong.com.cn/getWxInfo");//此处填写自己项目中的地址能访问到下面getWxBaseInfo方法 （本方法加了路由处理）
        //获取到code
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_base&state=123#wechat_redirect";
        $this->getWxBaseInfo($url, 302);//携带微信验证的code跳转到snsapi_base授权获取用户基本信息 即下面的方法getWxBaseInfo

    }
    /**
     * snsapi_base授权获取用户基本信息
     * @return bool|string
     */
    public function getWxBaseInfo()
    {
        $appID = "wx5601175d5dd97b90";//自己微信公众号中的appid
        $appSecret = "9c58ba855e8cc2cae279cf7997c3f54f";//自己微信公众号中的appSecret
        $Code = $_GET['code'];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appID."&secret=".$appSecret."&code=".$Code."&grant_type=authorization_code";
        $res = $this->get_by_curl($url);
        var_dump($res);
        die;
        return $res;
    }

 /**
     * snsapi_userinfo授权获取详细用户信息 入口
     */
    public function getWxForDetail()
    {
        //获取用户Code
        $appid = "wx5601175d5dd97b90";//自己微信公众号中的appid
        $redirect_uri = urlencode("https://cordxapi.baoyitong.com.cn/getWxDetail");//此处填写自己项目中的地址能访问到下面getWxDetail方法 （本方法加了路由处理）
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=1234#wechat_redirect";//state参数可随意填写
        $this->getWxDetail($url, 302);;//携带微信验证的code跳转到snsapi_userinfo授权获取用户基本信息 即下面的方法getWxDetail
    }
    /**
     * snsapi_userinfo授权获取详细用户信息
     * @return bool|string
     */
    public function getWxDetail()
    {
        $appID = "wx5601175d5dd97b90";//自己微信公众号中的appid
        $appSecret = "9c58ba855e8cc2cae279cf7997c3f54f";//自己微信公众号中的appSecret
        $Code = input('code');
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appID."&secret=".$appSecret."&code=".$Code."&grant_type=authorization_code";
        $res = $this->get_by_curl($url);
        
        echo apireturn(200,'success',$res);die;
        
        $access_token = $res['access_token'];
        $openId = $res['openid'];
        $url1 = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openId."&lang=zh_CN";
        $info = $this->get_by_curl($url1);
        var_dump($info);die;
        return $info;
    }



}