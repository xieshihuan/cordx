<?php
/**
 * +----------------------------------------------------------------------
 * | 登录制器
 * +----------------------------------------------------------------------
 */
namespace app\api\controller;
use think\Controller;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

use app\common\model\Users as U;

class Users extends Base
{
    public function index(){
        //条件筛选
        $keyword = Request::param('keyword');
        $qid = Request::param('qid');
        
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['username|mobile', 'like', '%'.$keyword.'%'];
        }
        
        $where[]=['id', 'neq', $this->user_id];
        
        $where[]=['id', '>', 1];
        
        $where[]=['is_delete', '=', 1];
        
        
        $whra['uid'] = $this->user_id;
        $whra['qid'] = $qid;
        
        $otherid= Db::name('assess')->field('otherid')->where($whra)->buildSql(true);
        
        //调取列表
        $list = Db::name('users')
            ->field('id,username,mobile')
            ->order('username asc,id ASC')
            ->where('id','exp','not in '.$otherid)
            ->where($where)
            ->select();

        foreach ($list as $key => $val){
            $whrq['uid'] = $this->user_id;
            $whrq['otherid'] = $val['id'];
            $whrq['qid'] = $qid;
            $whrq['type'] = 2;
            $is_cunzai = Db::name('assess')
            ->where($whrq)
            ->count();
            if($is_cunzai > 0){
                $list[$key]['is_cunzai'] = 1;
            }else{
                $list[$key]['is_cunzai'] = 0;
            }
            
            $list[$key]['mobile'] = substr_replace($val['mobile'],'****',-8,-4);
        }
        
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $list;
		return json_encode($rs_arr,true);
		exit;
    }
    
    
    // 验证并重置密码
    public function resetPassword(){

        $password = $this->request->param('password');
        
        if(empty($password)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请输入新密码';
            return json_encode($rs_arr,true);
            exit;
        }
        
        $where['id'] = $this->user_id;
        $uinfo = Db::name('users')->where($where)->find();
        if($uinfo){
            
            $dataout['id'] = $uinfo['id'];
            $dataout['password'] = md5($password.'core2022');

            $m = new U();
            $result = $m->editPost($dataout);
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
            
        }else{

            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '该账户不存在，请联系管理员！';
            return json_encode($rs_arr,true);
            exit;
        
        }

    }
    
    public function assess(){
        //条件筛选
        $qid = Request::param('qid');
        $uid = Request::param('uid');
        
        
        $where = [];
        $wheres = [];
        if(!empty($qid)){
             $where[]=['qid', 'eq', $qid];
             $wheres[]=['qid', 'eq', $qid];
        }
       
        if(!empty($uid)){
            $where[]=['uid', 'eq', $uid];
            $wheres[]=['otherid', 'eq', $uid];
        }else{
            $where[]=['uid', 'eq', $this->user_id];
            $wheres[]=['otherid', 'eq', $this->user_id];
        }
        
        $where[]=['type', 'eq', 1];
        $where[]=['is_tijiao', 'eq', 1];
        
        $wheres[]=['type', 'eq', 2];
        $wheres[]=['is_tijiao', 'eq', 1];
        
        //调取列表
        $list = Db::name('assess')
            ->order('id desc')
            ->where($where)
            ->select();
            foreach ($list as $key => $val){
                $list[$key]['answer'] = json_decode($val['answer'],true);
                $list[$key]['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
                $list[$key]['update_time'] = date('Y-m-d H:i:s',$val['update_time']);
                $list[$key]['exam_name'] = Db::name('daxuetang')->where('id',$val['qid'])->value('exam_name');
            }
            
        $lists = Db::name('assess')
            ->order('id desc')
            ->where($wheres)
            ->select();
            
            foreach ($lists as $key => $val){
                $lists[$key]['answer'] = json_decode($val['answer'],true);
                $lists[$key]['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
                $lists[$key]['update_time'] = date('Y-m-d H:i:s',$val['update_time']);
                $lists[$key]['exam_name'] = Db::name('daxuetang')->where('id',$val['qid'])->value('exam_name');
            }
            
        $data_rt['zlist'] = $list;
        $data_rt['otherlist'] = $lists;

        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
		return json_encode($rs_arr,true);
		exit;
    }
    
    public function device_status(){
        $status = Request::param('status');
        $data['device_status'] = $status;
        
        Db::name('users')->where('id',$this->user_id)->update($data);
        
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		return json_encode($rs_arr,true);
		exit;
    }
    
    //查询设备统计
    public function zichan(){
        $where['id'] = $this->user_id;
        $uinfo = Db::name('users')->field('id,username,mobile,group_device,train_ruless')->where($where)->find();
        
        $wherec = [];
     
        $whra['uid'] = $this->user_id;
        $whra['leixing'] = 1;
        $clist = Db::name('cateuser')
        ->where($whra)
        ->select();
        
        if(count($clist) > 0){
            foreach ($clist as $keys => $vals){
                if($vals['catid'] > 0){
                    $group_name = self::select_name($vals['catid']);
                    $arr = explode('/',$group_name);
                    $arrs = array_reverse($arr);
                    $group_list = implode(' | ',$arrs);
                    $group_list = ltrim($group_list,' | ');
                    $clist[$keys]['group_name'] = $group_list;
                }else{
                    $clist[$keys]['group_name'] = '';
                }
            } 
        }else{
            $clist = array();
        }
        
        $data['clist'] = $clist;
        
        
        $data['username'] = $uinfo['username'];
        $data['mibile'] = $uinfo['mobile'];
        $data['group_id'] = $uinfo['group_device'];
        
        
        
        //我的资产汇总
        $user_zichan_count = Db::name('product')->where('uid',$this->user_id)->count();
        //我的资产详细
        $user_zichan = Db::name('product_cate')->field('id,title')->order('id asc')->select();
        foreach ($user_zichan as $key => $val){
            $whr['cate_id'] = $val['id'];
            $whr['uid'] = $this->user_id;
            $user_zichan[$key]['number'] = Db::name('product')->where($whr)->count();
        }
        
        if(!empty($zhandian_id)){
            if($uinfo['id'] == 1){
            
                //查询当前站点及所属下级站点
                $cate = Db::name('cate')->select();
                $xz = getChildsId($cate,$zhandian_id);
                
                $itemz = '';
                foreach($xz as $valxz){
                    $itemz .= $valxz['id'].',';
                }
                $idxzs = $itemz.$zhandian_id;
                $wherec[] = ['zhandian_id','in',$idxzs];
            
            }else{
                $ruless = explode(',',$uinfo['train_ruless']);
               
                if(in_array($zhandian_id,$ruless)){
                    $wherec[] = ['zhandian_id','=',$zhandian_id];
                }else{
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = '站点id有误';
                    return json_encode($rs_arr,true);
                    exit;
                }
            }
        }else{
            if($uinfo['id'] == 1){
                $wherec[] = ['zhandian_id','>',0];
            }else{
                $ruless = explode(',',$uinfo['train_ruless']);
                $wherec[] = ['zhandian_id','in',$uinfo['train_ruless']];
            }
        }
        
        //站点资产汇总
        $zhandian_zichan_count = Db::name('product')->where($wherec)->count();
        //站点资产详细
        $zhandian_zichan = Db::name('product_cate')->field('id,title')->order('id asc')->select();
        foreach ($zhandian_zichan as $key => $val){
            $whr_zhan['cate_id'] = $val['id'];
            $zhandian_zichan[$key]['number'] = Db::name('product')->where($whr_zhan)->where($wherec)->count();
        }
        
        $data['user_zichan_count'] = $user_zichan_count;
        $data['user_zichan_list'] = $user_zichan;
        $data['zhandian_zichan_count'] = $zhandian_zichan_count;
        $data['zhandian_zichan'] = $zhandian_zichan;
        
        
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data;
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
    
    //绑定openid
    public function bind_openid(){
        $openid = Request::param('openid');
        $data['openid'] = $openid;
        $data['uid'] = $this->user_id;
        
        Db::name('weixin')->insert($data);
        echo apireturn(200,'success','');die;
    }
    
    
}