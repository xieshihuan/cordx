<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
use think\facade\Request;


//实例化默认模型
use app\common\model\TrainApply as TA;

class TrainApply extends Base
{
    
    protected $validate = 'TrainApply';
    
     //权限列表
    public function index(){
       
        $where = [];
       
        $keyword = Request::param('keyword');
        $catid = Request::param('catid');
        $period = Request::param('period');
        $start = Request::param('start');
        $end = Request::param('end');
        $start = strtotime(date($start));
        $end = strtotime(date($end));
        
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['u1.username|u1.mobile', 'like', '%'.$keyword.'%'];
        }
        
        $uinfo = Db::name('users')->where('id',$this->admin_id)->find();
        $ruless = trim($uinfo['period_ruless'],',');
        
        if($uinfo['id'] > 1){
            if(!empty($catid)){
                if(in_array($catid,explode(',',$ruless))){
                    $whr1[]=['catid', '=', $catid];
                    $uuid= Db::name('cateuser')->field('uid')->where($whr1)->buildSql(true);
                }else{
                    $rs_arr['status'] = 200;
                    $rs_arr['msg'] = '无权限';
                    $rs_arr['data'] = array();
                    return json_encode($rs_arr,true);
                    exit;
                }
            }else{
                $whr2[]=['catid', 'in', $ruless];
                $uuid= Db::name('cateuser')->field('uid')->where($whr2)->buildSql(true);
            }
        }else{
            if(!empty($catid)){
                $whr1[]=['catid', '=', $catid];
                $uuid= Db::name('cateuser')->field('uid')->where($whr1)->buildSql(true);
            }else{
                $whr2[]=['catid', '>', 0];
                $uuid= Db::name('cateuser')->field('uid')->where($whr2)->buildSql(true);
            }
        }
        
        if(!empty($period)){
            $where[] = ['ta.period' ,'=', $period];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end=="")
        {
            $where[] = ['ta.apply_time','>=',$start];
        }
        if(isset($end)&&$end!=""&&isset($start)&&$start=="")
        {
            $where[] = ['ta.apply_time','<=',$end];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end!="")
        {
            $where[] = ['ta.apply_time','between',[$start,$end]];
        }

        $where[] = ['ta.apply_uid' ,'=', $this->admin_id];
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : config('page');
        
        $a = $pageSize*($page-1);
        
        $count = Db::name('train_apply')
            ->alias('ta')
            ->leftJoin('users u1','u1.id = ta.uid')
            ->leftJoin('users u2','u2.id = ta.apply_uid')
            ->leftJoin('train_cate tc','tc.id = ta.catid')
            ->field('ta.*,u1.username as username,u1.mobile as mobile,u2.username as apply_name')
            ->group('unionid')
            ->order('ta.id DESC')
            ->where($where)
            ->where('ta.uid','exp','In '.$uuid)
            ->count();
            
        //调取列表
        $list = Db::name('train_apply')
            ->alias('ta')
            ->leftJoin('users u1','u1.id = ta.uid')
            ->leftJoin('users u2','u2.id = ta.apply_uid')
            ->leftJoin('train_cate tc','tc.id = ta.catid')
            ->field('ta.*,u1.username as username,u1.mobile as mobile,u2.username as apply_name')
            ->group('unionid')
            ->order('ta.id DESC')
            ->limit($a.','.$pageSize)
            ->where($where)
            ->where('ta.uid','exp','In '.$uuid)
            ->select();
        
        foreach ($list as $key => $val){
            
            //查询上级
            if($val['type'] == 2){
                $list[$key]['topname'] = Db::name('train_cate')->where('id',$val['catid'])->value('title').' - 答题申请';
            }else{
                $topid = Db::name('train_cate')->where('id',$val['catid'])->value('parentid');
                $list[$key]['topname'] = Db::name('train_cate')->where('id',$topid)->value('title');
            }
            
            //审批项
            $list[$key]['apply_list'] = Db::name('train_apply')
                ->alias('ta')
                ->leftJoin('train_cate tc','tc.id = ta.catid')
                ->field('tc.id,tc.title')
                ->where('unionid',$val['unionid'])
                ->select();
            
            $whraa['uid'] = $val['uid'];
            $whraa['leixing'] = 1;
            $clist = Db::name('cateuser')
            ->where($whraa)
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
        
        $rlist['total'] = $count;
        $rlist['data'] = $list;
          
         
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $rlist;
        return json_encode($rs_arr);
        exit;
    
    }
    
    public function indexs(){
       
        $where = [];
       
        $keyword = Request::param('keyword');
        $catid = Request::param('catid');
        $period = Request::param('period');
        $start = Request::param('start');
        $end = Request::param('end');
        $start = strtotime(date($start));
        $end = strtotime(date($end));
        
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        if(!empty($catid)){
            $whr1[]=['catid', '=', $catid];
            $uuid= Db::name('cateuser')->field('uid')->where($whr1)->buildSql(true);
        }else{
            $uinfo = Db::name('users')->where('id',$this->admin_id)->find();
            if($uinfo['group_id'] == 1 || $uinfo['group_id'] == 2 || $uinfo['group_id'] == 7 || $uinfo['group_id'] == 12 || $uinfo['group_id'] == 13 || $uinfo['group_id'] == 14 || $uinfo['group_id'] == 15){
                $whra[] = ['catid','in','1'];
                $uuid= Db::name('cateuser')->field('uid')->where($whra)->buildSql(true);
            }else{
                $ruless = $uinfo['ruless'].','.$uinfo['period_ruless'];
                
                $whr2[]=['catid', 'in', $ruless];
                $uuid= Db::name('cateuser')->field('uid')->where($whr2)->buildSql(true);
            }
        }
        if(!empty($period)){
            $where[] = ['ta.period' ,'=', $period];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end=="")
        {
            $where[] = ['ta.apply_time','>=',$start];
        }
        if(isset($end)&&$end!=""&&isset($start)&&$start=="")
        {
            $where[] = ['ta.apply_time','<=',$end];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end!="")
        {
            $where[] = ['ta.apply_time','between',[$start,$end]];
        }

        $where[] = ['tr.apply_uid' ,'=', $this->admin_id];
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : config('page');
        
        $a = $pageSize*($page-1);
        
        $count = Db::name('train_record')
            ->alias('tr')
            ->leftJoin('train_apply ta','tr.apply_id = ta.id')
            ->leftJoin('users u','ta.uid = u.id')
            ->leftJoin('users u1','ta.apply_uid = u.id')
            ->field('tr.*,tr.status as train_status,ta.*,u.username as username,u.mobile as mobile,u1.username as apply_name')
            ->order('ta.id DESC')
            ->where($where)
            ->where('ta.uid','exp','In '.$uuid)
            ->count();
            
        //调取列表
        
        $list = Db::name('train_record')
            ->alias('tr')
            ->leftJoin('train_apply ta','tr.apply_id = ta.id')
            ->leftJoin('users u','tr.uid = u.id')
            ->leftJoin('users u1','tr.apply_uid = u.id')
            ->field('tr.*,tr.status as train_status,ta.*,u.username as username,u.mobile as mobile,u1.username as apply_name')
            ->order('tr.id DESC')
            ->limit($a.','.$pageSize)
            ->where($where)
            ->where('ta.uid','exp','In '.$uuid)
            ->group('ta.unionid')
            ->select();
           
        foreach ($list as $key => $val){
            $whraa['uid'] = $val['uid'];
            $whraa['leixing'] = 1;
            $clist = Db::name('cateuser')
            ->where($whraa)
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
            $list[$key]['catname'] = Db::name('train_cate')->where('id',$val['catid'])->value('title');
            
        }
        
        $rlist['total'] = $count;
        $rlist['data'] = $list;
          
         
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $rlist;
        return json_encode($rs_arr);
        exit;
    
    }
    
    
    //管理员待办详情
    public function detail(){
        $unionid = Request::param('unionid');
        
        $where = [];
        if(!empty($unionid)){
            $where[] = ['unionid','=',$unionid];
        }
        
        $tinfo = Db::name('train_apply')->where('apply_uid',$this->admin_id)->where($where)->find();
    
        $whrab['uid'] = $tinfo['uid'];
        $whrab['leixing'] = 1;
        $clist = Db::name('cateuser')
            ->field('catid')
            ->where($whrab)
            ->select();
        foreach ($clist as $keys => $vals){
            $group_name = self::select_name($vals['catid']);
            $arr = explode('/',$group_name);
            $arrs = array_reverse($arr);
            $group_list = implode('/',$arrs);
            $group_list = ltrim($group_list,'/');
            $clist[$keys]['group_name'] = $group_list;
        }
        $tinfo['clist'] = $clist;
        
        $tinfo['username'] = Db::name('users')->where('id',$tinfo['uid'])->value('username');
        $tinfo['mobile'] = Db::name('users')->where('id',$tinfo['uid'])->value('mobile');
        $tinfo['apply_time'] = date('Y-m-d H:i:s',$tinfo['apply_time']);
        
        //查询上级
        if($tinfo['type'] == 2){
            $tinfo['topname'] = Db::name('train_cate')->where('id',$tinfo['catid'])->value('title').' - 答题申请';
        }else{
            $topid = Db::name('train_cate')->where('id',$tinfo['catid'])->value('parentid');
            $tinfo['topname'] = Db::name('train_cate')->where('id',$topid)->value('title');
        }

        $sons = Db::name('train_apply')
        ->alias('ta')
        ->leftJoin('train_cate tc','ta.catid = tc.id')
        ->field('ta.*,tc.title as catename')
        ->where('unionid',$tinfo['unionid'])
        ->where($where)
        ->select();
        foreach ($sons as $key => $val){
            $photo_list = Db::name('train_image')->where('uid',$val['uid'])->where('catid',$val['catid'])->select();
            foreach ($photo_list as $keys => $vals){
                $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                $photo_list[$keys]['url'] = $http_type.$_SERVER['HTTP_HOST'].$vals['url'];
            }
            
            $sons[$key]['photo_list'] = $photo_list;
        }
    
        $tinfo['sons'] = $sons;
            
        $tinfo['record'] = Db::name('train_record')
            ->alias('tr')
            ->leftJoin('users u','tr.apply_uid = u.id')
            ->field('tr.*,u.username as apply_name')
            ->where('unionid',$tinfo['unionid'])
            ->where($where)
            ->group('unionid')
            ->order('id desc')
            ->select();
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $tinfo;
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
    
    
    //管理员审核
    public function apply_exam(){
        $unionid = Request::param('unionid');
        $status = Request::param('status');
        $content = Request::param('content');
        if(empty($unionid)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = 'unionid not found';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $statu = Db::name('train_apply')->where('unionid',$unionid)->value('status');
            $uid = Db::name('train_apply')->where('unionid',$unionid)->value('uid');
            if($statu == 2){
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '已通过,请勿重复审批';
                return json_encode($rs_arr,true);
                exit;
            }
        }
        if(empty($status)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = 'status not found';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $arr = array('2','3');
            if(!in_array($status,$arr)){
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '请输入正确的状态';
                return json_encode($rs_arr,true);
                exit;
            }
        }
        if($status == 2){
            $list = Db::name('train_apply')->where('unionid',$unionid)->where('type',2)->select();
            foreach ($list as $key => $val){
                $tiku_id = Db::name('train_cate')->where('id',$val['catid'])->value('tiku_id');
                
                $testid = Db::name('traintest')->where('uid',$val['uid'])->where('tid',$tiku_id)->value('id');
                
                Db::name('traincache')->where('testid',$testid)->delete();
                
                Db::name('traintest')->where('uid',$val['uid'])->where('tid',$tiku_id)->delete();
            }
        }
        
        $rlist = Db::name('train_apply')->where('unionid',$unionid)->select();
        foreach ($rlist as $keys => $vals){
            $data['uid'] = $uid;
            $data['apply_id'] = $vals['id'];
            $data['unionid'] = $unionid;
            $data['status'] = $status;
            $data['beizhu'] = $content;
            $data['apply_uid'] = $this->admin_id;
            $data['apply_time'] = date('Y-m-d H:i:s',time());
            if(Db::name('train_record')->insert($data)){
                $datas['status'] = $status;
                $datas['content'] = $content;
                //$datas['apply_time'] = time();
                Db::name('train_apply')->where('unionid',$unionid)->update($datas);
                
            }
        }
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        return json_encode($rs_arr,true);
        exit;
    
        
    }

}
