<?php

namespace app\api\controller;
use think\Controller;
use think\facade\Hook;
use think\facade\Request;
use think\facade\Session;
use think\Db;

class Base extends Controller
{
    
    protected $user_id = NULL,$encryption = null;
	
    //初始化方法
    public function initialize()
    {
    
        header('Content-Type: text/html;charset=utf-8');
    	header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
    	header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE'); // 允许请求的类型
		
		$token=\request()->header('accessToken');    //从header里获取token
		
		//检查token
	    if($token){
	        
            $admin = $this->CheckToken($token);
            if($admin['status'] != 200){
                echo json_encode($admin,true);
                die;
            }
            
	    }else{
	        
	        $data_rt['status'] = 99901;
			$data_rt['msg'] = '请先登录';
			echo json_encode($data_rt,true);
	        die;
	    }
	    
    }
    
    /**
	 * 检查token
	 */
	public function CheckToken($token){
	    
		if($token){
			$res = Db::name('users')
				->field('id,username,country,mobile,status')
				->where(['access_token'=>$token])
				->find();
			
			if ($res){
				if($res['status'] == 1){
                    $this->user_id = $res['id'];
                    $this->country = $res['country'];

                    $rs_arr['status'] = 200;
                    $rs_arr['msg']='验证通过';
                    $rs_arr['data']=$res['id'];
                    return $rs_arr;
                    die;
                }else{
                    $rs_arr['status'] = 99901;
                    $rs_arr['msg']= '您的账户已被禁用，请联系管理员';
                    return $rs_arr;
                    die;
                }
			}else{
				$rs_arr['status'] = 99901;
				$rs_arr['msg']= '登录信息不存在';
				return $rs_arr;
				die;
			}
			
		}else{
			$rs_arr['status'] = 99901;
			$rs_arr['msg']='请先登录';
			return $rs_arr;
			die;
		}
		
	}

    //空操作
    public function _empty(){
        if(Request::isAjax()){
            $rs_arr['status'] = 201;
			$rs_arr['msg']='操作方法为空';
			return $rs_arr;
			die;
        }else{
            $rs_arr['status'] = 201;
			$rs_arr['msg']='操作方法为空';
			return $rs_arr;
			die;
        }
    }
}
