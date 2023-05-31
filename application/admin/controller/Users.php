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
        $aguid = Request::param('aguid');
        $role_id = Request::param('role_id');
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        
        $whr1=[];

        $uinfo = Db::name('users')->where('id',$this->admin_id)->find();
        $rules = $uinfo['ruless'].','.$uinfo['period_ruless'].','.$uinfo['train_ruless'];
        
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

        }else{
            
            
            $whr1[] = ['id','in',$rules];
            $uids = Db::name('cateuser')
                ->where($whr1)
                ->field('uid')
                ->select();

            $a = '';
            foreach($uids as $key => $val){
                $a .= $val['uid'].',';
            }
            
            $where[]=['u.id', '>', $a];
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
        
        if(!empty($aguid)){
            $where['agu.attendance_group_id'] = $aguid;
        }
        
        if(!empty($role_id)){
            $where['u.role_id'] = $role_id;
        }
        
        $where[]=['u.is_delete', '=', '1'];
       
        $where[]=['u.id', 'neq', 1];
        
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : config('page');
        
        $a = $pageSize*($page-1);
        
        $count = Db::name('users')
            ->alias('u')
            ->leftJoin('auth_group ag','ag.id = u.group_id')
            ->leftJoin('cateuser cu','cu.uid = u.id')
            ->leftJoin('cate c','c.id = u.id')
            ->leftJoin('attendance_group_user agu','u.id = agu.uid')
            ->field('u.*,ag.title as group_name,agu.attendance_group_id')
            ->order('u.id ASC')
            ->group('u.id')
            ->where($where)
            ->count();
        
        //调取列表
        $list = Db::name('users')
            ->alias('u')
            ->leftJoin('auth_group ag','u.group_id = ag.id')
            ->leftJoin('attendance_group_user agu','u.id = agu.uid')
            ->field('u.*,ag.title as group_name,agu.attendance_group_id')
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
            
            $whrkq['id'] = $val['attendance_group_id'];
            $kaoqin_name = Db::name('attendance_group')
            ->where($whrkq)
            ->value('title');
            
            $list[$key]['kaoqin_name'] = $kaoqin_name;
        }
          
        $rlist['count'] = $count;
        $rlist['data'] = $list;
          
         
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $rlist;
		return json_encode($rs_arr,true);
		exit;
    }
    //用户列表
    public function index(){
        //条件筛选
        $keyword = Request::param('keyword');
        $catid = Request::param('catid');
        $catids = Request::param('catids');
        $groupId = Request::param('group_id');
        $did = Request::param('did');
        $aid = Request::param('aid');
        $groupIds = Request::param('group_ids');
        $group_device = Request::param('group_device');
        $period = Request::param('period');
        $aguid = Request::param('attendance_group_id');
        $role_id = Request::param('role_id');
        $is_kaohes = Request::param('is_kaohes');
        
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        
        $whr1=[];

        $uinfo = Db::name('users')->where('id',$this->admin_id)->find();
        
        $rules = '';
        //trim($uinfo['ruless'].','.$uinfo['period_ruless'].','.$uinfo['train_ruless']);
        if(!empty($uinfo['ruless'])){
            $rules .= ','.$uinfo['ruless'];
        }
        if(!empty($uinfo['period_ruless'])){
            $rules .= ','.$uinfo['period_ruless'];
        }
        if(!empty($uinfo['train_ruless'])){
            $rules .= ','.$uinfo['train_ruless'];
        }
        
        $rules = trim($rules,',');
        if($uinfo['id'] > 1){
               
            if(!empty($catid)){
                
                if(in_array($catid,explode(',',$rules))){
                    $whr1[]=['catid', '=', $catid];
                    
                    $uuid = Db::name('cateuser')->field('uid')->where($whr1)->buildSql(true);
                    
                }else{
                    $rs_arr['status'] = 200;
                    $rs_arr['msg'] = '无权限';
                    $rs_arr['data'] = array();
                    return json_encode($rs_arr,true);
                    exit;
                }
    
            }else{
                
                $whr1[] = ['catid','in',$rules];
                
                $uuid = Db::name('cateuser')->field('uid')->where($whr1)->buildSql(true);
            }
         
        }else{
            
            if(!empty($catid)){
                
                $whr1[]=['catid', '=', $catid];

                $uuid= Db::name('cateuser')->field('uid')->where($whr1)->buildSql(true);
    
            }else{
                $where[]=['u.id', '>', 0];
                
                $uuid= Db::name('users')->field('id')->where($where)->buildSql(true);
            }
            
            
        }
        
        $whrq=[];
        if(!empty($catids)){
            $whrq['catid'] = $catids;
        }
        if(!empty($groupId)){
            $where[]=['u.group_id', '=', $groupId];
        }
        if(!empty($groupIds)){
            $where[]=['u.group_ids', '=', $groupIds];
        }
        if(!empty($group_device)){
            $where[]=['u.group_device', '=', $group_device];
        }
        if(!empty($period)){
            $where[]=['u.period', '=', $period];
        }
        if(!empty($role_id)){
            $where[] = ['u.role_id','=',$role_id];
        }
        if(!empty($is_kaohes)){
            $where[] = ['u.is_kaohes','=',$is_kaohes];
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
        
        $members1 = array();
        if($aid){
            $member1 = Db::name('attendance_group')
                ->where('id',$aid)
                ->value('member');
            if($member1){
                $members1 = explode(',',$member1);
            }else{
                $members1 = array();
            }
        }
        
        if(!empty($aguid)){
            $where[] = ['agu.attendance_group_id','=',$aguid];
        }
        
        
        $where[]=['u.id', 'neq', '1'];
        $where[]=['u.is_delete', 'eq', '1'];
        
       
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : config('page');
        
        $a = $pageSize*($page-1);
        
        $count = Db::name('users')
            ->alias('u')
            ->leftJoin('auth_group ag','ag.id = u.group_id')
            ->leftJoin('cateuser cu','cu.uid = u.id')
            ->leftJoin('cate c','c.id = u.id')
            ->leftJoin('attendance_group_user agu','agu.uid = u.id')
            ->field('u.*,ag.title as group_name,agu.attendance_group_id as aguid')
            ->order('u.id ASC')
            ->group('u.id')
            ->where($where)
            ->where('u.id','exp','In '.$uuid)
            ->count();
        
        //调取列表
        $list = Db::name('users')
            ->alias('u')
            ->leftJoin('auth_group ag','u.group_id = ag.id')
            ->leftJoin('attendance_group_user agu','agu.uid = u.id')
            ->field('u.*,ag.title as group_name,agu.attendance_group_id')
            ->order('u.id ASC')
            ->limit($a.','.$pageSize)
            ->group('u.id')
            ->where($where)
            ->where('u.id','exp','In '.$uuid)
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
            
            if(in_array($val['id'],$members1)){
                $list[$key]['is_kaoqin'] = 1;
            }else{
                $list[$key]['is_kaoqin'] = 0;
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
            
            $whrkq['id'] = $val['attendance_group_id'];
            $kaoqin_name = Db::name('attendance_group')
            ->where($whrkq)
            ->value('title');
            
            $list[$key]['kaoqin_name'] = $kaoqin_name;
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
    
    
    //查看用户完成情况
    public function detail(){
        
        $id = Request::param('id');
        
        $uinfo = Db::name('users')->where('id',$id)->find();
        $periods = '0,'.$uinfo['period'];
        
        $wherep[] = ['period','in',$periods];
        
        $train_num = 0;
        $where = [];
        $where[] = ['parentid','>',0];
        $where[] = ['is_delete','=',1];
        $list = Db::name('train_cate')->where($where)->order('parentid asc')->select();
        foreach($list as $key => $val){
            $list[$key]['topname'] = Db::name('train_cate')->where('id',$val['parentid'])->value('title');
            $list[$key]['apply_status'] = Db::name('train_apply')->where('catid',$val['id'])->where('uid',$id)->value('status');
            
            $photo_list = Db::name('train_image')->where('uid',$id)->where('catid',$val['id'])->select();
            foreach ($photo_list as $keys => $vals){
                $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                $photo_list[$keys]['url'] = $http_type.$_SERVER['HTTP_HOST'].$vals['url'];
            }
            
            $list[$key]['photo_list'] = $photo_list;
            
            $list_two = Db::name('train_cate')->where($wherep)->where('parentid',$val['parentid'])->where('status',1)->order('sort asc')->select();
            
            if($uinfo['period'] == 1){
                if(count($list_two) > 0){
                    $wnum = 0;
                    $dshnum = 0;
                    $wspnum = 0;
                    foreach($list_two as $keys => $vals){
                        $apply_status = Db::name('train_apply')->where('catid',$vals['id'])->where('uid',$id)->value('status');
                        $list_two[$keys]['apply_status'] = $apply_status;
                        if($apply_status == 2){
                            $wnum++;
                        }
                        if($apply_status == 1){
                            $dshnum++;
                        }
                        //查模版类型
                        
                        $module_type = Db::name('template')->where('id',$vals['module_id'])->value('type');
                        $list_two[$keys]['module_type'] = $module_type;
                        $templist = Db::name('template')->where('parentid',$vals['module_id'])->select();
                        $list_two[$keys]['module_num'] = count($templist);
                        
                        if(count($templist) > 0 && $module_type == 4){
                            foreach($templist as $keyss => $valss){
                                if(Db::name('train_status')->where('template_id',$valss['id'])->where('uid',$id)->value('status') != 2){
                                    $wspnum++;                              
                                }
                            }
                        }
                        
                    }
                    //$list[$key]['znum'] = count($list_two);
                    //$list[$key]['wnum'] = $wnum;
                    
                    //进度状态
                    $list[$key]['scale'] = intval(($wnum/count($list_two))*100);
                    
                    if($wnum > 0){
                        if($wnum < count($list_two)){
                            $list[$key]['wstatus'] = 2;
                            if(count($list_two) - $wnum != $dshnum){
                                if($wspnum == 0){
                                    $list[$key]['submit_status'] = 1; 
                                }else{
                                    $list[$key]['submit_status'] = 2; 
                                }                                 
                            }else{
                                $list[$key]['submit_status'] = 2;     
                            }
                            $train_num++;
                        }else{
                            $tiku_id = Db::name('train_cate')->where('id',$val['parentid'])->value('tiku_id');
                            //查询随机抽考成绩
                            $whrz1['uid'] = $id;
                            $whrz1['tid'] = $tiku_id;
                            $whrz1['catid'] = $val['parentid'];
                            if($tiku_id > 0 && $val['is_exam'] == 1){
            
                                $traininfo1 = Db::name('traintest')->where($whrz1)->find();
                                
                                if($traininfo1){
                                    if($traininfo1['cnum'] == 0 && $traininfo1['status'] == 2){
                                        $list[$key]['wstatus'] = 3;
                                    }else{
                                        $list[$key]['wstatus'] = 4;
                                    }
                                }else{
                                    $list[$key]['wstatus'] = 4;
                                }
                                
                            }else{
                                $list[$key]['wstatus'] = 3;
                            }
                            
                            $list[$key]['submit_status'] = 2;     
                        }
                    }else{
                        if(count($list_two) > 0){
                            $list[$key]['wstatus'] = 1;
                            if($wspnum == 0){
                                $list[$key]['submit_status'] = 1; 
                            }else{
                                $list[$key]['submit_status'] = 2; 
                            }  
                            $train_num++;
                        }else{
                            $list[$key]['wstatus'] = 1;
                            $list[$key]['submit_status'] = 2;  
                        }
                    }
                    //$list[$key]['clist'] = $list_two;
                    
                }else{
                    
                    foreach($list_two as $keys => $vals){
                        //查模版类型
                        $list_two[$keys]['module_type'] = Db::name('template')->where('id',$vals['module_id'])->value('type');
                        $list_two[$keys]['module_num'] = Db::name('template')->where('parentid',$vals['module_id'])->count();
                        $list_two[$keys]['apply_status'] = 2;
                        
                    }
                    //$list[$key]['znum'] = 0;
                    //$list[$key]['wnum'] = 0;
                    $list[$key]['scale'] = 0;
                    //$list[$key]['clist'] = $list_two;
                    $list[$key]['wstatus'] = 3;
                    $list[$key]['submit_status'] = 2; 
                             
                }
                
            }else{
                foreach($list_two as $keys => $vals){
                    //查模版类型
                    $list_two[$keys]['module_type'] = Db::name('template')->where('id',$vals['module_id'])->value('type');
                    $list_two[$keys]['module_num'] = Db::name('template')->where('parentid',$vals['module_id'])->count();
                    $list_two[$keys]['apply_status'] = 2;
                }
                //$list[$key]['znum'] = count($list_two);
                //$list[$key]['wnum'] = count($list_two);
                $list[$key]['scale'] = 100;
                //$list[$key]['clist'] = $list_two;
                $list[$key]['submit_status'] = 2; 
                $list[$key]['wstatus'] = 3; 
                $list[$key]['apply_status'] = 2;
            }
            
            
        }
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $list;
		return json_encode($rs_arr,true);
		exit;
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
                
                $data['mobile'] = trim($data['mobile']);
                
                //查询是否存在
                $count = Db::name('users')->where('mobile',$data['mobile'])->where('is_delete',1)->count();
                if($count > 0){
                    $rs_arr['status'] = 201;
            		$rs_arr['msg'] ='用户已存在';
            		return json_encode($rs_arr,true);
            		exit;
                }
                
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
                
                $data['mobile'] = trim($data['mobile']);
                
                //查询是否存在
                $count = Db::name('users')->where('id','neq',$data['id'])->where('mobile',$data['mobile'])->where('is_delete',1)->count();
                if($count > 0){
                    $rs_arr['status'] = 201;
            		$rs_arr['msg'] ='用户已存在';
            		return json_encode($rs_arr,true);
            		exit;
                }
                
                unset($data['password']);
                
                $m = new M();
                $data['access_token'] = '';
                $data['token'] = '';
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
                    Db::name('cateuser')->where($whr)->delete();
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
        
        $type = Request::post('type');
        
        $zrules = authss();
        
        if($type == 1){
            $rules = Db::name('users')
            ->where('id',Request::param('id'))
            ->value('ruless');
        }elseif($type == 2){
            $rules = Db::name('users')
            ->where('id',Request::param('id'))
            ->value('period_ruless');
        }elseif($type == 3){
            $rules = Db::name('users')
            ->where('id',Request::param('id'))
            ->value('train_ruless');
        }else{
            $rules = Db::name('users')
            ->where('id',Request::param('id'))
            ->value('check_ruless');
        }
            
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
        $ruless = Request::post('ruless');
        $type = Request::post('type');
        $id = Request::post('id');
        
        if(empty($type)){
            $data_rt['status'] = 201;
            $data_rt['msg'] = '请选择授权类型';
            return json_encode($data_rt,true);
            die;
        }
        
        //if(!empty($rules)){
            if($type == 1){
                $data['rules'] = $rules;
                $data['ruless'] = $ruless;
                $where['id'] = $id;
            
                if(M::update($data,$where)){
                    $data_rt['status'] = 200;
                    $data_rt['code'] = 200;
                    $data_rt['msg'] = '站点配置成功';
                    return json_encode($data_rt,true);
                    die;
                }else{
                    $data_rt['status'] = 200;
                    $data_rt['code'] = 201;
                    $data_rt['msg'] = '保存错误';
                    return json_encode($data_rt,true);
                    die;
                }
            }elseif($type == 2){
                $data['period_rules'] = $rules;
                $data['period_ruless'] = $ruless;
                $where['id'] = $id;
                
                if(M::update($data,$where)){
                    $data_rt['status'] = 200;
                    $data_rt['code'] = 200;
                    $data_rt['msg'] = '站点配置成功';
                    return json_encode($data_rt,true);
                    die;
                }else{
                    $data_rt['status'] = 200;
                    $data_rt['code'] = 201;
                    $data_rt['msg'] = '保存错误';
                    return json_encode($data_rt,true);
                    die;
                }
          
            }elseif($type == 3){
                $data['train_rules'] = $rules;
                $data['train_ruless'] = $ruless;
                $where['id'] = $id;
                
                if(M::update($data,$where)){
                    $data_rt['status'] = 200;
                    $data_rt['code'] = 200;
                    $data_rt['msg'] = '站点配置成功';
                    return json_encode($data_rt,true);
                    die;
                }else{
                    $data_rt['status'] = 200;
                    $data_rt['code'] = 201;
                    $data_rt['msg'] = '保存错误';
                    return json_encode($data_rt,true);
                    die;
                }
            }else{
                $data['check_rules'] = $rules;
                $data['check_ruless'] = $ruless;
                $where['id'] = $id;
                
                if(M::update($data,$where)){
                    $data_rt['status'] = 200;
                    $data_rt['code'] = 200;
                    $data_rt['msg'] = '站点配置成功';
                    return json_encode($data_rt,true);
                    die;
                }else{
                    $data_rt['status'] = 200;
                    $data_rt['code'] = 201;
                    $data_rt['msg'] = '保存错误';
                    return json_encode($data_rt,true);
                    die;
                }
            }
            

        // }else{
        //     $data_rt['status'] = 200;
        //     $data_rt['code'] = 201;
        //     $data_rt['msg'] = '请选择站点';
        //     return json_encode($data_rt,true);
        //     die;
        // }
        
        
    }

    //获取用户组织列表
    public function getlist(){
        
        $type = Request::param('type');
        
        $whr['id'] = $this->admin_id;
        $uinfo = Db::name('users')->where($whr)->find();
        
        if($this->admin_id == 1){
            $whra[] = ['id','>',0];
            $list= Db::name('cate')->where($whra)->select();
        }else{
            if($type == 1){
                $rules = trim($uinfo['ruless'], ',');
            }else if($type == 2){
                $rules = trim($uinfo['period_ruless'], ',');
            }else if($type == 3){
                $rules = trim($uinfo['train_ruless'], ',');
            }else{
                $rules = $uinfo['ruless'].','.$uinfo['period_ruless'].','.$uinfo['train_ruless'];
            }
            $whra[] = ['id','in',$rules];
            $list= Db::name('cate')->where($whra)->select();
        }
        $data_rt['status'] = 200;
        $data_rt['msg'] = '获取成功';
        $data_rt['data'] = $list;
        return json_encode($data_rt,true);
        die;

    }



}
