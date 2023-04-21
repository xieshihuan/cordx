<?php
namespace app\admin\controller;
use think\Db;
use think\facade\Request;

use app\common\model\RegisterCredential as RC;
use app\common\model\Brand as B;
use app\common\model\Other as O;
use app\common\model\BrandWorld as BW;


class Aptitude extends Base
{
    protected $validate = 'Aptitude';
    
    //获取公司列表
    public function company_list(){
        
        $leixing = Request::param('leixing');
        
        $list = Db::name('company')->where('leixing',$leixing)->order('sort asc')->select();
    
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $list;
		return json_encode($rs_arr,true);
		exit;
    
    }
    
    //获取操作记录
    public function aptitude_log(){
        $keyword = Request::param('keyword');
        $type_id = Request::param('type_id');
        $aptitude_type = Request::param('aptitude_type');
        $start = Request::param('start');
        $end = Request::param('end');
        $start = strtotime(date($start));
        $end = strtotime(date($end));
        
        $where=[];
        if(!empty($keyword)){
            $where[]=['u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        if(!empty($type_id)){
            $where[]=['au.type_id', '=', $type_id];
        }else{
            $group_id = Db::name('users')->where('id',$this->admin_id)->value('group_id');
            if($group_id == 6){
                $where[]=['au.type_id', 'in', '1,4'];
            }elseif($group_id == 7){
                $where[]=['au.type_id', 'in', '2,3'];
            }elseif($group_id == 8){
                $where[]=['au.type_id', 'in', '1,3'];
            }else{
                $where[] = ['au.type_id','>',0];
            }
        }
        if(!empty($aptitude_type)){
            $where[]=['au.aptitude_type', '=', $aptitude_type];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end=="")
        {
            $where[] = ['au.aptitude_time','>=',$start];
        }
        if(isset($end)&&$end!=""&&isset($start)&&$start=="")
        {
            $where[] = ['au.aptitude_time','<=',$end];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end!="")
        {
            $where[] = ['au.aptitude_time','between',[$start,$end]];
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        $list = Db::name('aptitude_update')
            ->alias('au')
            ->leftJoin('users u','au.aptitude_uid = u.id')
            ->field('au.*,u.username as username')
            ->order('au.aptitude_time desc')
            ->where($where)
            ->select();
            
        foreach($list as $key => $val){
            if($val['type_id'] == 1){
                $list[$key]['type_name'] = '医疗器械注册证';
            }else if($val['type_id'] == 2){
                $list[$key]['type_name'] = '国内商标';
            }else if($val['type_id'] == 3){
                $list[$key]['type_name'] = '其他资质';
            }else{
                $list[$key]['type_name'] = '国际资质';
            }
            if($val['aptitude_type']  == 1){
                $list[$key]['aptitude_type_name'] = '新增';
            }else if($val['aptitude_type']  == 2){
                $list[$key]['aptitude_type_name'] = '编辑';
            }else{
                $list[$key]['aptitude_type_name'] = '删除';
            }
            $list[$key]['aptitude_content'] = explode('^',$val['aptitude_content']);
        }
            
        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;
        //调取列表
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
		return json_encode($rs_arr,true);
		exit;
    
    }
    
    //获取短信记录
    public function sms_log(){
        $keyword = Request::param('keyword');
        $type_id = Request::param('type_id');
        $remind_type = Request::param('remind_type');
        $status = Request::param('status');
        $start = Request::param('start');
        $end = Request::param('end');
        
        $where=[];
        if(!empty($keyword)){
            $where[]=['bianhao|name|phone', 'like', '%'.$keyword.'%'];
        }
        if(!empty($type_id)){
            $where[]=['type_id', '=', $type_id];
        }else{
            $group_id = Db::name('users')->where('id',$this->admin_id)->value('group_id');
            if($group_id == 6){
                $where[]=['type_id', 'in', '1,4'];
            }elseif($group_id == 7){
                $where[]=['type_id', 'in', '2,3'];
            }elseif($group_id == 8){
                $where[]=['type_id', 'in', '1,3'];
            }else{
                $where[] = ['type_id','>',0];
            }
        }
        if(!empty($remind_type)){
            $where[]=['remind_type', '=', $remind_type];
        }
        if(!empty($status)){
            $where[]=['status', '=', $status];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end=="")
        {
            $where[] = ['remind_time','>=',$start];
        }
        if(isset($end)&&$end!=""&&isset($start)&&$start=="")
        {
            $where[] = ['remind_time','<=',$end];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end!="")
        {
            $where[] = ['remind_time','between',[$start,$end]];
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        $list = Db::name('remind')
            ->order('remind_time asc,id asc')
            ->where($where)
            ->select();
            
        foreach($list as $key => $val){
            if($val['type_id'] == 1){
                $list[$key]['type_name'] = '医疗器械注册证';
            }else if($val['type_id'] == 2){
                $list[$key]['type_name'] = '国内商标';
            }else if($val['type_id'] == 3){
                $list[$key]['type_name'] = '其他资质';
            }else{
                $list[$key]['type_name'] = '国际商标';
            }
            if($val['remind_type']  == 1){
                $list[$key]['remind_typename'] = '临期提醒';
            }else{
                $list[$key]['remind_typename'] = '过期提醒';
            }
            if($val['status']  == 1){
                $list[$key]['status_name'] = '待发送';
            }elseif($val['status']  == 2){
                $list[$key]['status_name'] = '发送成功';
            }else{
                $list[$key]['status_name'] = '发送失败';
            }
            $list[$key]['phone'] = explode('^',$val['phone']);
        }
            
        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;
        //调取列表
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
		return json_encode($rs_arr,true);
		exit;
    
    }
    
    //获取负责人列表
    public function user_list(){
        
        $group_id = Request::param('group_id');
        
        $where = [];
        $where[]=['','EXP',Db::raw("FIND_IN_SET($group_id,admin_rule)")];
        
        //$where[]=['group_id', 'in', $group_id];
        
        $list = Db::name('users')->field('id,username')->where($where)->order('username asc')->select();
        if(count($list) > 0){
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = 'success';
    		$rs_arr['data'] = $list;
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = 'filed';
    		$rs_arr['data'] = array();
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    //注册证列表
    public function register_list(){

        //条件筛选
        $keyword = Request::param('keyword');
        $name = Request::param('name');
        $leibie = Request::param('leibie');
        $datetype = Request::param('datetype');
        $start = Request::param('start');
        $end = Request::param('end');
        $status = Request::param('status');
        //全局查询条件
        $where=[];
        

        if(!empty($keyword)){
            $where[]=['rc.bianhao|rc.principal_name|rc.product_name', 'like', '%'.$keyword.'%'];
        }
        if(!empty($name)){
            $where[]=['rc.name', 'like', '%'.$name.'%'];
        }
        if(!empty($leibie)){
            $where[]=['rc.leibie', '=', $leibie];
        }
         
        if(!empty($datetype)){
            if(isset($start)&&$start!=""&&isset($end)&&$end=="")
            {
                $where[] = ['rc.'.$datetype,'>=',$start];
            }
            if(isset($end)&&$end!=""&&isset($start)&&$start=="")
            {
                $where[] = ['rc.'.$datetype,'<=',$end];
            }
            if(isset($start)&&$start!=""&&isset($end)&&$end!="")
            {
                $where[] = ['rc.'.$datetype,'between',[$start,$end]];
            }
        }
        
        $where[] = ['rc.is_delete','=',1];

        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        $list = Db::name('register_credential')
            ->alias('rc')
            ->leftJoin('users u','rc.principal = u.id')
            ->field('rc.*,u.username as username')
            ->order('rc.end_time asc,rc.validity_time asc')
            ->where($where)
            ->select();
            
        foreach($list as $key => $val){
            $time = date('Y-m-d',time());
            //根据日期类型去判断天数
            if($val['reminder_type'] == 1){
                $reminderday = $val['reminder_time'];
            }elseif($val['reminder_type'] == 2){
                $reminderday = $val['reminder_time']*30;
            }else{
                $reminderday = $val['reminder_time']*365;
            }
                    
            if($val['end_time'] != '0000-00-00'){
                if($val['end_time'] < $time){
                    $list[$key]['status'] = 1;
                    $list[$key]['number'] = -intval((strtotime($time)-strtotime($val['end_time']))/86400);
                }else{
                    $remind_time = strtotime($val['end_time'])-($reminderday*86400);
                    if($remind_time < time()){
                        $list[$key]['status'] = 2;
                        $list[$key]['number'] = intval((time()-$remind_time)/86400);
                    }else{
                        $list[$key]['status'] = 3;
                        $list[$key]['number'] = intval(($remind_time-time())/86400);
                    }
                }
            }else{
                if($val['validity_time'] < $time){
                    $list[$key]['status'] = 1;
                    $list[$key]['number'] = -intval((strtotime($time)-strtotime($val['validity_time']))/86400);
                }else{
                    $remind_time = strtotime($val['validity_time'])-($reminderday*86400);
                    if($remind_time < time()){
                        $list[$key]['status'] = 2;
                        $list[$key]['number'] = intval((time()-$remind_time)/86400);
                    }else{
                        $list[$key]['status'] = 3;
                        $list[$key]['number'] = intval(($remind_time-time())/86400);
                    }
                }
            }
            
            $list[$key]['exceed_phone'] = explode('^',$val['exceed_phone']);
           
            
        }
        
        usort($list, array('\app\admin\model\Aptitude','cmp_sequence_score'));
        
        if(!empty($status)){
            $list = seacharr_by_value($list,'status',$status);
        }
        
        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;
        //调取列表
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
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
    
    //注册证添加
    public function register_add(){
        $data = Request::param();
        if(empty($data['name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册人';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['leibie'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择注册证类别';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['bianhao'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册证编号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['principal'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择资质负责人';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['approval_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入批准日期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['validity_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入有效期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '临期提醒天数';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择临期提醒时间类型';
    		return json_encode($rs_arr,true);
    		exit;
        } if(empty($data['reminder_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入临期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['exceed_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入过期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        }
        if(empty($data['exceed_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入过期提醒次数';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['exceed_phone'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入提醒手机号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_home'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册人住所';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_address'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册人生产地址';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入产品名称';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_names'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入产品简称';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_specification'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入规格型号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入样本类型';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_ingredient'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入主要组成成分';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_purpose'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入预期用途';
    		return json_encode($rs_arr,true);
    		exit;
        }
        
        if(empty($data['product_information'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入产品储存条件及有效期';
    		return json_encode($rs_arr,true);
    		exit;
        }
        
        $principal_name = Db::name('users')->where('id',$data['principal'])->value('username');
        $data['principal_name'] = $principal_name;
        
        $RC = new RC();
        $result =  $RC->addPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $zzid = Db::name('register_credential')->order('id desc')->limit(1)->value('id');
            //判断结束日期
            if(!empty($data['end_time'])){
                $endtime = $data['end_time'];
            }else{
                $endtime = $data['validity_time'];
            }
            //根据日期类型去判断天数
            if($data['reminder_type'] == 1){
                $reminderday = $data['reminder_time'];
            }elseif($data['reminder_type'] == 2){
                $reminderday = $data['reminder_time']*30;
            }else{
                $reminderday = $data['reminder_time']*365;
            }
            //判断频率是否合理
            if($reminderday < $data['reminder_rate']){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '临期提醒频率不得大于过期时间';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //计算提醒日期
            $remind_time = strtotime($endtime)-($reminderday*86400);
            $remind_day = date('Y-m-d',$remind_time);
            $remind_rate = $data['reminder_rate']*86400;
            $end_time = strtotime($endtime);
            //循环添加提醒记录
            while($remind_time < $end_time)
            {
                if($remind_time > time()){
                    
                    $snum = intval(($end_time - $remind_time)/86400);
                    $ins['type_id'] = 1;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = 'register'.$zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-医疗器械注册证-'.$data['bianhao'].'-剩余'.$snum.'天过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_time);
                    $ins['remind_type'] = 1;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
                
                $remind_time = $remind_time + $remind_rate;
            }
            
            //循环添加过期记录
            for($i = 1;$i <= $data['exceed_num'];$i++){
                $remind_endtime = $end_time + ($i*$data['exceed_rate']*86400);
                if($remind_endtime > time()){
                    $ins['type_id'] = 1;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = 'register'.$zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-医疗器械注册证-'.$data['bianhao'].'-已过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_endtime);
                    $ins['remind_type'] = 2;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
            }
            
            //增加操作记录
            aptitudelog($type_id = 1, $zzid, $aptitude_type = 1, $aptitude_content = '新增该数据', $aptitude_uid = $this->admin_id);

            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    //注册证修改
    public function register_upd(){
        $data = Request::param();
        $reminder_type = $data['reminder_type'];
       
        if(empty($data['id'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入id';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $info = Db::name('register_credential')->where('id',$data['id'])->find();
        }
        
        $aptitude_content = '';
        if(empty($data['name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['name'] != $info['name']){
                $aptitude_content = $aptitude_content.'注册证类别：（原内容） '.$info['name'].' （新内容） '.$data['name'].'^';
            }
        }
        
        if(empty($data['leibie'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择注册证类别';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['leibie'] != $info['leibie']){
                $aptitude_content = $aptitude_content.'注册证类别：（原内容） '.$info['leibie'].' （新内容） '.$data['leibie'].'^';
            }
        }
        
        if(empty($data['bianhao'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册证编号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['bianhao'] != $info['bianhao']){
                $aptitude_content = $aptitude_content.'注册证编号：（原内容） '.$info['bianhao'].' （新内容） '.$data['bianhao'].'^';
            }
        }
        
        if(empty($data['principal'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择资质负责人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['principal'] != $info['principal']){
                $old = Db::name('users')->where('id',$info['principal'])->value('username');
                $new = Db::name('users')->where('id',$data['principal'])->value('username');
                $aptitude_content = $aptitude_content.'资质负责人：（原内容） '.$old.' （新内容） '.$new.'^';
            }
        } 
        if(empty($data['approval_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入批准日期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['approval_time'] != $info['approval_time']){
                $aptitude_content = $aptitude_content.'批准日期：（原内容） '.$info['approval_time'].' （新内容） '.$data['approval_time'].'^';
            }
        } 
        if(empty($data['validity_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入有效期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['validity_time'] != $info['validity_time']){
                $aptitude_content = $aptitude_content.'有效期：（原内容） '.$info['validity_time'].' （新内容） '.$data['validity_time'].'^';
            }
        } 
        
        if(empty($data['submit_time'])){
            $data['submit_time'] = '0000-00-00';
        }
        if($data['submit_time'] != $info['submit_time']){
            $aptitude_content = $aptitude_content.'提交延续日：（原内容） '.$info['submit_time'].' （新内容） '.$data['submit_time'].'^';
        }
        if(empty($data['continue_time'])){
            $data['continue_time'] = '0000-00-00';
        }
        if($data['continue_time'] != $info['continue_time']){
            $aptitude_content = $aptitude_content.'延续批准日：（原内容） '.$info['continue_time'].' （新内容） '.$data['continue_time'].'^';
        }
        if(empty($data['effective_time'])){
            $data['effective_time'] = '0000-00-00';
        }
        if($data['effective_time'] != $info['effective_time']){
            $aptitude_content = $aptitude_content.'生效日期：（原内容） '.$info['effective_time'].' （新内容） '.$data['effective_time'].'^';
        }
        if(empty($data['end_time'])){
            $data['end_time'] = '0000-00-00';
        }
        if($data['end_time'] != $info['end_time']){
            $aptitude_content = $aptitude_content.'延续效期至：（原内容） '.$info['validity_time'].' （新内容） '.$data['validity_time'].'^';
        }
        
        if(empty($data['reminder_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '临期提醒天数';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_time'] != $info['reminder_time']){
                $aptitude_content = $aptitude_content.'临期提醒天数：（原内容） '.$info['reminder_time'].' （新内容） '.$data['reminder_time'].'^';
            }
        } 
        
        
        if(empty($reminder_type)){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择临期提醒时间类型';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($reminder_type != $info['reminder_type']){
                if($info['reminder_type'] == 1){
                    $old = '日';
                }elseif($info['reminder_type'] == 2){
                    $old = '月';
                }else{
                    $old = '年';
                }
                if($reminder_type == 1){
                    $new = '日';
                }elseif($reminder_type == 2){
                    $new = '月';
                }else{
                    $new = '年';
                }
                $aptitude_content = $aptitude_content.'临期提醒时间类型：（原内容） '.$old.' （新内容） '.$new.'^';
            }
        }
        if(empty($data['reminder_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入临期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_rate'] != $info['reminder_rate']){
                $aptitude_content = $aptitude_content.'临期提醒频率：（原内容） '.$info['reminder_rate'].'天/次 （新内容） '.$data['reminder_rate'].'天/次^';
            }
        }
        if(empty($data['exceed_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '过期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_rate'] != $info['exceed_rate']){
                $aptitude_content = $aptitude_content.'过期提醒频率：（原内容） '.$info['exceed_rate'].'天/次 （新内容） '.$data['exceed_rate'].'天/次^';
            }
        } 
        
        if(empty($data['exceed_num'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入过期提醒次数';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_num'] != $info['exceed_num']){
                $aptitude_content = $aptitude_content.'过期提醒次数：（原内容） '.$info['exceed_rate'].'次 （新内容） '.$data['exceed_rate'].'次^';
            }
        }
        if(empty($data['exceed_phone'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入提醒手机号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_phone'] != $info['exceed_phone']){
                $aptitude_content = $aptitude_content.'提醒手机号：（原内容） '.$info['exceed_phone'].' （新内容） '.$data['exceed_phone'].'^';
            }
        } 
        if(empty($data['product_home'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册人住所';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_home'] != $info['product_home']){
                $aptitude_content = $aptitude_content.'注册人住所：（原内容） '.$info['product_home'].' （新内容） '.$data['product_home'].'^';
            }
        } 
        if(empty($data['product_address'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册人生产地址';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_address'] != $info['product_address']){
                $aptitude_content = $aptitude_content.'注册人生产地址：（原内容） '.$info['product_address'].' （新内容） '.$data['product_address'].'^';
            }
        } 
        if(empty($data['product_name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入产品名称';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_name'] != $info['product_name']){
                $aptitude_content = $aptitude_content.'产品名称：（原内容） '.$info['product_name'].' （新内容） '.$data['product_name'].'^';
            }
        } 
        if(empty($data['product_names'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入产品简称';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_names'] != $info['product_names']){
                $aptitude_content = $aptitude_content.'产品简称：（原内容） '.$info['product_names'].' （新内容） '.$data['product_names'].'^';
            }
        } 
        if(empty($data['product_specification'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入规格型号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_specification'] != $info['product_specification']){
                $aptitude_content = $aptitude_content.'规格型号：（原内容） '.$info['product_specification'].' （新内容） '.$data['product_specification'].'^';
            }
        } 
        if(empty($data['product_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入样本类型';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_type'] != $info['product_type']){
                $aptitude_content = $aptitude_content.'样本类型：（原内容） '.$info['product_type'].' （新内容） '.$data['product_type'].'^';
            }
        } 
        if(empty($data['product_ingredient'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入主要组成成分';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_ingredient'] != $info['product_ingredient']){
                $aptitude_content = $aptitude_content.'主要组成成分：（原内容） '.$info['product_ingredient'].' （新内容） '.$data['product_ingredient'].'^';
            }
        } 
        if(empty($data['product_purpose'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入预期用途';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_purpose'] != $info['product_purpose']){
                $aptitude_content = $aptitude_content.'预期用途：（原内容） '.$info['product_purpose'].' （新内容） '.$data['product_purpose'].'^';
            }
        }
        
        if(empty($data['product_information'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入产品储存条件及有效期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_information'] != $info['product_information']){
                $aptitude_content = $aptitude_content.'产品储存条件及有效期：（原内容） '.$info['product_information'].' （新内容） '.$data['product_information'].'^';
            }
        }
        
        $principal_name = Db::name('users')->where('id',$data['principal'])->value('username');
        
        $data['principal_name'] = $principal_name;
        
        $RC = new RC();
        $result = $RC->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            
            $zzid = 'register'.$data['id'];
            Db::name('remind')->where('zzid',$zzid)->where('status',1)->delete();
            
            //判断结束日期
            if(!empty($data['end_time'])){
                $endtime = $data['end_time'];
            }else{
                $endtime = $data['validity_time'];
            }
            
            //根据日期类型去判断天数
            if($reminder_type == 1){
                $reminderday = $data['reminder_time'];
            }else if($reminder_type == 2){
                $reminderday = $data['reminder_time']*30;
            }else{
                $reminderday = $data['reminder_time']*365;
            }
            
            //判断频率是否合理
            if($reminderday < $data['reminder_rate']){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '临期提醒频率不得大于过期时间';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //计算提醒日期
            $remind_time = strtotime($endtime)-($reminderday*86400);
            $remind_day = date('Y-m-d',$remind_time);
            $remind_rate = $data['reminder_rate']*86400;
            $end_time = strtotime($endtime);
            //循环添加提醒记录
            while($remind_time < $end_time)
            {
                if($remind_time > time()){
                    
                    $snum = intval(($end_time - $remind_time)/86400);
                    
                    $ins['type_id'] = 1;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = $zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-医疗器械注册证-'.$data['bianhao'].'-剩余'.$snum.'天过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_time);
                    $ins['remind_type'] = 1;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
                
                $remind_time = $remind_time + $remind_rate;
            }
            
            //循环添加过期记录
            for($i = 1;$i <= $data['exceed_num'];$i++){
                $remind_endtime = $end_time + ($i*$data['exceed_rate']*86400);
                if($remind_endtime > time()){
                    $ins['type_id'] = 1;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = $zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-医疗器械注册证-'.$data['bianhao'].'-已过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_endtime);
                    $ins['remind_type'] = 2;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
            }
            
            
            //增加操作记录
            if($aptitude_content != ''){
                aptitudelog($type_id = 1, $data['id'],$aptitude_type = 2,$aptitude_content,$aptitude_uid = $this->admin_id);
            }
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
        
    }
    
    //注册证删除
    public function register_del(){
        $data = Request::param();
        
        if(empty($data['id'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入id';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $info = Db::name('register_credential')->where('id',$data['id'])->find();
        }
        
        if($info['is_delete'] == 2){
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = '该资质已删除';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $data['is_delete'] = 2;
        }
     
        $RC = new RC();
        $result = $RC->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $aptitude_content='删除该数据';
            //增加操作记录
            if($aptitude_content != ''){
                aptitudelog($type_id = 1, $data['id'],$aptitude_type = 3,$aptitude_content,$aptitude_uid = $this->admin_id);
            }
            
            
            $zzid = 'register_credential'.$data['id'];
            Db::name('remind')->where('zzid',$zzid)->where('status',1)->delete();
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
        
    }
    
    //商标列表
    public function brand_list(){

        //条件筛选
        $bianhao = Request::param('bianhao');
        $brand_name = Request::param('brand_name');
        $name = Request::param('name');
        $datetype = Request::param('datetype');
        $start = Request::param('start');
        $end = Request::param('end');
        $status = Request::param('status');
        
        $is_scan = Request::param('is_scan');
        $brand_type = Request::param('brand_type');
        $brand_status = Request::param('brand_status');
        $brand_performance = Request::param('brand_performance');
        //全局查询条件
        $where=[];

        // if(!empty($keyword)){
        //     $where[]=['rc.bianhao|rc.principal_name|rc.brand_name', 'like', '%'.$keyword.'%'];
        // }
        if(!empty($bianhao)){
            $where[]=['rc.bianhao', 'like', '%'.$bianhao.'%'];
        }
        if(!empty($brand_name)){
            $where[]=['rc.brand_name', 'like', '%'.$brand_name.'%'];
        }
        if(!empty($name)){
            $where[]=['rc.name', 'like', '%'.$name.'%'];
        }
        if(!empty($is_scan)){
            $where[]=['rc.is_scan', '=', $is_scan];
        }
        if(!empty($brand_type)){
            $where[]=['rc.brand_type', '=', $brand_type];
        }
        if(!empty($brand_status)){
            $where[]=['rc.brand_status', '=', $brand_status];
        }
        if(!empty($brand_performance)){
            $where[]=['rc.brand_performance', '=', $brand_performance];
        }
        if(!empty($datetype)){
            if(isset($start)&&$start!=""&&isset($end)&&$end=="")
            {
                $where[] = ['rc.'.$datetype,'>=',$start];
            }
            if(isset($end)&&$end!=""&&isset($start)&&$start=="")
            {
                $where[] = ['rc.'.$datetype,'<=',$end];
            }
            if(isset($start)&&$start!=""&&isset($end)&&$end!="")
            {
                $where[] = ['rc.'.$datetype,'between',[$start,$end]];
            }
        }
        
        $where[] = ['rc.is_delete','=',1];
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        $list = Db::name('brand')
            ->alias('rc')
            ->leftJoin('users u','rc.principal = u.id')
            ->field('rc.*,u.username as username')
            ->order('rc.end_time asc,rc.validity_time asc')
            ->where($where)
            ->select();
            
        foreach($list as $key => $val){
            $time = date('Y-m-d',time());
            //根据日期类型去判断天数
            if($val['reminder_type'] == 1){
                $reminderday = $val['reminder_time'];
            }elseif($val['reminder_type'] == 2){
                $reminderday = $val['reminder_time']*30;
            }else{
                $reminderday = $val['reminder_time']*365;
            }
                    
            if($val['end_time'] != '0000-00-00'){
                if($val['end_time'] < $time){
                    $list[$key]['status'] = 1;
                    $list[$key]['number'] = -intval((strtotime($time)-strtotime($val['end_time']))/86400);
                }else{
                    $remind_time = strtotime($val['end_time'])-($reminderday*86400);
                    if($remind_time < time()){
                        $list[$key]['status'] = 2;
                        $list[$key]['number'] = intval((time()-$remind_time)/86400);
                    }else{
                        $list[$key]['status'] = 3;
                        $list[$key]['number'] = intval(($remind_time-time())/86400);
                    }
                }
            }else{
                if($val['validity_time'] < $time){
                    $list[$key]['status'] = 1;
                    $list[$key]['number'] = -intval((strtotime($time)-strtotime($val['validity_time']))/86400);
                }else{
                    $remind_time = strtotime($val['validity_time'])-($reminderday*86400);
                    if($remind_time < time()){
                        $list[$key]['status'] = 2;
                        $list[$key]['number'] = intval((time()-$remind_time)/86400);
                    }else{
                        $list[$key]['status'] = 3;
                        $list[$key]['number'] = intval(($remind_time-time())/86400);
                    }
                }
            }
            
            $list[$key]['exceed_phone'] = explode('^',$val['exceed_phone']);
           
            
        }
        
        usort($list, array('\app\admin\model\Aptitude','cmp_sequence_score'));
        
        if(!empty($status)){
            $list = seacharr_by_value($list,'status',$status);
        }
        
        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;
        //调取列表
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
		return json_encode($rs_arr,true);
		exit;

    }
    
    //商标添加
    public function brand_add(){
        $data = Request::param();
        if(empty($data['name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择注册人';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['brand_name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入商标名称';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['leibie'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入国际分类';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['bianhao'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册证编号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['principal'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择资质负责人';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['brand_status'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入商标状态';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['brand_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入业务类型';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['brand_performance'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入完成情况';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['apply_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入申请日期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        
        if(empty($data['register_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册日期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['validity_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入有效期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '临期提醒天数';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择临期提醒时间类型';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入临期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['exceed_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '过期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['exceed_num'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入过期提醒次数';
    		return json_encode($rs_arr,true);
    		exit;
        }
        if(empty($data['exceed_phone'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入提醒手机号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        
        $principal_name = Db::name('users')->where('id',$data['principal'])->value('username');
        
        $data['principal_name'] = $principal_name;
        
        $B = new B();
        $result =  $B->addPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            
            $zzid = Db::name('brand')->order('id desc')->limit(1)->value('id');
            //判断结束日期
            if(!empty($data['end_time'])){
                $endtime = $data['end_time'];
            }else{
                $endtime = $data['validity_time'];
            }
            //根据日期类型去判断天数
            if($data['reminder_type'] == 1){
                $reminderday = $data['reminder_time'];
            }elseif($data['reminder_type'] == 2){
                $reminderday = $data['reminder_time']*30;
            }else{
                $reminderday = $data['reminder_time']*365;
            }
            //判断频率是否合理
            if($reminderday < $data['reminder_rate']){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '临期提醒频率不得大于过期时间';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //计算提醒日期
            $remind_time = strtotime($endtime)-($reminderday*86400);
            $remind_day = date('Y-m-d',$remind_time);
            $remind_rate = $data['reminder_rate']*86400;
            $end_time = strtotime($endtime);
            //循环添加提醒记录
            while($remind_time < $end_time)
            {
                if($remind_time > time()){
                    
                    $snum = intval(($end_time - $remind_time)/86400);
                    
                    $ins['type_id'] = 2;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = 'brand'.$zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-商标-'.$data['brand_name'].$data['bianhao'].'-剩余'.$snum.'天过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_time);
                    $ins['remind_type'] = 1;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
                
                $remind_time = $remind_time + $remind_rate;
            }
            
            //循环添加过期记录
            for($i = 1;$i <= $data['exceed_num'];$i++){
                $remind_endtime = $end_time + ($i*$data['exceed_rate']*86400);
                if($remind_endtime > time()){
                    $ins['type_id'] = 2;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = 'brand'.$zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-商标-'.$data['brand_name'].$data['bianhao'].'-已过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_endtime);
                    $ins['remind_type'] = 2;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
            }
            
            //增加操作记录
            aptitudelog($type_id = 2, $zzid, $aptitude_type = 1,$aptitude_content = '新增该数据',$aptitude_uid = $this->admin_id);

            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    //商标修改
    public function brand_upd(){
        $data = Request::param();
        $reminder_type = $data['reminder_type'];
       
        if(empty($data['id'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入id';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $info = Db::name('brand')->where('id',$data['id'])->find();
        }
        
        $aptitude_content = '';
        if(empty($data['name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择注册人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['name'] != $info['name']){
                $aptitude_content = $aptitude_content.'注册人名称：（原内容） '.$info['name'].' （新内容） '.$data['name'].'^';
            }
        } 
        if(empty($data['brand_name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入商标名称';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['brand_name'] != $info['brand_name']){
                $aptitude_content = $aptitude_content.'商标名称：（原内容） '.$info['brand_name'].' （新内容） '.$data['brand_name'].'^';
            }
        }
         
        if(empty($data['leibie'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入国际分类';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['leibie'] != $info['leibie']){
                $aptitude_content = $aptitude_content.'国际分类：（原内容） '.$info['leibie'].' （新内容） '.$data['leibie'].'^';
            }
        }
         
        if(empty($data['bianhao'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册证编号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['bianhao'] != $info['bianhao']){
                $aptitude_content = $aptitude_content.'注册证编号：（原内容） '.$info['bianhao'].' （新内容） '.$data['bianhao'].'^';
            }
        }
        if(empty($data['principal'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择资质负责人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['principal'] != $info['principal']){
                $old = Db::name('users')->where('id',$info['principal'])->value('username');
                $new = Db::name('users')->where('id',$data['principal'])->value('username');
                $aptitude_content = $aptitude_content.'资质负责人：（原内容） '.$old.' （新内容） '.$new.'^';
            }
        }  
        if(empty($data['brand_status'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入商标状态';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['brand_status'] != $info['brand_status']){
                $aptitude_content = $aptitude_content.'商标状态：（原内容） '.$info['brand_status'].' （新内容） '.$data['brand_status'].'^';
            }
        }
        if(empty($data['brand_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入业务类型';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['brand_type'] != $info['brand_type']){
                $aptitude_content = $aptitude_content.'业务类型：（原内容） '.$info['brand_type'].' （新内容） '.$data['brand_type'].'^';
            }
        } 
        if(empty($data['brand_performance'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入完成情况';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['brand_performance'] != $info['brand_performance']){
                $aptitude_content = $aptitude_content.'完成情况：（原内容） '.$info['brand_performance'].' （新内容） '.$data['brand_performance'].'^';
            }
        } 
        if(empty($data['apply_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入申请日期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['apply_time'] != $info['apply_time']){
                $aptitude_content = $aptitude_content.'申请日期：（原内容） '.$info['apply_time'].' （新内容） '.$data['apply_time'].'^';
            }
        }  
        
        if(empty($data['register_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册日期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['register_time'] != $info['register_time']){
                $aptitude_content = $aptitude_content.'注册日期：（原内容） '.$info['register_time'].' （新内容） '.$data['register_time'].'^';
            }
        }   
        if(empty($data['validity_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入有效期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['validity_time'] != $info['validity_time']){
                $aptitude_content = $aptitude_content.'有效期：（原内容） '.$info['validity_time'].' （新内容） '.$data['validity_time'].'^';
            }
        } 
        
        if(empty($data['end_time'])){
            $data['end_time'] = '0000-00-00';
        }
        if($data['end_time'] != $info['end_time']){
            $aptitude_content = $aptitude_content.'续展后证书效期：（原内容） '.$info['end_time'].' （新内容） '.$data['end_time'].'^';
        }
    
        if(empty($data['reminder_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '临期提醒天数';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_time'] != $info['reminder_time']){
                $aptitude_content = $aptitude_content.'临期提醒天数：（原内容） '.$info['reminder_time'].' （新内容） '.$data['reminder_time'].'^';
            }
        }  
        if(empty($data['reminder_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择临期提醒时间类型';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_type'] != $info['reminder_type']){
                if($info['reminder_type'] == 1){
                    $old = '日';
                }elseif($info['reminder_type'] == 2){
                    $old = '月';
                }else{
                    $old = '年';
                }
                if($reminder_type == 1){
                    $new = '日';
                }elseif($reminder_type == 2){
                    $new = '月';
                }else{
                    $new = '年';
                }
                $aptitude_content = $aptitude_content.'临期提醒时间类型：（原内容） '.$old.' （新内容） '.$new.'^';
            }
        }  
        if(empty($data['reminder_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入临期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_rate'] != $info['reminder_rate']){
                $aptitude_content = $aptitude_content.'临期提醒频率：（原内容） '.$info['reminder_rate'].'天/次 （新内容） '.$data['reminder_rate'].'天/次^';
            }
        }
        if(empty($data['exceed_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '过期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_rate'] != $info['exceed_rate']){
                $aptitude_content = $aptitude_content.'过期提醒频率：（原内容） '.$info['exceed_rate'].'天/次 （新内容） '.$data['exceed_rate'].'天/次^';
            }
        } 
        if(empty($data['exceed_num'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入过期提醒次数';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_num'] != $info['exceed_num']){
                $aptitude_content = $aptitude_content.'过期提醒次数：（原内容） '.$info['exceed_num'].'次 （新内容） '.$data['exceed_num'].'次^';
            }
        } 
        if(empty($data['exceed_phone'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入提醒手机号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_phone'] != $info['exceed_phone']){
                $aptitude_content = $aptitude_content.'提醒手机号：（原内容） '.$info['exceed_phone'].' （新内容） '.$data['exceed_phone'].'^';
            }
        }  
        
        
        if($data['is_scan'] != $info['is_scan']){
            $aptitude_content = $aptitude_content.'是否扫描：（原内容） '.$info['is_scan'].' （新内容） '.$data['is_scan'].'^';
        }
        if($data['storage_place'] != $info['storage_place']){
            $aptitude_content = $aptitude_content.'存放地点：（原内容） '.$info['storage_place'].' （新内容） '.$data['storage_place'].'^';
        }
        if($data['beizhu'] != $info['beizhu']){
            $aptitude_content = $aptitude_content.'备注：（原内容） '.$info['beizhu'].' （新内容） '.$data['beizhu'].'^';
        }
        
        $data['principal_name'] = Db::name('users')->where('id',$data['principal'])->value('username');
        
        $B = new B();
        $result = $B->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            
            $zzid = 'brand'.$data['id'];
            Db::name('remind')->where('zzid',$zzid)->where('status',1)->delete();
            
            //判断结束日期
            if(!empty($data['end_time'])){
                $endtime = $data['end_time'];
            }else{
                $endtime = $data['validity_time'];
            }
            //根据日期类型去判断天数
            if($data['reminder_type'] == 1){
                $reminderday = $data['reminder_time'];
            }elseif($data['reminder_type'] == 2){
                $reminderday = $data['reminder_time']*30;
            }else{
                $reminderday = $data['reminder_time']*365;
            }
            //判断频率是否合理
            if($reminderday < $data['reminder_rate']){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '临期提醒频率不得大于过期时间';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //计算提醒日期
            $remind_time = strtotime($endtime)-($reminderday*86400);
            $remind_day = date('Y-m-d',$remind_time);
            $remind_rate = $data['reminder_rate']*86400;
            $end_time = strtotime($endtime);
            //循环添加提醒记录
            while($remind_time < $end_time)
            {
                if($remind_time > time()){
                    
                    $snum = intval(($end_time - $remind_time)/86400);
                    
                    $ins['type_id'] = 2;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = $zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-商标-'.$data['brand_name'].$data['bianhao'].'-剩余'.$snum.'天过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_time);
                    $ins['remind_type'] = 1;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
                
                $remind_time = $remind_time + $remind_rate;
            }
            
            //循环添加过期记录
            for($i = 1;$i <= $data['exceed_num'];$i++){
                $remind_endtime = $end_time + ($i*$data['exceed_rate']*86400);
                if($remind_endtime > time()){
                    $ins['type_id'] = 2;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = $zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-商标-'.$data['brand_name'].$data['bianhao'].'-已过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_endtime);
                    $ins['remind_type'] = 2;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
            }
            
            
            if($aptitude_content != ''){
                aptitudelog($type_id = 2, $data['id'], $aptitude_type = 2,$aptitude_content,$aptitude_uid = $this->admin_id);
            }
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
        
    }

    //商标删除
    public function brand_del(){
        $data = Request::param();
        
        if(empty($data['id'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入id';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $info = Db::name('brand')->where('id',$data['id'])->find();
        }
        
        if($info['is_delete'] == 2){
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = '该资质已删除';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $data['is_delete'] = 2;
        }
     
        $B = new B();
        $result = $B->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $aptitude_content='删除该数据';
            //增加操作记录
            if($aptitude_content != ''){
                aptitudelog($type_id = 2, $data['id'],$aptitude_type = 3,$aptitude_content,$aptitude_uid = $this->admin_id);
            }
            
            $zzid = 'brand'.$data['id'];
            Db::name('remind')->where('zzid',$zzid)->where('status',1)->delete();
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
        
    }
    
    //其他资质列表
    public function other_list(){

        //条件筛选
        $name = Request::param('name');
        $title = Request::param('title');
        $issued_name = Request::param('issued_name');
        $related = Request::param('related');
        $datetype = Request::param('datetype');
        $start = Request::param('start');
        $end = Request::param('end');
        $status = Request::param('status');
        
        //全局查询条件
        $where=[];

        if(!empty($name)){
            $where[]=['o.name', 'like', '%'.$name.'%'];
        }
        if(!empty($title)){
            $where[]=['o.title', 'like', '%'.$title.'%'];
        }
        if(!empty($issued_name)){
            $where[]=['o.issued_name', 'like', '%'.$issued_name.'%'];
        }
        if(!empty($related)){
            $where[]=['o.related', 'like', '%'.$related.'%'];
        }
        
        if(!empty($datetype)){
            if(isset($start)&&$start!=""&&isset($end)&&$end=="")
            {
                $where[] = ['o.'.$datetype,'>=',$start];
            }
            if(isset($end)&&$end!=""&&isset($start)&&$start=="")
            {
                $where[] = ['o.'.$datetype,'<=',$end];
            }
            if(isset($start)&&$start!=""&&isset($end)&&$end!="")
            {
                $where[] = ['o.'.$datetype,'between',[$start,$end]];
            }
        }
        
        $where[] = ['o.is_delete','=',1];
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        $list = Db::name('other')
            ->alias('o')
            ->leftJoin('users u','o.principal = u.id')
            ->field('o.*,u.username as username')
            ->order('o.end_time asc,o.validity_time asc')
            ->where($where)
            ->select();
            
        foreach($list as $key => $val){
            $time = date('Y-m-d',time());
            //根据日期类型去判断天数
            if($val['reminder_type'] == 1){
                $reminderday = $val['reminder_time'];
            }elseif($val['reminder_type'] == 2){
                $reminderday = $val['reminder_time']*30;
            }else{
                $reminderday = $val['reminder_time']*365;
            }
                    
            if($val['end_time'] != '0000-00-00'){
                if($val['end_time'] < $time){
                    $list[$key]['status'] = 1;
                    $list[$key]['number'] = -intval((strtotime($time)-strtotime($val['end_time']))/86400);
                }else{
                    $remind_time = strtotime($val['end_time'])-($reminderday*86400);
                    if($remind_time < time()){
                        $list[$key]['status'] = 2;
                        $list[$key]['number'] = intval((time()-$remind_time)/86400);
                    }else{
                        $list[$key]['status'] = 3;
                        $list[$key]['number'] = intval(($remind_time-time())/86400);
                    }
                }
            }else{
                if($val['validity_time'] < $time){
                    $list[$key]['status'] = 1;
                    $list[$key]['number'] = -intval((strtotime($time)-strtotime($val['validity_time']))/86400);
                }else{
                    $remind_time = strtotime($val['validity_time'])-($reminderday*86400);
                    if($remind_time < time()){
                        $list[$key]['status'] = 2;
                        $list[$key]['number'] = intval((time()-$remind_time)/86400);
                    }else{
                        $list[$key]['status'] = 3;
                        $list[$key]['number'] = intval(($remind_time-time())/86400);
                    }
                }
            }
            
            $list[$key]['exceed_phone'] = explode('^',$val['exceed_phone']);
            
            if($val['reminder_time'] == 0){
                $list[$key]['reminder_time'] = '';
            }
            if($val['reminder_type'] == 0){
                $list[$key]['reminder_type'] = '';
            }
            if($val['reminder_rate'] == 0){
                $list[$key]['reminder_rate'] = '';
            }
            if($val['exceed_rate'] == 0){
                $list[$key]['exceed_rate'] = '';
            }
        }
        
        
        usort($list, array('\app\admin\model\Aptitude','cmp_sequence_score'));
        
        if(!empty($status)){
            $list = seacharr_by_value($list,'status',$status);
        }
        
        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;
        //调取列表
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
		return json_encode($rs_arr,true);
		exit;

    }
    
   //其他资质添加
    public function other_add(){
        $data = Request::param();
        if(empty($data['name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择注册人';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['title'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入证件名称';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['issued_name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入发证单位';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['related'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入资质相关人';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['principal'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择资质负责人';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['approval_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入发证日期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['validity_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入有效期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
    //     if(empty($data['reminder_time'])){
    //         $rs_arr['status'] = 201;
    // 		$rs_arr['msg'] = '临期提醒天数';
    // 		return json_encode($rs_arr,true);
    // 		exit;
    //     } 
    //     if(empty($data['reminder_type'])){
    //         $rs_arr['status'] = 201;
    // 		$rs_arr['msg'] = '请选择临期提醒时间类型';
    // 		return json_encode($rs_arr,true);
    // 		exit;
    //     } 
    //     if(empty($data['reminder_rate'])){
    //         $rs_arr['status'] = 201;
    // 		$rs_arr['msg'] = '请输入临期提醒频率';
    // 		return json_encode($rs_arr,true);
    // 		exit;
    //     } 
    //     if(empty($data['exceed_rate'])){
    //         $rs_arr['status'] = 201;
    // 		$rs_arr['msg'] = '过期提醒频率';
    // 		return json_encode($rs_arr,true);
    // 		exit;
    //     } 
    //     if(empty($data['exceed_phone'])){
    //         $rs_arr['status'] = 201;
    // 		$rs_arr['msg'] = '请输入提醒手机号';
    // 		return json_encode($rs_arr,true);
    // 		exit;
    //     } 
    //     if(empty($data['exceed_num'])){
    //         $rs_arr['status'] = 201;
    // 		   $rs_arr['msg'] = '请输入提醒次数';
    // 		   return json_encode($rs_arr,true);
    // 		   exit;
    //      } 
        
        $principal_name = Db::name('users')->where('id',$data['principal'])->value('username');
        $data['principal_name'] = $principal_name;
        
        $O = new O();
        $result =  $O->addPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            
            $zzid = Db::name('other')->order('id desc')->limit(1)->value('id');
            //判断结束日期
            if(!empty($data['end_time'])){
                $endtime = $data['end_time'];
            }else{
                $endtime = $data['validity_time'];
            }
            //根据日期类型去判断天数
            if($data['reminder_type'] == 1){
                $reminderday = $data['reminder_time'];
            }elseif($data['reminder_type'] == 2){
                $reminderday = $data['reminder_time']*30;
            }else{
                $reminderday = $data['reminder_time']*365;
            }
            //判断频率是否合理
            if($reminderday < $data['reminder_rate']){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '临期提醒频率不得大于过期时间';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //计算提醒日期
            $remind_time = strtotime($endtime)-($reminderday*86400);
            $remind_day = date('Y-m-d',$remind_time);
            $remind_rate = $data['reminder_rate']*86400;
            $end_time = strtotime($endtime);
            //循环添加提醒记录
            while($remind_time < $end_time)
            {
                if($remind_time > time()){
                    
                    $snum = intval(($end_time - $remind_time)/86400);
                    
                    $ins['type_id'] = 3;
                    $ins['bianhao'] = $data['title'];
                    $ins['zzid'] = 'other'.$zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-'.$data['title'].'-剩余 '.$snum.' 天过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_time);
                    $ins['remind_type'] = 1;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
                
                $remind_time = $remind_time + $remind_rate;
            }
            
            //循环添加过期记录
            for($i = 1;$i <= $data['exceed_num'];$i++){
                $remind_endtime = $end_time + ($i*$data['exceed_rate']*86400);
                if($remind_endtime > time()){
                    
                    $ins['type_id'] = 3;
                    $ins['bianhao'] = $data['title'];
                    $ins['zzid'] = 'other'.$zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-'.$data['title'].'-已过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_endtime);
                    $ins['remind_type'] = 2;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
            }
            
            //增加操作记录
            aptitudelog($type_id = 3, $zzid, $aptitude_type = 1,$aptitude_content = '新增该数据',$aptitude_uid = $this->admin_id);

            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    //其他资质修改
    public function other_upd(){
        $data = Request::param();
        $reminder_type = $data['reminder_type'];
       
        if(empty($data['id'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入id';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $info = Db::name('other')->where('id',$data['id'])->find();
        }
        
        $aptitude_content = '';
        if(empty($data['name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择注册人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['name'] != $info['name']){
                $aptitude_content = $aptitude_content.'注册人名称：（原内容） '.$info['name'].' （新内容） '.$data['name'].'^';
            }
        } 
        if(empty($data['title'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入证件名称';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['title'] != $info['title']){
                $aptitude_content = $aptitude_content.'商标名称：（原内容） '.$info['title'].' （新内容） '.$data['title'].'^';
            }
        }
         
        if(empty($data['issued_name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入发证单位';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['issued_name'] != $info['issued_name']){
                $aptitude_content = $aptitude_content.'国际分类：（原内容） '.$info['issued_name'].' （新内容） '.$data['issued_name'].'^';
            }
        }
         
        if(empty($data['related'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入资质相关人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['related'] != $info['related']){
                $aptitude_content = $aptitude_content.'资质相关人：（原内容） '.$info['related'].' （新内容） '.$data['related'].'^';
            }
        }
        if(empty($data['principal'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择资质负责人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['principal'] != $info['principal']){
                $old = Db::name('users')->where('id',$info['principal'])->value('username');
                $new = Db::name('users')->where('id',$data['principal'])->value('username');
                $aptitude_content = $aptitude_content.'资质负责人：（原内容） '.$old.' （新内容） '.$new.'^';
            }
        }  
       
        if(empty($data['approval_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入发证日期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['approval_time'] != $info['approval_time']){
                $aptitude_content = $aptitude_content.'发证日期：（原内容） '.$info['approval_time'].' （新内容） '.$data['approval_time'].'^';
            }
        }  
        
        if(empty($data['validity_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入有效期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['validity_time'] != $info['validity_time']){
                $aptitude_content = $aptitude_content.'有效期：（原内容） '.$info['validity_time'].' （新内容） '.$data['validity_time'].'^';
            }
        } 
       
        if(empty($data['end_time'])){
            $data['end_time'] = '0000-00-00';
        }
        if($data['end_time'] != $info['end_time']){
            $aptitude_content = $aptitude_content.'延续效期至：（原内容） '.$info['end_time'].' （新内容） '.$data['end_time'].'^';
        }
        
        if($data['reminder_time'] != $info['reminder_time']){
            $aptitude_content = $aptitude_content.'临期提醒天数：（原内容） '.$info['reminder_time'].' （新内容） '.$data['reminder_time'].'^';
        }
        if($data['reminder_type'] != $info['reminder_type']){
            if($info['reminder_type'] == 1){
                $old = '日';
            }elseif($info['reminder_type'] == 2){
                $old = '月';
            }else{
                $old = '年';
            }
            if($reminder_type == 1){
                $new = '日';
            }elseif($reminder_type == 2){
                $new = '月';
            }else{
                $new = '年';
            }
            $aptitude_content = $aptitude_content.'临期提醒时间类型：（原内容） '.$old.' （新内容） '.$new.'^';
        }
        if($data['reminder_rate'] != $info['reminder_rate']){
            $aptitude_content = $aptitude_content.'临期提醒频率：（原内容） '.$info['reminder_rate'].'天/次 （新内容） '.$data['reminder_rate'].'天/次^';
        }
        if($data['exceed_rate'] != $info['exceed_rate']){
            $aptitude_content = $aptitude_content.'过期提醒频率：（原内容） '.$info['exceed_rate'].'天/次 （新内容） '.$data['exceed_rate'].'天/次^';
        }
        if($data['exceed_num'] != $info['exceed_num']){
            $aptitude_content = $aptitude_content.'过期提醒次数：（原内容） '.$info['exceed_num'].'次 （新内容） '.$data['exceed_num'].'次^';
        }
        if($data['exceed_phone'] != $info['exceed_phone']){
            $aptitude_content = $aptitude_content.'提醒手机号：（原内容） '.$info['exceed_phone'].' （新内容） '.$data['exceed_phone'].'^';
        }
    //     if(empty($data['reminder_time'])){
    //         $rs_arr['status'] = 201;
    // 		$rs_arr['msg'] = '临期提醒天数';
    // 		return json_encode($rs_arr,true);
    // 		exit;
    //     }else{
    //         if($data['reminder_time'] != $info['reminder_time']){
    //             $aptitude_content = $aptitude_content.'临期提醒天数：（原内容） '.$info['reminder_time'].' （新内容） '.$data['reminder_time'].'^';
    //         }
    //     }  
    //     if(empty($data['reminder_type'])){
    //         $rs_arr['status'] = 201;
    // 		$rs_arr['msg'] = '请选择临期提醒时间类型';
    // 		return json_encode($rs_arr,true);
    // 		exit;
    //     }else{
    //         if($data['reminder_type'] != $info['reminder_type']){
    //             if($info['reminder_type'] == 1){
    //                 $old = '日';
    //             }elseif($info['reminder_type'] == 2){
    //                 $old = '月';
    //             }else{
    //                 $old = '年';
    //             }
    //             if($reminder_type == 1){
    //                 $new = '日';
    //             }elseif($reminder_type == 2){
    //                 $new = '月';
    //             }else{
    //                 $new = '年';
    //             }
    //             $aptitude_content = $aptitude_content.'临期提醒时间类型：（原内容） '.$old.' （新内容） '.$new.'^';
    //         }
    //     }  
    //     if(empty($data['reminder_rate'])){
    //         $rs_arr['status'] = 201;
    // 		$rs_arr['msg'] = '请输入临期提醒频率';
    // 		return json_encode($rs_arr,true);
    // 		exit;
    //     }else{
    //         if($data['reminder_rate'] != $info['reminder_rate']){
    //             $aptitude_content = $aptitude_content.'临期提醒频率：（原内容） '.$info['reminder_rate'].'天/次 （新内容） '.$data['reminder_rate'].'天/次^';
    //         }
    //     }
    //     if(empty($data['exceed_rate'])){
    //         $rs_arr['status'] = 201;
    // 		$rs_arr['msg'] = '过期提醒频率';
    // 		return json_encode($rs_arr,true);
    // 		exit;
    //     }else{
    //         if($data['exceed_rate'] != $info['exceed_rate']){
    //             $aptitude_content = $aptitude_content.'过期提醒频率：（原内容） '.$info['exceed_rate'].'天/次 （新内容） '.$data['exceed_rate'].'天/次^';
    //         }
    //     } 
    //     if(empty($data['exceed_phone'])){
    //         $rs_arr['status'] = 201;
    // 		$rs_arr['msg'] = '请输入提醒手机号';
    // 		return json_encode($rs_arr,true);
    // 		exit;
    //     }else{
    //         if($data['exceed_phone'] != $info['exceed_phone']){
    //             $aptitude_content = $aptitude_content.'提醒手机号：（原内容） '.$info['exceed_phone'].' （新内容） '.$data['exceed_phone'].'^';
    //         }
    //     }  
    //     if(empty($data['exceed_num'])){
    //         $rs_arr['status'] = 201;
    // 		   $rs_arr['msg'] = '请输入提醒手机号';
    // 		   return json_encode($rs_arr,true);
    // 		   exit;
    //     }else{
    //         if($data['exceed_num'] != $info['exceed_num']){
    //             $aptitude_content = $aptitude_content.'过期提醒次数：（原内容） '.$info['exceed_num'].'次 （新内容） '.$data['exceed_num'].'次^';
    //         } 
    //     } 
        
        
        if($data['beizhu'] != $info['beizhu']){
            $aptitude_content = $aptitude_content.'备注：（原内容） '.$info['beizhu'].' （新内容） '.$data['beizhu'].'^';
        }
        
        $principal_name = Db::name('users')->where('id',$data['principal'])->value('username');
        $data['principal_name'] = $principal_name;
        
        $O = new O();
        $result = $O->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            
            $zzid = 'other'.$data['id'];
            Db::name('remind')->where('zzid',$zzid)->where('status',1)->delete();
            
            //判断结束日期
            if(!empty($data['end_time'])){
                $endtime = $data['end_time'];
            }else{
                $endtime = $data['validity_time'];
            }
            //根据日期类型去判断天数
            if($data['reminder_type'] == 1){
                $reminderday = $data['reminder_time'];
            }elseif($data['reminder_type'] == 2){
                $reminderday = $data['reminder_time']*30;
            }else{
                $reminderday = $data['reminder_time']*365;
            }
            //判断频率是否合理
            if($reminderday < $data['reminder_rate']){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '临期提醒频率不得大于过期时间';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //计算提醒日期
            $remind_time = strtotime($endtime)-($reminderday*86400);
            $remind_day = date('Y-m-d',$remind_time);
            $remind_rate = $data['reminder_rate']*86400;
            $end_time = strtotime($endtime);
            //循环添加提醒记录
            while($remind_time < $end_time)
            {
                if($remind_time > time()){
                    
                    $snum = intval(($end_time - $remind_time)/86400);
                    
                    $ins['type_id'] = 3;
                    $ins['bianhao'] = $data['title'];
                    $ins['zzid'] = $zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-'.$data['title'].'-剩余 '.$snum.' 天过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_time);
                    $ins['remind_type'] = 1;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
                
                $remind_time = $remind_time + $remind_rate;
            }
            
            //循环添加过期记录
            for($i = 1;$i <= $data['exceed_num'];$i++){
                $remind_endtime = $end_time + ($i*$data['exceed_rate']*86400);
                if($remind_endtime > time()){
                    $ins['type_id'] = 3;
                    $ins['bianhao'] = $data['title'];
                    $ins['zzid'] = $zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-'.$data['title'].'-已过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_endtime);
                    $ins['remind_type'] = 2;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
            }
            
            
            if($aptitude_content != ''){
                aptitudelog($type_id = 3, $data['id'], $aptitude_type = 2,$aptitude_content,$aptitude_uid = $this->admin_id);
            }
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
        
    }


    //商标删除
    public function other_del(){
        $data = Request::param();
        
        if(empty($data['id'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入id';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $info = Db::name('other')->where('id',$data['id'])->find();
        }
        
        if($info['is_delete'] == 2){
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = '该资质已删除';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $data['is_delete'] = 2;
        }
     
        $O = new O();
        $result = $O->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $aptitude_content='删除该数据';
            //增加操作记录
            if($aptitude_content != ''){
                aptitudelog($type_id = 3, $data['id'],$aptitude_type = 3,$aptitude_content,$aptitude_uid = $this->admin_id);
            }
            
            $zzid = 'other'.$data['id'];
            Db::name('remind')->where('zzid',$zzid)->where('status',1)->delete();
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
        
    }
    
    
    
    //商标列表
    public function brand_world_list(){

        //条件筛选
        $bianhao = Request::param('bianhao');
        $brand_name = Request::param('brand_name');
        $bianhao = Request::param('bianhao');
        $name = Request::param('name');
        $datetype = Request::param('datetype');
        $start = Request::param('start');
        $end = Request::param('end');
        $status = Request::param('status');
        
        $is_scan = Request::param('is_scan');
        $brand_type = Request::param('brand_type');
        $brand_status = Request::param('brand_status');
        $brand_performance = Request::param('brand_performance');
        //全局查询条件
        $where=[];

        if(!empty($bianhao)){
            $where[]=['rc.bianhao|rc.apply_number', 'like', '%'.$bianhao.'%'];
        }
        if(!empty($brand_name)){
            $where[]=['rc.brand_name', 'like', '%'.$brand_name.'%'];
        }
        // if(!empty($keyword)){
        //     $where[]=['rc.bianhao|rc.principal_name|rc.brand_name', 'like', '%'.$keyword.'%'];
        // }
        if(!empty($name)){
            $where[]=['rc.name', 'like', '%'.$name.'%'];
        }
        if(!empty($is_scan)){
            $where[]=['rc.is_scan', '=', $is_scan];
        }
        if(!empty($brand_type)){
            $where[]=['rc.brand_type', '=', $brand_type];
        }
        if(!empty($brand_status)){
            $where[]=['rc.brand_status', '=', $brand_status];
        }
        if(!empty($brand_performance)){
            $where[]=['rc.brand_performance', '=', $brand_performance];
        }
        if(!empty($datetype)){
            if(isset($start)&&$start!=""&&isset($end)&&$end=="")
            {
                $where[] = ['rc.'.$datetype,'>=',$start];
            }
            if(isset($end)&&$end!=""&&isset($start)&&$start=="")
            {
                $where[] = ['rc.'.$datetype,'<=',$end];
            }
            if(isset($start)&&$start!=""&&isset($end)&&$end!="")
            {
                $where[] = ['rc.'.$datetype,'between',[$start,$end]];
            }
        }
        
        $where[] = ['rc.is_delete','=',1];
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        $list = Db::name('brand_world')
            ->alias('rc')
            ->leftJoin('users u','rc.principal = u.id')
            ->field('rc.*,u.username as username')
            ->order('rc.validity_time asc')
            ->where($where)
            ->select();
            
        foreach($list as $key => $val){
            $time = date('Y-m-d',time());
            //根据日期类型去判断天数
            if($val['reminder_type'] == 1){
                $reminderday = $val['reminder_time'];
            }elseif($val['reminder_type'] == 2){
                $reminderday = $val['reminder_time']*30;
            }else{
                $reminderday = $val['reminder_time']*365;
            }
                    
          
            if($val['validity_time'] < $time){
                $list[$key]['status'] = 1;
                $list[$key]['number'] = -intval((strtotime($time)-strtotime($val['validity_time']))/86400);
            }else{
                $remind_time = strtotime($val['validity_time'])-($reminderday*86400);
                if($remind_time < time()){
                    $list[$key]['status'] = 2;
                    $list[$key]['number'] = intval((time()-$remind_time)/86400);
                }else{
                    $list[$key]['status'] = 3;
                    $list[$key]['number'] = intval(($remind_time-time())/86400);
                }
            }
        
            
            $list[$key]['exceed_phone'] = explode('^',$val['exceed_phone']);
           
            
        }
        
        usort($list, array('\app\admin\model\Aptitude','cmp_sequence_score'));
        
        if(!empty($status)){
            $list = seacharr_by_value($list,'status',$status);
        }
        
        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;
        //调取列表
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
		return json_encode($rs_arr,true);
		exit;

    }
    
    //商标添加
    public function brand_world_add(){
        $data = Request::param();
        if(empty($data['name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册主体';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['brand_name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入商标名称';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        
        if(empty($data['apply_country'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入申请国家';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        
        if(empty($data['apply_number'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入申请号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        
        if(empty($data['apply_address'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择申请人地址';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['bianhao'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['apply_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入申请日期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['register_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册日期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['validity_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入有效期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['leibie'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入国际分类';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '临期提醒天数';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择临期提醒时间类型';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入临期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['exceed_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '过期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['exceed_num'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入过期提醒次数';
    		return json_encode($rs_arr,true);
    		exit;
        }
        if(empty($data['exceed_phone'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入提醒手机号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['principal'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择资质负责人';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        
        
        $principal_name = Db::name('users')->where('id',$data['principal'])->value('username');
        
        $data['principal_name'] = $principal_name;
        
        $BW = new BW();
        $result =  $BW->addPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            
            $zzid = Db::name('brand_world')->order('id desc')->limit(1)->value('id');
            //判断结束日期
            if(!empty($data['end_time'])){
                $endtime = $data['end_time'];
            }else{
                $endtime = $data['validity_time'];
            }
            //根据日期类型去判断天数
            if($data['reminder_type'] == 1){
                $reminderday = $data['reminder_time'];
            }elseif($data['reminder_type'] == 2){
                $reminderday = $data['reminder_time']*30;
            }else{
                $reminderday = $data['reminder_time']*365;
            }
            //判断频率是否合理
            if($reminderday < $data['reminder_rate']){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '临期提醒频率不得大于过期时间';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //计算提醒日期
            $remind_time = strtotime($endtime)-($reminderday*86400);
            $remind_day = date('Y-m-d',$remind_time);
            $remind_rate = $data['reminder_rate']*86400;
            $end_time = strtotime($endtime);
            //循环添加提醒记录
            while($remind_time < $end_time)
            {
                if($remind_time > time()){
                    
                    $snum = intval(($end_time - $remind_time)/86400);
                    
                    $ins['type_id'] = 4;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = 'brand_world'.$zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-国际商标-'.$data['brand_name'].$data['bianhao'].'-剩余'.$snum.'天过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_time);
                    $ins['remind_type'] = 1;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
                
                $remind_time = $remind_time + $remind_rate;
            }
            
            //循环添加过期记录
            for($i = 1;$i <= $data['exceed_num'];$i++){
                $remind_endtime = $end_time + ($i*$data['exceed_rate']*86400);
                if($remind_endtime > time()){
                    $ins['type_id'] = 4;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = 'brand_world'.$zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-国际商标-'.$data['brand_name'].$data['bianhao'].'-已过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_endtime);
                    $ins['remind_type'] = 2;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
            }
            
            //增加操作记录
            aptitudelog($type_id = 4, $zzid, $aptitude_type = 1,$aptitude_content = '新增该数据',$aptitude_uid = $this->admin_id);

            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    //商标修改
    public function brand_world_upd(){
        $data = Request::param();
        $reminder_type = $data['reminder_type'];
       
        if(empty($data['id'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入id';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $info = Db::name('brand_world')->where('id',$data['id'])->find();
        }
        
        $aptitude_content = '';
        if(empty($data['name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择注册人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['name'] != $info['name']){
                $aptitude_content = $aptitude_content.'注册人名称：（原内容） '.$info['name'].' （新内容） '.$data['name'].'^';
            }
        } 
        if(empty($data['brand_name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入商标名称';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['brand_name'] != $info['brand_name']){
                $aptitude_content = $aptitude_content.'商标名称：（原内容） '.$info['brand_name'].' （新内容） '.$data['brand_name'].'^';
            }
        }
        if(empty($data['apply_country'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入申请国家';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['apply_country'] != $info['apply_country']){
                $aptitude_content = $aptitude_content.'申请国家：（原内容） '.$info['apply_country'].' （新内容） '.$data['apply_country'].'^';
            }
        }
        
        if(empty($data['apply_number'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入申请号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['apply_number'] != $info['apply_number']){
                $aptitude_content = $aptitude_content.'申请号：（原内容） '.$info['apply_number'].' （新内容） '.$data['apply_number'].'^';
            }
        } 
        
        if(empty($data['apply_address'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择申请人地址';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['apply_address'] != $info['apply_address']){
                $aptitude_content = $aptitude_content.'申请人地址：（原内容） '.$info['apply_address'].' （新内容） '.$data['apply_address'].'^';
            }
        }
        if(empty($data['bianhao'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册证编号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['bianhao'] != $info['bianhao']){
                $aptitude_content = $aptitude_content.'注册证编号：（原内容） '.$info['bianhao'].' （新内容） '.$data['bianhao'].'^';
            }
        }
        if(empty($data['apply_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入申请日期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['apply_time'] != $info['apply_time']){
                $aptitude_content = $aptitude_content.'申请日期：（原内容） '.$info['apply_time'].' （新内容） '.$data['apply_time'].'^';
            }
        }  
        
        if(empty($data['register_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册日期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['register_time'] != $info['register_time']){
                $aptitude_content = $aptitude_content.'注册日期：（原内容） '.$info['register_time'].' （新内容） '.$data['register_time'].'^';
            }
        }   
        if(empty($data['validity_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入有效期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['validity_time'] != $info['validity_time']){
                $aptitude_content = $aptitude_content.'有效期：（原内容） '.$info['validity_time'].' （新内容） '.$data['validity_time'].'^';
            }
        } 
        if(empty($data['leibie'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入国际分类';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['leibie'] != $info['leibie']){
                $aptitude_content = $aptitude_content.'国际分类：（原内容） '.$info['leibie'].' （新内容） '.$data['leibie'].'^';
            }
        }
        if(empty($data['reminder_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '临期提醒天数';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_time'] != $info['reminder_time']){
                $aptitude_content = $aptitude_content.'临期提醒天数：（原内容） '.$info['reminder_time'].' （新内容） '.$data['reminder_time'].'^';
            }
        }  
        if(empty($data['reminder_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择临期提醒时间类型';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_type'] != $info['reminder_type']){
                if($info['reminder_type'] == 1){
                    $old = '日';
                }elseif($info['reminder_type'] == 2){
                    $old = '月';
                }else{
                    $old = '年';
                }
                if($reminder_type == 1){
                    $new = '日';
                }elseif($reminder_type == 2){
                    $new = '月';
                }else{
                    $new = '年';
                }
                $aptitude_content = $aptitude_content.'临期提醒时间类型：（原内容） '.$old.' （新内容） '.$new.'^';
            }
        }  
        if(empty($data['reminder_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入临期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_rate'] != $info['reminder_rate']){
                $aptitude_content = $aptitude_content.'临期提醒频率：（原内容） '.$info['reminder_rate'].'天/次 （新内容） '.$data['reminder_rate'].'天/次^';
            }
        }
        if(empty($data['exceed_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '过期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_rate'] != $info['exceed_rate']){
                $aptitude_content = $aptitude_content.'过期提醒频率：（原内容） '.$info['exceed_rate'].'天/次 （新内容） '.$data['exceed_rate'].'天/次^';
            }
        } 
        if(empty($data['exceed_num'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入过期提醒次数';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_num'] != $info['exceed_num']){
                $aptitude_content = $aptitude_content.'过期提醒次数：（原内容） '.$info['exceed_num'].'次 （新内容） '.$data['exceed_num'].'次^';
            }
        } 
        if(empty($data['exceed_phone'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入提醒手机号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_phone'] != $info['exceed_phone']){
                $aptitude_content = $aptitude_content.'提醒手机号：（原内容） '.$info['exceed_phone'].' （新内容） '.$data['exceed_phone'].'^';
            }
        }  
        
        if($data['aptitude_type'] != $info['aptitude_type']){
            $aptitude_content = $aptitude_content.'证件类型：（原内容） '.$info['aptitude_type'].' （新内容） '.$data['aptitude_type'].'^';
        }
        if($data['beizhu'] != $info['beizhu']){
            $aptitude_content = $aptitude_content.'备注：（原内容） '.$info['beizhu'].' （新内容） '.$data['beizhu'].'^';
        }
        
        if(empty($data['principal'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择资质负责人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['principal'] != $info['principal']){
                $old = Db::name('users')->where('id',$info['principal'])->value('username');
                $new = Db::name('users')->where('id',$data['principal'])->value('username');
                $aptitude_content = $aptitude_content.'资质负责人：（原内容） '.$old.' （新内容） '.$new.'^';
            }
        }
        
    
        $data['principal_name'] = Db::name('users')->where('id',$data['principal'])->value('username');
        
        $BW = new BW();
        $result = $BW->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            
            $zzid = 'brand_world'.$data['id'];
            Db::name('remind')->where('zzid',$zzid)->where('status',1)->delete();
            
            //判断结束日期
            if(!empty($data['end_time'])){
                $endtime = $data['end_time'];
            }else{
                $endtime = $data['validity_time'];
            }
            //根据日期类型去判断天数
            if($data['reminder_type'] == 1){
                $reminderday = $data['reminder_time'];
            }elseif($data['reminder_type'] == 2){
                $reminderday = $data['reminder_time']*30;
            }else{
                $reminderday = $data['reminder_time']*365;
            }
            //判断频率是否合理
            if($reminderday < $data['reminder_rate']){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '临期提醒频率不得大于过期时间';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //计算提醒日期
            $remind_time = strtotime($endtime)-($reminderday*86400);
            $remind_day = date('Y-m-d',$remind_time);
            $remind_rate = $data['reminder_rate']*86400;
            $end_time = strtotime($endtime);
            //循环添加提醒记录
            while($remind_time < $end_time)
            {
                if($remind_time > time()){
                    
                    $snum = intval(($end_time - $remind_time)/86400);
                    
                    $ins['type_id'] = 4;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = $zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-国际商标-'.$data['brand_name'].$data['bianhao'].'-剩余'.$snum.'天过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_time);
                    $ins['remind_type'] = 1;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
                
                $remind_time = $remind_time + $remind_rate;
            }
            
            //循环添加过期记录
            for($i = 1;$i <= $data['exceed_num'];$i++){
                $remind_endtime = $end_time + ($i*$data['exceed_rate']*86400);
                if($remind_endtime > time()){
                    $ins['type_id'] = 4;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = $zzid;
                    $ins['name'] = $data['name'];
                    $ins['neirong'] = $data['name'].'-国际商标-'.$data['brand_name'].$data['bianhao'].'-已过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_endtime);
                    $ins['remind_type'] = 2;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
            }
            
            
            if($aptitude_content != ''){
                aptitudelog($type_id = 4, $data['id'], $aptitude_type = 2,$aptitude_content,$aptitude_uid = $this->admin_id);
            }
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
        
    }

    //商标删除
    public function brand_world_del(){
        $data = Request::param();
        
        if(empty($data['id'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入id';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $info = Db::name('brand_world')->where('id',$data['id'])->find();
        }
        
        if($info['is_delete'] == 2){
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = '该资质已删除';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $data['is_delete'] = 2;
        }
     
        $BW = new BW();
        $result = $BW->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $aptitude_content='删除该数据';
            //增加操作记录
            if($aptitude_content != ''){
                aptitudelog($type_id = 4, $data['id'],$aptitude_type = 3,$aptitude_content,$aptitude_uid = $this->admin_id);
            }
            
            $zzid = 'brand_world'.$data['id'];
            Db::name('remind')->where('zzid',$zzid)->where('status',1)->delete();
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
        
    }
    
    
}
