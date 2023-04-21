<?php
namespace app\api\controller;
use think\Db;
use think\facade\Request;
use think\facade\Env;
 
class Wxnofiy{
    
    //审批通知
    public function guestbook(){
        //此处模拟前端表单ajax提交
        $input_data = input();
        $title=$input_data['title'];
        $leixing=$input_data['leixing'];
        $neirong=$input_data['neirong'];
        $shijian=$input_data['shijian'];
        $uname=$input_data['uname'];

        if(isset($input_data) && !empty($input_data)){
            $set_up =  Db::name('setup')->where("id",1)->find();
            
            $openid=$input_data['openid'];
            
            if($input_data['type'] == 3){
                $pagepath = "/pages/approve-admin/user-approve/index?spNum=2";
                //提交成功，触发信息推送
                $data=[
                    'touser'=>$openid,
                    'template_id'=>'Si6XlS7PuOiU1chvNIUnx1bkudlVorWyLpLUDZpLihI',
                    "url"=>"",
                    "miniprogram"=>array(
                         "appid"=>"wx491d0319de706ff1",
                         "pagepath"=>$pagepath,
                    ),
                    'topcolor'=>"#FF0000",
                    'data'=>array(
                        'first'=>array('value'=>$title,'color'=>"#fc0101"),
                        'keyword1'=>array('value'=>$uname,'color'=>"#173177"),  
                        'keyword2'=>array('value'=>$leixing,'color'=>"#173177"), 
                        'keyword3'=>array('value'=>$neirong,'color'=>"#173177"), 
                        'keyword4'=>array('value'=>$shijian,'color'=>"#173177"),  
                        'remark'=>array('value'=>"请及时查看！",'color'=>"#173177"),
                    )
                ];
            }else{
                $pagepath = "/pages/approve-admin/user-approve/index?spNum=0";
                    //提交成功，触发信息推送
                $data=[
                    'touser'=>$openid,
                    'template_id'=>'4M0peMNdEK-zGPkXjcq5bDy_-kTCaxm6SJMGL9kvpqE',
                    "url"=>"",
                    "miniprogram"=>array(
                         "appid"=>"wx491d0319de706ff1",
                         "pagepath"=>$pagepath,
                    ),
                    'topcolor'=>"#FF0000",
                    'data'=>array(
                        'first'=>array('value'=>$title,'color'=>"#fc0101"),
                        'keyword1'=>array('value'=>$leixing,'color'=>"#173177"), 
                        'keyword2'=>array('value'=>$neirong,'color'=>"#173177"), 
                        'keyword3'=>array('value'=>$shijian,'color'=>"#173177"),  
                        'remark'=>array('value'=>"请及时处理！",'color'=>"#173177"),
                    )
                ];
            }
    
            $get_all_access_token = $this->get_all_access_token();


            $json_data=json_encode($data);//转化成json数组让微信可以接收
            $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$get_all_access_token;//模板消息请求URL

            $res=$this->https_request($url,urldecode($json_data));
            //请求开始
            $res=json_decode($res,true);
            var_dump($res);
            die;
            if($res['errcode']==0 && $res['errmsg']=="ok"){ 
            //发送成功    
                echo apireturn(200,'success','');die;
            }else{
                echo apireturn(201,'success','');die;
            }
            
        }
    }
    
    //审批结果通知
    public function tongzhi(){
        //此处模拟前端表单ajax提交
        $input_data = input();
        $uname=$input_data['uname'];
        $neirong=$input_data['neirong'];
        $shijian=$input_data['shijian'];

        if(isset($input_data) && !empty($input_data)){
            $set_up =  Db::name('setup')->where("id",1)->find();
            
            $openid=$input_data['openid'];
            
            $pagepath = "/pages/approve-admin/user-record/index";
            
            if($input_data['type'] == 2){
                $status = '审核通过';
            }else{
                $status = '审核拒绝';
            }
            //提交成功，触发信息推送
            $data=[
                'touser'=>$openid,
                'template_id'=>'aostDdQW4vhR8zLBYpjjuI_aaD8ufC9DpeBL_BXYSe8',
                "url"=>"",
                "miniprogram"=>array(
                     "appid"=>"wx491d0319de706ff1",
                     "pagepath"=>$pagepath,
                ),
                'topcolor'=>"#FF0000",
                'data'=>array(
                    'first'=>array('value'=>$uname.'，您好：','color'=>"#fc0101"),
                    'keyword1'=>array('value'=>$neirong,'color'=>"#173177"), 
                    'keyword2'=>array('value'=>$status,'color'=>"#173177"), 
                    'remark'=>array('value'=>$shijian,'color'=>"#173177"),
                )
            ];

            $get_all_access_token = $this->get_all_access_token();


            $json_data=json_encode($data);//转化成json数组让微信可以接收
            $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$get_all_access_token;//模板消息请求URL

            $res=$this->https_request($url,urldecode($json_data));
            //请求开始
            $res=json_decode($res,true);
            
            if($res['errcode']==0 && $res['errmsg']=="ok"){ 
            //发送成功    
                echo apireturn(200,'success','');die;
            }else{
                echo apireturn(201,'success','');die;
            }
            
        }
    }
    
    //审批结果通知
    public function yestday_notice($uname,$day,$openid){
      
        //此处模拟前端表单ajax提交
        if(isset($openid) && !empty($openid)){
            $set_up =  Db::name('setup')->where("id",1)->find();
            
            $pagepath = "/pages/checkLog-admin/checkCountInfo/index?month=".date('Y-m',time());
            
            //提交成功，触发信息推送
            $data=[
                'touser'=>$openid,
                'template_id'=>'EfdDyRZn4Q9Hk_VuVcxsataLwGEDVy9tYFOyhDVnLeQ',
                "url"=>"",
                "miniprogram"=>array(
                     "appid"=>"wx491d0319de706ff1",
                     "pagepath"=>$pagepath,
                ),
                'topcolor'=>"#FF0000",
                'data'=>array(
                    'first'=>array('value'=>'您有新的考勤异常，请及时处理！','color'=>"#fc0101"),
                    'keyword1'=>array('value'=>$uname,'color'=>"#173177"), 
                    'keyword2'=>array('value'=>$day,'color'=>"#173177"), 
                    'remark'=>array('value'=>'点击查看详情','color'=>"#173177"),
                )
            ];

            $get_all_access_token = $this->get_all_access_token();

            $json_data=json_encode($data);//转化成json数组让微信可以接收
            $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$get_all_access_token;//模板消息请求URL

            $this->https_request($url,urldecode($json_data));
            
        }
    }
    
    //审批结果通知
    public function month_notice($uname,$title,$neirong,$shijian,$openid){
      
        //此处模拟前端表单ajax提交
        if(isset($openid) && !empty($openid)){
            $set_up =  Db::name('setup')->where("id",1)->find();
            
            $pagepath = "/pages/checkLog-admin/checkCountInfo/index?month=".$shijian;
            
            //提交成功，触发信息推送
            $data=[
                'touser'=>$openid,
                'template_id'=>'YWbWYgbVp1_ZmyVLPDLVMj7ypPUmpCZoPX627GTdEW8',
                "url"=>"",
                "miniprogram"=>array(
                     "appid"=>"wx491d0319de706ff1",
                     "pagepath"=>$pagepath,
                ),
                'topcolor'=>"#FF0000",
                'data'=>array(
                    'first'=>array('value'=>$uname.'，您好：','color'=>"#fc0101"),
                    'keyword1'=>array('value'=>$title,'color'=>"#173177"), 
                    'keyword2'=>array('value'=>$neirong,'color'=>"#173177"), 
                    'remark'=>array('value'=>'点击查看详情','color'=>"#173177"),
                )
            ];

            $get_all_access_token = $this->get_all_access_token();


            $json_data=json_encode($data);//转化成json数组让微信可以接收
            $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$get_all_access_token;//模板消息请求URL

            $this->https_request($url,urldecode($json_data));
        }
    }
 
    public function https_request($url,$data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
 

 //微信access_token默认时间是7200s，设置每6000s获取一次并保存入库
    public function get_all_access_token(){
      $access_token_jilu = Db::name('setup')->where('id',1)->find();
       if(time()-$access_token_jilu['token_exp']>600){
            $appid = $access_token_jilu['appid'];
            $secret = $access_token_jilu['appsecret'];
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret.'&force_refresh=true';
            $res = $this->http_curl($url);
            
            $access_token = $res['access_token'];
            //session('uacc',$res);
            //$access_token =session('uacc.access_token');
            $update_data =[
                'token_exp' =>time(),
                'token'=>$access_token
            ];
            $update_data = Db::name('setup')->where('id',1)->update($update_data);
        }else{
             $access_token = $access_token_jilu['token'];
        }

        //halt($access_token);
            
        return $access_token;
    }


   //获取access_token的curl方法
    public function http_curl($url,$type='get',$res='json',$arr=''){
        //1.初始化curl
        $ch = curl_init();
        //2.设置curl的参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($type == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
        }
        //3.采集
        $output = curl_exec($ch);
        //4.关闭
        curl_close($ch);
        if ($res == 'json') {
            return json_decode($output,true);
        }
    }


}
