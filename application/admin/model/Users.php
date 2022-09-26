<?php
namespace app\admin\model;

class Users extends Base {
    
    public function checkLogin()
    {
        $username = input("post.username");
        $password = input("post.password");
        
        $result = $this->where(['mobile'=>$username])->find();
        
        if(empty($result)){
            echo apireturn(201,'帐号不存在','');die;
        }else{
            if($result['password'] != md5($password.'core2022')){
                echo apireturn(201,'密码错误','');die;
            }
           
            if($result['group_id'] > 0){
                
                if ($result['status']==1){
                    
                    $token = $this->MakeToken();
                   
                    //更新登录IP和登录时间
                    $this->where('id', $result['id'])->update(['last_login_time' => time(),'expires_time' => time()+7200,'last_login_ip'=>request()->ip(),'token' => $token]);
    
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
                    
                    echo apireturn(200,'登录成功',$admin);die;
                }else{
                    echo apireturn(201,'用户已被禁用','');die;
                }
                
            }else{
                echo apireturn(201,'用户不存在','');die;
                
            }

        }
    
        //登录成功

    }

    public function checkLogins()
    {
        $mobile = input("post.mobile");
        $password = input("post.password");

        $result = $this->where(['mobile'=>$mobile])->find();

        if(empty($result)){

            echo apireturn(201,'该账户不存在','');die;

        }else{

            if($result['password'] != md5($password.'core2022')){

                echo apireturn(201,'密码错误！','');die;

            }else{

                if ($result['status']==1){

                    $token = $this->MakeToken();

                    //更新登录IP和登录时间
                    $this->where('id', $result['id'])->update(['access_token' => $token]);

                    $rules = db('auth_group_access')
                        ->alias('a')
                        ->leftJoin('auth_group ag','a.group_id = ag.id')
                        ->field('a.group_id,ag.rules,ag.title')
                        ->where('uid',$result['id'])
                        ->find();

                    $uinfo = $this->where(['id'=>$result['id']])->find();

                    $admin['uinfo'] = $uinfo;
                    $admin['rules'] = explode(',',$rules['rules']);


                    echo apireturn(200,'登录成功',$admin);die;

                }else{

                    echo apireturn(201,'用户已被禁用！','');die;

                }

            }

        }
        //登录成功
    }

    public function checkLoginss()
    {
        $mobile = input("post.mobile");
        $username = input("post.username");

        $result = $this->where(['mobile'=>$mobile])->find();

        if(empty($result)){

            echo apireturn(201,'该账户不存在','');die;

        }else{

            if($username){

                $aa = mb_str_split($username);
                $bb = mb_str_split($result['username']);

                $cnum = 0;
                foreach ($aa as $key => $val){
                    if(in_array($val,$bb)){
                        $cnum = $cnum + 1;
                    }else{
                        $cnum = $cnum;
                    }
                }

                if($cnum == 0){

                    echo apireturn(201,'用户名不匹配','');die;

                }else{

                    if ($result['status']==1){

                        $token = $this->MakeToken();

                        //更新登录IP和登录时间
                        $this->where('id', $result['id'])->update(['access_token' => $token]);

                        $rules = db('auth_group_access')
                            ->alias('a')
                            ->leftJoin('auth_group ag','a.group_id = ag.id')
                            ->field('a.group_id,ag.rules,ag.title')
                            ->where('uid',$result['id'])
                            ->find();

                        $uinfo = $this->where(['id'=>$result['id']])->find();

                        $admin['uinfo'] = $uinfo;
                        $admin['rules'] = explode(',',$rules['rules']);


                        echo apireturn(200,'登录成功',$admin);die;

                    }else{

                        echo apireturn(201,'用户已被禁用！','');die;

                    }

                }

            }else{
                echo apireturn(201,'请输入用户名！','');die;
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