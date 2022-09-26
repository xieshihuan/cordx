<?php
/**
 * +----------------------------------------------------------------------
 * | 会员列表控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use app\common\model\UsersType;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\Users as M;

use PHPExcel_IOFactory;
use PHPExcel;

class Users extends Base
{
    protected $validate = 'Users';
    
    //列表
    public function indexs(){
        //条件筛选
        $keyword = Request::param('keyword');
        $catid = Request::param('catid');
        $catids = Request::param('catids');
        $groupId = Request::param('group_id');
        $did = Request::param('did');

        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        
        $whr1=[];

        $uinfo = Db::name('users')->where('id',$this->admin_id)->find();
        
        if(!empty($catid)){
            $whr1[]=['catid', '=', $catid];

            $uids = Db::name('cateuser')
                ->where($whr1)
                ->field('uid')
                ->select();

            $a = '';
            foreach($uids as $key => $val){
                $a .= $val['uid'].',';
            }
            $where[]=['u.id', 'in', $a];

        }
        
        $whrq=[];
        if(!empty($catids)){
            $whrq['catid'] = $catids;
        }
        if(!empty($groupId)){
            $where[]=['u.group_id', '=', $groupId];
        }
        $members = array();
        if($did){
            $member = Db::name('daxuetang')
                ->where('id',$did)
                ->value('member');
            if($member){
                $members = explode(',',$member);
            }else{
                $members = array();
            }
        }
        $where[]=['u.id', '>', '1'];
        $where[]=['u.is_delete', '=', '1'];
       
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : config('page');
        
        $a = $pageSize*($page-1);
        
        $count = Db::name('users')
            ->alias('u')
            ->leftJoin('auth_group ag','ag.id = u.group_id')
            ->leftJoin('cateuser cu','cu.uid = u.id')
            ->leftJoin('cate c','c.id = u.id')
            ->field('u.*,ag.title as group_name')
            ->order('u.id ASC')
            ->group('u.id')
            ->where($where)
            ->count();
        
        //调取列表
        $list = Db::name('users')
            ->alias('u')
            ->leftJoin('auth_group ag','u.group_id = ag.id')
            ->field('u.*,ag.title as group_name')
            ->order('u.id ASC')
            ->limit($a.','.$pageSize)
            ->group('u.id')
            ->where($where)
            ->select();
        

        foreach ($list as $key => $val){
            $whrq['uid'] = $val['id'];
            $whrq['leixing'] = 1;
            $is_cunzai = Db::name('cateuser')
            ->where($whrq)
            ->count();
            if($is_cunzai > 0){
                $list[$key]['is_cunzai'] = 1;
            }else{
                $list[$key]['is_cunzai'] = 0;
            }
            if(in_array($val['id'],$members)){
                $list[$key]['is_kaohe'] = 1;
            }else{
                $list[$key]['is_kaohe'] = 0;
            }

            $whra['uid'] = $val['id'];
            $whra['leixing'] = 1;
            $clist = Db::name('cateuser')
            ->where($whra)
            ->select();
            foreach ($clist as $keys => $vals){
                $group_name = self::select_name($vals['catid']);
                $arr = explode('/',$group_name);
                $arrs = array_reverse($arr);
                $group_list = implode('/',$arrs);
                $group_list = ltrim($group_list,'/');
                $clist[$keys]['group_name'] = $group_list;
            } 
            
            $list[$key]['clist'] = $clist;
        }
          
        $rlist['count'] = $count;
        $rlist['data'] = $list;
          
         
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $rlist;
		return json_encode($rs_arr,true);
		exit;
    }
    //列表
    public function index(){
        //条件筛选
        $keyword = Request::param('keyword');
        $catid = Request::param('catid');
        $catids = Request::param('catids');
        $groupId = Request::param('group_id');
        $did = Request::param('did');

        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        
        $whr1=[];

        $uinfo = Db::name('users')->where('id',$this->admin_id)->find();
        if($uinfo['group_id'] == 1 || $uinfo['group_id'] == 2 || $uinfo['group_id'] == 7){
            if(!empty($catid)){
                $whr1[]=['catid', '=', $catid];

                $uids = Db::name('cateuser')
                    ->where($whr1)
                    ->field('uid')
                    ->select();

                $a = '';
                foreach($uids as $key => $val){
                    $a .= $val['uid'].',';
                }
                $where[]=['u.id', 'in', $a];

            }
        }else{
            if(!empty($catid)){
                if(in_array($catid,explode(',',$uinfo['ruless']))){
                    $whr1[]=['catid', '=', $catid];

                    $uids = Db::name('cateuser')
                        ->where($whr1)
                        ->field('uid')
                        ->select();

                    $a = '';
                    foreach($uids as $key => $val){
                        $a .= $val['uid'].',';
                    }
                    $where[]=['u.id', 'in', $a];
                }else{
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = '无权限';
                    return json_encode($rs_arr,true);
                    exit;
                }

            }else{
                $whr1[] = ['catid','in',$uinfo['ruless']];
                $uids = Db::name('cateuser')
                    ->where($whr1)
                    ->field('uid')
                    ->select();

                $a = '';
                foreach($uids as $key => $val){
                    $a .= $val['uid'].',';
                }
                $where[]=['u.id', 'in', $a];
            }
        }
        
        $whrq=[];
        if(!empty($catids)){
            $whrq['catid'] = $catids;
        }
        if(!empty($groupId)){
            $where[]=['u.group_id', '=', $groupId];
        }
        $members = array();
        if($did){
            $member = Db::name('daxuetang')
                ->where('id',$did)
                ->value('member');
            if($member){
                $members = explode(',',$member);
            }else{
                $members = array();
            }
        }
        $where[]=['u.id', '>', '1'];
        $where[]=['u.is_delete', '=', '1'];
       
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : config('page');
        
        $a = $pageSize*($page-1);
        
        $count = Db::name('users')
            ->alias('u')
            ->leftJoin('auth_group ag','ag.id = u.group_id')
            ->leftJoin('cateuser cu','cu.uid = u.id')
            ->leftJoin('cate c','c.id = u.id')
            ->field('u.*,ag.title as group_name')
            ->order('u.id ASC')
            ->group('u.id')
            ->where($where)
            ->count();
        
        //调取列表
        $list = Db::name('users')
            ->alias('u')
            ->leftJoin('auth_group ag','u.group_id = ag.id')
            ->field('u.*,ag.title as group_name')
            ->order('u.id ASC')
            ->limit($a.','.$pageSize)
            ->group('u.id')
            ->where($where)
            ->select();
        

        foreach ($list as $key => $val){
            $whrq['uid'] = $val['id'];
            $whrq['leixing'] = 1;
            $is_cunzai = Db::name('cateuser')
            ->where($whrq)
            ->count();
            if($is_cunzai > 0){
                $list[$key]['is_cunzai'] = 1;
            }else{
                $list[$key]['is_cunzai'] = 0;
            }
            if(in_array($val['id'],$members)){
                $list[$key]['is_kaohe'] = 1;
            }else{
                $list[$key]['is_kaohe'] = 0;
            }

            $whra['uid'] = $val['id'];
            $whra['leixing'] = 1;
            $clist = Db::name('cateuser')
            ->where($whra)
            ->select();
            foreach ($clist as $keys => $vals){
                $group_name = self::select_name($vals['catid']);
                $arr = explode('/',$group_name);
                $arrs = array_reverse($arr);
                $group_list = implode('/',$arrs);
                $group_list = ltrim($group_list,'/');
                $clist[$keys]['group_name'] = $group_list;
            } 
            
            $list[$key]['clist'] = $clist;
        }
          
        $rlist['count'] = $count;
        $rlist['data'] = $list;
          
         
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $rlist;
		return json_encode($rs_arr,true);
		exit;
    }
    
    public function select_name($id){
        $str = '';
        $whr['id'] = $id;
        $info = Db::name('cate')->where($whr)->find();
        $str .= $info['title'].'/';
        
        if($id != 1){
            $str .= self::select_name($info['parentid']);
        }
        
        return $str;
    }
    
 
    //添加保存
    public function addPost(){
        if(Request::isPost()) {
            $data = Request::param();
            $result = $this->validate($data,$this->validate);
            if (true !== $result) {
                // 验证失败 输出错误信息
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = $result;
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                $data['last_login_time'] = time();
                $data['create_ip'] = $data['last_login_ip'] = Request::ip();
                
                $phone = $data['mobile'];
                $country = $data['country'];
                $code = 123456;
                $data['password'] = md5($code.'core2022');
                
                $m = new M();
                $result =  $m->create($data);
                
                if($result){
                    
                    $dataz['uid'] = $result['id'];
                    $dataz['group_id'] = $result['group_id'];
                    $dataz['create_time'] = time();
                    $dataz['update_time'] = time();
                    $results = Db::name('auth_group_access')->insert($dataz);
                    
                    if($results){
                         //发送用户短信（密码）
                        if($country == 86){
                    	    $time = '5分钟';
                            //$res = saiyouSms($phone,$code,$time);
                        }else{
                            //$res = YzxSms($code,'00'.$country.$phone);
                        }
                    
                        $rs_arr['status'] = 200;
        		        $rs_arr['msg'] = '添加成功';
                		return json_encode($rs_arr,true);
                		exit;
                    }else{
                        $rs_arr['status'] = 500;
                		$rs_arr['msg'] ='权限组添加失败';
                		return json_encode($rs_arr,true);
                		exit;
                    }
                    
                    
                    
                }else{
                    $rs_arr['status'] = 500;
            		$rs_arr['msg'] = $result['msg'];
            		return json_encode($rs_arr,true);
            		exit;
                }
            }
        }
    }

    //修改保存
    public function editPost(){

        if(Request::isPost()) {

            $data = Request::param();
            $result = $this->validate($data,$this->validate);

            if (true !== $result) {
                // 验证失败 输出错误信息
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = $result;
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                
                unset($data['password']);
                
                $m = new M();
                $result =  $m->update($data);

                if($result){
                    
                    $whrz['uid'] = $result['id'];
                    $info = Db::name('auth_group_access')->where($whrz)->find();
                    if($info){
                        $dataz['group_id'] = $result['group_id'];
                        $dataz['update_time'] = time();
                        Db::name('auth_group_access')->where($whrz)->update($dataz);
                    }else{
                        $datazz['uid'] = $result['id'];
                        $datazz['group_id'] = $result['group_id'];
                        $datazz['create_time'] = time();
                        $datazz['update_time'] = time();
                        Db::name('auth_group_access')->insert($datazz);
                    }

                    $rs_arr['status'] = 200;
        	        $rs_arr['msg'] = '修改成功';
            		return json_encode($rs_arr,true);
            		exit;
                    
                }else{
                    $rs_arr['status'] = 500;
            		$rs_arr['msg'] = '修改失败';
            		return json_encode($rs_arr,true);
            		exit;
                }
            }
        }
    }
    
    //重置密码
    public function resetPassword(){
        if(Request::isPost()) {
            $data = Request::param();
            
            if(empty($data['id']) ){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = 'ID不存在';
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                $whr['id'] = $data['id'];
                $uinfo = Db::name('users')->where($whr)->find();
            }
            
            $phone = $uinfo['mobile'];
            $country = $uinfo['country'];
            $code = 123456;
            $data['password'] = md5($code.'core2022');
            
            
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
            
        }
    }
    
     //重置密码发短信
    public function resetPassword——sms(){
        if(Request::isPost()) {
            $data = Request::param();
            
            if(empty($data['id']) ){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = 'ID不存在';
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                $whr['id'] = $data['id'];
                $uinfo = Db::name('users')->where($whr)->find();
            }
            
            $phone = $uinfo['mobile'];
            $country = $uinfo['country'];
            $code = rand(100000,999999);
            $data['password'] = md5($code.'core2022');
            
            
            $m = new M();
            $result =  $m->editPost($data);
            if($result['error']){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = $result['msg'];
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                if($country == 86){
            	    $time = '5分钟';
                    $res = saiyouSms($phone,$code,$time);
                }else{
                    $res = YzxSms($code,'00'.$country.$phone);
                }
                    
                $rs_arr['status'] = 200;
    	        $rs_arr['msg'] = $result['msg'];
        		return json_encode($rs_arr,true);
        		exit;
            }
            
        }
    }

     //删除
    public function del(){
        if(Request::isPost()) {
            $id = Request::post('id');
            if(empty($id) ){
                $rs_arr['status'] = 500;
    	        $rs_arr['msg'] ='ID不存在';
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                if($id == 1){
                    $rs_arr['status'] = 500;
        	        $rs_arr['msg'] ='该账号不可删除';
            		return json_encode($rs_arr,true);
            		exit;
                }else{
                    $whr['uid'] = $id;
                    $data['status'] = 2;
                    Db::name('cateuser')->where($whr)->update($data);
                }
            }
            
            $whrs['id'] = $id;
            $datas['is_delete'] = 2;
            Db::name('users')->where($whrs)->update($datas);
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] ='success';
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    //批量删除
    public function selectDel(){
        if(Request::isPost()) {
            $id = Request::post('id');
            if (empty($id)) {
                $rs_arr['status'] = 500;
    	        $rs_arr['msg'] ='ID不存在';
        		return json_encode($rs_arr,true);
        		exit;
            }
            // $m = new M();
            // $m->selectDel($id);
            
            if(empty($id) ){
                $rs_arr['status'] = 500;
    	        $rs_arr['msg'] ='ID不存在';
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                $list = explode(',',$id);
                
                foreach ($list as $key => $val){
                    if($val == 1){
                        $rs_arr['status'] = 500;
            	        $rs_arr['msg'] ='该账号不可删除';
                		return json_encode($rs_arr,true);
                		exit;
                    }else{
                        $whr['uid'] = $val;
                        $data['status'] = 2;
                        Db::name('cateuser')->where($whr)->update($data);
                                
                        $whrs['id'] = $val;
                        $datas['is_delete'] = 2;
                        Db::name('users')->where($whrs)->update($datas);
                    }
                    
                }
            }
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] ='success';
    		return json_encode($rs_arr,true);
    		exit;
        }

    }



    //用户组显示权限
    public function groupAccess(){
        $zrules = authss();
        
        $rules = Db::name('users')
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
        if(!empty($rules)){
            $data = Request::post();
            $where['id'] = $data['id'];
            $group_id = Db::name('users')->where($where)->value('group_id');
            if($group_id>0){
                if(M::update($data,$where)){
                    $data_rt['status'] = 200;
                    $data_rt['msg'] = '站点配置成功';
                    return json_encode($data_rt,true);
                    die;
                }else{
                    $data_rt['status'] = 201;
                    $data_rt['msg'] = '保存错误';
                    return json_encode($data_rt,true);
                    die;
                }
            }else{
                $data_rt['status'] = 201;
                $data_rt['msg'] = '普通用户无法授权';
                return json_encode($data_rt,true);
                die;
            }

        }else{
            $data_rt['status'] = 201;
            $data_rt['msg'] = '请选择站点';
            return json_encode($data_rt,true);
            die;
        }
        
    }

    //获取用户组织列表
    public function getlist(){
        $whr['id'] = $this->admin_id;
        $gid = Db::name('users')->where($whr)->value('group_id');
        if($gid == 3){
            $ruless = Db::name('users')->where($whr)->value('ruless');
            $whra[] = ['id','in',$ruless];
            $list= Db::name('cate')->where($whra)->select();
            $data_rt['status'] = 200;
            $data_rt['msg'] = '获取成功';
            $data_rt['data'] = $list;
            return json_encode($data_rt,true);
            die;
        }else{
            $data_rt['status'] = 201;
            $data_rt['msg'] = 'not access';
            return json_encode($data_rt,true);
            die;
        }


    }



}
