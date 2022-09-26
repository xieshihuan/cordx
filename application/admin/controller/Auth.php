<?php

namespace app\admin\controller;
use app\admin\model\Users;
use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\admin\model\AuthRule;
use think\Db;
use think\facade\Request;

class Auth extends Base
{
    /*-----------------------管理员管理----------------------*/
    
    //管理员列表
    public function adminList()
    {
        //条件筛选
        $username = Request::param('username');
        $group_id = Request::param('group_id');
        //全局查询条件
        $where=[];
        if( !empty($username) ){
            $where[]=['a.username|a.nickname', 'like', '%'.$username.'%'];
        }
        if( !empty($group_id) ){
            $where[]=['ac.group_id', '=', $group_id];
        }
        
        $where[]=['a.id', '<>', 1];
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        
        //查出所有数据
        $list = Db::name('users')
            ->alias('a')
            ->leftJoin('auth_group_access ac','a.id = ac.uid')
            ->leftJoin('auth_group ag','ac.group_id = ag.id')
            ->field('a.*,ac.group_id,ag.title')
            ->group('username')
            ->where($where)
            ->order('id asc')
            ->paginate($pageSize,false,['query' => request()->param()]);
        $page = $list->render();
        
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $list;
		return json_encode($rs_arr,true);
		exit;
        // $this->view->assign('page'  , $page);
        // $this->view->assign('list'  , $list);
        // $this->view->assign('empty' , empty_list(11));
        
        // return $this->view->fetch();
    }

    //管理员添加
    public function adminAdd(){
        if(Request::isPost()){
            $data = Request::post();
            if(empty($data['group_id'])){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = '请选择用户组';
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                $group_id = $data['group_id'];
                unset($data['group_id']);
            }
            $check_user = Users::where('username',$data['username'])->find();
            if ($check_user) {
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = '用户名已存在';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //验证
            $msg = $this->validate($data,'app\admin\validate\Users');
            if($msg!='true'){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = $msg;
        		return json_encode($rs_arr,true);
        		exit;
            }

            //单独验证密码
            if (empty($data['password'])) {
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = '密码不能为空';
        		return json_encode($rs_arr,true);
        		exit;
            }

            $data['password'] = md5(trim($data['password']));
            $data['logintime'] = time();
            $data['loginip'] = Request::ip();
            $data['status'] = $data['status'];
            //添加
            $result = Users::create($data);
            if($result){
                AuthGroupAccess::create([
                    'uid'  =>  $result->id,
                    'group_id' =>  $group_id
                ]);
                $rs_arr['status'] = 200;
        		$rs_arr['msg'] = '管理员添加成功';
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = '管理员添加失败';
        		return json_encode($rs_arr,true);
        		exit;
            }
        }
    }
    
    //管理员修改
    public function adminEdit(){
        if(Request::isPost()){
            $data = Request::post();
            $password=$data['password'];
            $where['id'] = $data['id'];

            if ($password){
                $data['password']=input('post.password','','md5');
            }else{
                unset($data['password']);
            }

            if(empty($data['group_id'])){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = '请选择用户组';
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                $group_id = $data['group_id'];
                unset($data['group_id']);
            }

            $msg = $this->validate($data,'app\admin\validate\Users');
            if($msg!='true'){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = $msg;
        		return json_encode($rs_arr,true);
        		exit;
            }
            Users::update($data,$where);
            AuthGroupAccess::update([
                'group_id' =>  $group_id
            ],['uid'=>$data['id']]);
            
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = '管理员修改成功';
    		return json_encode($rs_arr,true);
    		exit;

        }
        
    }
    
    //管理员密码修改
    public function adminReset(){
        if(Request::isPost()){
            $data = Request::post();
            $password=$data['password'];
            $where['id'] = $data['id'];

            if ($password){
                $data['password']=input('post.password','','md5');
            }else{
                unset($data['password']);
            }

            Users::update($data,$where);
            
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = '密码重置成功';
    		return json_encode($rs_arr,true);
    		exit;

        }
        
    }
    

    //管理员删除
    public function adminDel(){
        $id = Request::post('id');
        if ($id >1){
            Users::where('id','=',$id)->delete();
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = '删除成功';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = '超级管理员不可删除';
    		return json_encode($rs_arr,true);
    		exit;
        }
    }

    //管理员批量删除
    public function adminSelectDel(){
        $id = Request::post('id');
        if($id){
            $ids = explode(',',$id);
        }
        if(in_array('1',$ids)){
            return $result = ['error'=>1,'msg'=>'超级管理员不可删除!'];
        }
        Users::destroy($id);
        return $result = ['error'=>0,'msg'=>'删除成功!'];
    }

    //管理员状态修改
    public function adminState(){
        if(Request::isPost()){
            $id = Request::post('id');
            if (empty($id)){
                return ['error'=>1,'msg'=>'用户ID不存在!'];
            }
            if ($id==1){
                return ['error'=>1,'msg'=>'超级管理员不可修改状态!'];
            }

            $admin = Users::get($id);
            $status = $admin['status']==1?0:1;
            $admin->status = $status;
            $admin->save();
            return ['error'=>0,'msg'=>'修改成功!'];
        }
    }


    /*-----------------------角色组管理----------------------*/

    //用户组管理
    public function adminGroup(){
        //条件筛选
        $title = Request::param('title');
        $this->view->assign('title',$title);
        //全局查询条件
        $where=[];
        if($title){
            $where[]=['title', 'like', '%'.$title.'%'];
        }
        $where[] = ['id','>',1];
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $this->view->assign('pageSize', page_size($pageSize));

        //查出所有数据
        $list = AuthGroup::where($where)->paginate($pageSize,false,['query' => request()->param()]);
        $page = $list->render();
        
        $data_rt['status'] = 200;
        $data_rt['msg'] = '获取成功';
        $data_rt['data'] = $list;
        return json_encode($data_rt,true);
        die;
        
        
        // $this->view->assign('page' , $page);
        // $this->view->assign('list' ,$list);
        // $this->view->assign('empty', empty_list(7));
        // return $this->view->fetch();
    }
    
    //用户组管理
    public function adminGroups(){
        //条件筛选
        $title = Request::param('title');
        $this->view->assign('title',$title);
        //全局查询条件
        $where=[];
        if($title){
            $where[]=['title', 'like', '%'.$title.'%'];
        }
        $where[] = ['id','>',1];
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $this->view->assign('pageSize', page_size($pageSize));

        //查出所有数据
        $list = AuthGroup::where($where)->paginate($pageSize,false,['query' => request()->param()]);
        $page = $list->render();
        
        $data_rt['status'] = 200;
        $data_rt['msg'] = '获取成功';
        $data_rt['data'] = $list;
        return json_encode($data_rt,true);
        die;
        
        
        // $this->view->assign('page' , $page);
        // $this->view->assign('list' ,$list);
        // $this->view->assign('empty', empty_list(7));
        // return $this->view->fetch();
    }

    //用户组删除
    public function groupDel(){
        $id = Request::post('id');
        if($id){
            AuthGroup::where('id','=',$id)
                ->delete();
            $data_rt['status'] = 200;
            $data_rt['msg'] = '删除成功';
            return json_encode($data_rt,true);
            die;
        }else{
            $data_rt['status'] = 200;
            $data_rt['msg'] = '删除失败';
            return json_encode($data_rt,true);
            die;
        }

    }

    //用户组添加
    public function groupAdd(){
        if(Request::isPost()){
            $data = Request::post();
            if(!$data['title']){
                $data_rt['status'] = 500;
                $data_rt['msg'] = '用户组不能为空';
                return json_encode($data_rt,true);
                die;
            }
            if(AuthGroup::create($data)){
                $data_rt['status'] = 200;
                $data_rt['msg'] = '用户组添加成功';
                return json_encode($data_rt,true);
                die;
            }else{
                $data_rt['status'] = 500;
                $data_rt['msg'] = '用户组添加失败';
                return json_encode($data_rt,true);
                die;
            }
        }else{
            $data_rt['status'] = 404;
            $data_rt['msg'] = 'faild';
            return json_encode($data_rt,true);
            die;
        }
    }

    //用户组修改
    public function groupEdit(){
        if(request()->isPost()) {
            $data=Request::post();
            //防止重复
            if($data['title']){
                $map[] = ['id','<>',$data['id']];
                $map[] = ['title','=',$data['title']];
                $check_title = AuthGroup::where($map)->find();
                if ($check_title) {
                    $data_rt['status'] = 90003;
                    $data_rt['msg'] = '用户组名重复';
                    return json_encode($data_rt,true);
                    die;
                }
            }else{
                $data_rt['status'] = 90003;
                $data_rt['msg'] = '用户组名不能为空';
                return json_encode($data_rt,true);
                die;
            }

            $where['id'] = $data['id'];
            AuthGroup::update($data,$where);
            $data_rt['status'] = 200;
            $data_rt['msg'] = '管理员修改成功';
            return json_encode($data_rt,true);
            die;
        }
    }

    //用户组状态修改
    public function groupState(){
        if(Request::isPost()){
            $id = Request::post('id');
            if (empty($id)){
                return ['error'=>1,'msg'=>'ID不存在'];
            }

            $info = AuthGroup::get($id);
            $status = $info['status']==1?0:1;
            $info->status = $status;
            $info->save();
            return ['error'=>0,'msg'=>'修改成功!'];
        }
    }

    //用户组批量删除
    // public function groupSelectDel(){
    //     $id = Request::post('id');
    //     AuthGroup::destroy($id);
    //     return ['error'=>0,'msg'=>'删除成功!'];
    // }

    //用户组显示权限
    public function groupAccess(){
        $zrules = auths();
        
        $rules = Db::name('auth_group')
            ->where('id',Request::param('id'))
            ->value('rules');
            
        $list['zrules'] = $zrules;
        $list['checkIds'] = $rules;
        
        $data_rt['status'] = 200;
        $data_rt['msg'] = '获取成功';
        $data_rt['data'] = $list;
        return json_encode($data_rt,true);
        die;
           
    }

    //用户组保存权限
    public function groupSetaccess(){
        $rules = Request::post('rules');
        if(empty($rules)){
            $data_rt['status'] = 500;
            $data_rt['msg'] = '请选择权限';
            return json_encode($data_rt,true);
            die;
        }
        $data = Request::post();
        $where['id'] = $data['id'];
        if(AuthGroup::update($data,$where)){
            $data_rt['status'] = 200;
            $data_rt['msg'] = '权限配置成功';
            return json_encode($data_rt,true);
            die;
        }else{
            $data_rt['status'] = 500;
            $data_rt['msg'] = '保存错误';
            return json_encode($data_rt,true);
            die;
        }
    }

    /********************************权限管理*******************************/

    //权限列表
    public function adminRule(){
        
        $pid = 0;
        $list = Db::name('auth_rule')->where(['pid'=>$pid])->order('sort asc')->select();
        
        foreach ($list as $key => $val){
            
            $num = Db::name('auth_rule')->where(['pid'=>$val['id']])->count();
            if($num > 0){
                $list[$key]['children'] = self::get_trees($val['id']);
            }else{
                $list[$key]['children'] = '';
            }
            
        }
        
        $data_rt['status'] = 200;
        $data_rt['msg'] = '获取成功';
        $data_rt['data'] = $list;
        return json_encode($data_rt);
        exit;
        
    }
    
    public function get_trees($pid = 0){
      
        $list = Db::name('auth_rule')->where(['pid'=>$pid])->order('sort asc')->select();
        
        foreach ($list as $key => $val){
            
            $num = Db::name('auth_rule')->where(['pid'=>$val['id']])->count();
            
            if($num > 0){
                $list[$key]['children'] = self::get_trees($val['id']);
            }else{
                $list[$key]['children'] = '';
            }
            
        }
        
        return $list;
    }

    //权限列表_old
    // public function adminRules(){
    //     $list = Db::name('auth_rule')->order('sort ASC')->select();
    //     $list = tree($list);
        
    //     $data_rt['status'] = 200;
    //     $data_rt['msg'] = '获取成功';
    //     $data_rt['data'] = $list;
    //     return json_encode($data_rt);
    //     exit;
    // }

    //权限菜单显示或者隐藏
    // public function ruleState(){
    //     if(Request::isPost()){
    //         $id = Request::post('id');
    //         if (empty($id)){
    //             return ['error'=>1,'msg'=>'ID不存在'];
    //         }

    //         $info = AuthRule::get($id);
    //         $status = $info['status']==1?0:1;
    //         $info->status = $status;
    //         $info->save();

    //         return ['error'=>0,'msg'=>'修改成功'];
    //     }
    // }

    //设置权限是否验证
    // public function ruleOpen(){
    //     if(Request::isPost()){
    //         $id = Request::post('id');
    //         if (empty($id)){
    //             return ['error'=>1,'msg'=>'ID不存在'];
    //         }

    //         $info = AuthRule::get($id);
    //         $auth_open = $info['auth_open']==1?0:1;
    //         $info->auth_open = $auth_open;
    //         $info->save();

    //         return ['error'=>0,'msg'=>'修改成功'];
    //     }
    // }

    //设置权限排序
    // public function ruleSort(){
    //     if(Request::isPost()){
    //         $id = Request::post('id');
    //         $sort = Request::post('sort');
    //         if (empty($id)){
    //             return ['error'=>1,'msg'=>'ID不存在'];
    //         }

    //         $info = AuthRule::get($id);
    //         $info->sort = $sort;
    //         $info->save();

    //         return ['error'=>0,'msg'=>'修改成功'];
    //     }
    // }

    //权限删除
    public function ruleDel()
    {
        $id = Request::post('id');
      
        if ($id) {
            AuthRule::where('id', '=', $id)->delete();
            
            $data_rt['status'] = 200;
            $data_rt['msg'] = '删除成功';
            return json_encode($data_rt,true);
        }
    }

    //权限批量删除
    // public function ruleSelectDel(){
    //     $id=Request::post('id');
    //     if($id){
    //         AuthRule::destroy($id);
    //         return ['error'=>0,'msg'=>'删除成功'];
    //     }

    // }

    //权限增加
    public function ruleAdd(){
        if(Request::isPost()){
            $data=Request::post();
            
            // $count = Db::name('auth_rule')
            //     ->where(['name'=>$data['name']])
            //     ->count();
                
            // if($count > 0){
                
            //     $data_rt['status'] = 90003;
            //     $data_rt['msg'] = '重复';
            //     return json_encode($data_rt,true);
                
            // }else{
                
                if(AuthRule::create($data)){
                    
                    $data_rt['status'] = 200;
                    $data_rt['msg'] = '权限添加成功';
                    return json_encode($data_rt,true);
                }else{
                    $data_rt['status'] = 500;
                    $data_rt['msg'] = '权限添加失败';
                    return json_encode($data_rt,true);
                }
                
            //}
        }
    }

    //权限修改
    public function ruleEdit(){
        if(request()->isPost()) {
            $data=Request::post();
            $where['id'] = $data['id'];
            
            
            $list = Db::query("select * from `tp_auth_rule` where `id` !=".$data['id']." and `name` = "."'".$data['name']."'");
            
            // $count = count($list);
            
            
            // if($count > 0){
                
            //     $data_rt['status'] = 90003;
            //     $data_rt['msg'] = '重复';
            //     return json_encode($data_rt,true);
                
            // }else{
                    
                if(AuthRule::update($data,$where)){
                    $data_rt['status'] = 200;
                    $data_rt['msg'] = '权限修改成功';
                    return json_encode($data_rt,true);
                }else{
                    $data_rt['status'] = 500;
                    $data_rt['msg'] = '权限修改失败';
                    return json_encode($data_rt,true);
                }
                
            //}
        }
        
    }


}
