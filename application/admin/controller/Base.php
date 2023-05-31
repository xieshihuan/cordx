<?php

namespace app\admin\controller;
use think\Controller;
use think\facade\Hook;
use think\facade\Request;
use think\facade\Session;
use think\Db;

class Base extends Controller
{
    
    protected $admin_id = NULL,$encryption = null;
	
    //初始化方法
    public function initialize()
    {
    
        header('Content-Type: text/html;charset=utf-8');
    	header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
    	header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE'); // 允许请求的类型
		
		$token=\request()->header('token');    //从header里获取token
		
		//检查token
	    if($token){
	        
            $admin = $this->CheckToken($token);
            if($admin['status'] != 200){
                echo json_encode($admin,true);
                die;
            }else{
                
                //定义方法白名单
                $allow = [
                    'Index/index',      //首页左侧导航
                    'Index/three',      //获取三级导航
                    'Index/main',       //右侧
                    'Index/upload',     //上传文件
                    'Index/upload_train',     //上传多文件
                    'Index/uploads',     //上传多文件
                    'Index/uploads_del',     //上传多文件
                    'Index/wangEditor', //编辑器
                    'Index/ckeditor',   //编辑器
                    'Index/clear',      //清除缓存
                    'Index/logout',     //退出登录
                    'Index/resetpass',  //重置密码
                    'Index/detail',
                    'Login/index',      //登录页面
                    'Login/checkLogin', //校验登录
                    'Login/captcha',    //登录验证码
                    'Category/indexs',    //登录验证码
                    'Auth/admingroups',    //登录验证码
                    'Daxuetang/index',    //登录验证码
                    'Daxuetang/organize',
                    'Daxuetang/setorganize',
                    'Daxuetang/setrestrict',
                    'Daxuetang/member',
                    'Daxuetang/select_name',
                    'Daxuetang/setmember',
                    'Users/getlist',
                    'Tikus/index',
                    'Tikus/is_kaohe',
                    'Cateuser/check',
                    'Users/indexs',
                    'Users/detail',
                    'TrainApply/apply_exam',
                    'Template/index',
                    'Template/addpost',
                    'Template/editpost',
                    'Template/del',
                    'TrainCate/index',
                    'SpecItem/addpost',
                    'Aptitude/company_list',
                    'Aptitude/user_list',
                    'ProductCate/index',
                    'ProductType/index',
                    'Spec/index',
                    'AttendanceGroup/organize',
                    'AttendanceGroup/setorganize',
                    'AttendanceGroup/setrestrict',
                    'AttendanceGroup/member',
                    'AttendanceGroup/select_name',
                    'AttendanceGroup/setmember',
                    'AttendanceGroup/organize_status',
                    'AttendanceGroup/index',
                    'Users/index',
                    
                ];
        
                //查找当前控制器和方法，控制器首字母大写，方法首字母小写 如：Index/index
                $route = Request::controller() . '/' . lcfirst(Request::action());
              
                //权限认证
                if(!in_array($route, $allow)){
                    
                    if($admin['data']!=1){
                        //开始认证
                        $auth = new \Auth\Auth();
                     
                        $result = $auth->check($route,$admin['data']);
                        if(!$result){
                            $data_rt['status']= 501;
                			$data_rt['msg']='您无此操作权限';
                			echo json_encode($data_rt,true);
	                        die;
                        }
                    }
                }
            }
            
	    }else{
	        
	        $data_rt['status'] = 99901;
			$data_rt['msg'] = '请先登录';
			echo json_encode($data_rt,true);
	        die;
	    }
	    
        //Hook::listen("admin_log");
        Hook::listen("admin_log",$admin['data']);
    }
    
    /**
	 * 检查token
	 */
	public function CheckToken($token){
	    
		if($token){
			$res = Db::name('users')
				->field('id,username,mobile')
				->where(['token'=>$token])
				->find();
			
			if ($res){
				$this->admin_id = $res['id'];
				$rs_arr['status'] = 200;
				$rs_arr['msg']='验证通过';
				$rs_arr['data']=$res['id'];
				return $rs_arr;
				die;
			}else{
				$rs_arr['status'] = 99901;
				$rs_arr['msg']= '登录已过期，请重新登录';
				return $rs_arr;die;
			}
			
		}else{
			$rs_arr['status']=99901;
			$rs_arr['msg']='请先登录';
			return $rs_arr;die;
		}
		
	}

    //空操作
    public function _empty(){
        if(Request::isAjax()){
            $rs_arr['status']=201;
			$rs_arr['msg']='操作方法为空';
			return $rs_arr;
			die;
        }else{
            $rs_arr['status']=201;
			$rs_arr['msg']='操作方法为空';
			return $rs_arr;
			die;
        }
    }
}
