<?php
namespace app\api\model;

class Admin extends Base {
    
    public function checkLogin()
    {
        $username = input("post.username");
        $password = input("post.password");
        
        // $code = input("post.vercode");
        // if(!captcha_check($code))
        // {
        //     $data = ['status' => '0', 'msg' => '验证码错误'];
        //     return json($data);
        // }
    
        $result = $this->where(['username|nickname'=>$username,'password'=>md5($password)])->find();
        if(empty($result)){
            $data = ['status' => '0', 'msg' => '帐号或密码错误'];
            return json($data);
        }else{
            if ($result['status']==1){
                
                $token = $this->MakeToken();
               
                //更新登录IP和登录时间
                $this->where('id', $result['id'])->update(['logintime' => time(),'expires_time' => time()+7200,'loginip'=>request()->ip(),'token' => $token]);

                $rules = db('auth_group_access')
                    ->alias('a')
                    ->leftJoin('auth_group ag','a.group_id = ag.id')
                    ->field('a.group_id,ag.rules,ag.title')
                    ->where('uid',$result['id'])
                    ->find();
                    
                $uinfo = $this->where(['id'=>$result['id']])->find();
                
                $admin['uinfo'] = $uinfo;
                $admin['group_id'] = $rules['group_id'];
                $admin['rules'] = explode(',',$rules['rules']);
                
                $data = ['status' => '200', 'msg' => '登录成功', 'data'=>$admin];
                return json($data);
            }else{
                return ['status' => 0, 'msg' => '用户已被禁用!'];
            }

        }
        //登录成功

    }
    
    //创建token
	static public function MakeToken(){
		$str = md5(uniqid(md5(microtime(true)), true)); //创建唯一token
		$str = sha1($str);
		return $str;
	}
}