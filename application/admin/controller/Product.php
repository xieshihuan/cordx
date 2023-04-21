<?php
/**
 * +----------------------------------------------------------------------
 * | 新闻管理控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use app\common\model\Cate;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\Product as M;

class Product extends Base
{
    protected $validate = 'Product';
    
    //列表
    public function pindex(){

        //条件筛选
        $keyword = Request::param('keyword');
        $cate_id = Request::param('cate_id');
        $type_id = Request::param('type_id');
        $zhandian_id = Request::param('zhandian_id');
        $status = Request::param('status');
        $group = Request::param('group');

        $start = Request::param('start');
        $end = Request::param('end');
        $start = strtotime(date($start));
        $end = strtotime(date($end));
        $items = Request::param('items');
        //全局查询条件
        $where=[];

        if(!empty($cate_id)){
            $where[]=['p.cate_id', '=', $cate_id];
        } 
        if(!empty($type_id)){
            $where[]=['p.type_id', '=', $type_id];
        }
        if(!empty($status)){
            $where[]=['p.status', '=', $status];
        }
        
        $uinfo = Db::name('users')->where('id',$this->admin_id)->find();
        
        //超级管理员
        if($this->admin_id == 1){
            if(!empty($zhandian_id)){
                
                //查询当前站点及所属下级站点
                $cate = Db::name('cate')->select();
                $xz = getChildsId($cate,$zhandian_id);
                
                $itemz = '';
                foreach($xz as $valxz){
                    $itemz .= $valxz['id'].',';
                }
                $idxzs = $itemz.$zhandian_id;
                $where[] = ['p.zhandian_id','in',$idxzs];
                
            }
        }else{
            if(!empty($zhandian_id)){
                
                $rules = trim($uinfo['train_ruless'],',');
                if(in_array($zhandian_id,explode(',',$rules))){
                    //查询当前站点及所属下级站点
                    $cate = Db::name('cate')->select();
                    $xz = getChildsId($cate,$zhandian_id);
                    
                    $itemz = '';
                    foreach($xz as $valxz){
                        $itemz .= $valxz['id'].',';
                    }
                    $idxzs = $itemz.$zhandian_id;
                    $where[] = ['p.zhandian_id','in',$idxzs];
                }else{
                    $data_rt['status'] = 200;
                    $data_rt['msg'] = '无权限';
                    $data_rt['data'] = array();
                    return json_encode($data_rt,true);
                }
                
                //$where[]=['p.zhandian_id', '=', $zhandian_id];
            }else{
                $where[]=['p.zhandian_id', 'in', $uinfo['train_ruless']];
            }
        }
                
        if(isset($start)&&$start!=""&&isset($end)&&$end=="")
        {
            $where[] = ['p.collect_time','>=',$start];
        }
        if(isset($end)&&$end!=""&&isset($start)&&$start=="")
        {
            $where[] = ['p.collect_time','<=',$end];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end!="")
        {
            $where[] = ['p.collect_time','between',[$start,$end]];
        }

        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        if(!empty($keyword)){
            $whra[]=['username|mobile', 'like', '%'.$keyword.'%'];

            $uuid= Db::name('users')->field('id')->where($whra)->buildSql(true);
            //根据所有人的id in查询所有的成绩

            if(!empty($items)){
                $items = trim($items,'^');
                $itemids = explode('^',$items);
                $ids = '';
                foreach($itemids as $val){
                    
                    $item = sellist($ids,$val);
                    
                    $itemq = '';
                    foreach($item as $vals){
                        $itemq .= $vals['product_id'].',';
                    }
                    
                    $itemq = rtrim($itemq,',');
                    
                    $whrs[] = ['product_id','in',$itemq];
                }
                
                $itemid = Db::name('product_relation')->field('product_id')->where($whrs)->buildSql(true);

                $list = Db::name('product')
                    ->alias('p')
                    ->leftJoin('product_cate cate','p.cate_id = cate.id')
                    ->leftJoin('product_type type','p.type_id = type.id')
                    ->field('p.*,cate.title as cate_name,type.title as type_name')
                    ->order('p.status asc,p.create_time desc,p.id DESC')
                    ->where($where)
                    ->where('p.uid','exp','In '.$uuid)
                    ->where('p.id','exp', 'In '.$itemid)
                    ->select();
            }else{
                $list = Db::name('product')
                    ->alias('p')
                    ->leftJoin('product_cate cate','p.cate_id = cate.id')
                    ->leftJoin('product_type type','p.type_id = type.id')
                    ->field('p.*,cate.title as cate_name,type.title as type_name')
                    ->order('p.status asc,p.create_time desc,p.id DESC')
                    ->where($where)
                    ->where('p.uid','exp','In '.$uuid)
                    ->select();
            }
        }else{
            if(!empty($items)){
                
                //筛选
                $items = trim($items,'^');
                $itemids = explode('^',$items);
                
                $ids = '';
                    
                foreach($itemids as $val){
                  
                    $item = sellist($ids,$val);
                    
                    $itemq = '';
                    foreach($item as $vals){
                        $itemq .= $vals['product_id'].',';
                    }
                    
                    $itemq = rtrim($itemq,',');
                    
                    $whrs[] = ['product_id','in',$itemq];
                }
                $itemid = Db::name('product_relation')->field('product_id')->where($whrs)->buildSql(true);

                $list = Db::name('product')
                    ->alias('p')
                    ->leftJoin('product_cate cate','p.cate_id = cate.id')
                    ->leftJoin('product_type type','p.type_id = type.id')
                    ->field('p.*,cate.title as cate_name,type.title as type_name')
                    ->order('p.status asc,p.create_time desc,p.id DESC')
                    ->where($where)
                    ->where('p.id','exp','In '.$itemid)
                    ->select();
            }else {
                
                $list = Db::name('product')
                    ->alias('p')
                    ->leftJoin('product_cate cate', 'p.cate_id = cate.id')
                    ->leftJoin('product_type type', 'p.type_id = type.id')
                    ->field('p.*,cate.title as cate_name,type.title as type_name')
                    ->order('p.status asc,p.create_time desc,p.id DESC')
                    ->where($where)
                    ->select();
            }
        }
        
        foreach ($list as $keys => $vals){
            
                $group_id = Db::name('users')->where('id',$vals['zhandian_uid'])->value('group_device');
                
                $list[$keys]['submit_group_id'] = $group_id;
                if($group_id > 0){
                    $list[$keys]['submit_group_name'] = Db::name('auth_group')->where('id',$group_id)->value('title');
                }else{
                    $list[$keys]['submit_group_name'] = '普通用户';
                }
        }
        
        
        if($group < 99){
            $list = seacharr_by_value($list,'submit_group_id',$group);
        }
        
        
        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        
        foreach($list as $keys => $vals){
            //$group_name = self::select_name($vals['zhandian_id']);
            //$arr = explode('/',$group_name);
            //$arrs = array_reverse($arr);
            //$group_list = implode('/',$arrs);
            //$group_list = ltrim($group_list,'/');
            //$list[$keys]['zhandian_name'] = $group_list;
            
            $list[$keys]['zhandian_name'] = Db::name('cate')->where('id',$vals['zhandian_id'])->value('title');
            $list[$keys]['username'] = Db::name('users')->where('id',$vals['uid'])->value('username');
            $list[$keys]['mobile'] = Db::name('users')->where('id',$vals['uid'])->value('mobile');
            $list[$keys]['submit_username'] = Db::name('users')->where('id',$vals['zhandian_uid'])->value('username');
        }
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
    
    public function pdetail(){
        $id = Request::param('id');
        
        //全局查询条件
        $where=[];
        $wheres=[];

        if(!empty($id)){
            $where[]=['pr.product_id', '=', $id];
            $wheres[]=['product_id', '=', $id];
        }else{
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择设备';
    		return json_encode($rs_arr,true);
    		exit;
        }
        
        //查询参数列表
        $itemlist = Db::name('product_relation')
                ->alias('pr')
                ->leftJoin('spec pc','pr.spec_id = pc.id')
                ->leftJoin('spec_item pt','pr.result = pt.id')
                ->field('pr.*,pc.title as spec_name,pc.leixing as leixing,pt.item as spec_item_name')
                ->where($where)
                ->order('pr.id asc')
                ->select();
        //查询修改记录
        $updlist = Db::name('product_update')
                ->where($wheres)
                ->order('create_time desc')
                ->select();
                
                
        $data_rt['itemlist'] = $itemlist;
        $data_rt['updlist'] = $updlist;
        
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
		return json_encode($rs_arr,true);
		exit;
    }
    
    
    
    //列表
    public function useless_list(){

        //条件筛选
        $keyword = Request::param('keyword');
        $cate_id = Request::param('cate_id');
        $type_id = Request::param('type_id');
        $zhandian_id = Request::param('zhandian_id');
        $status = Request::param('status');
        
        $start = Request::param('start');
        $end = Request::param('end');
        $start = strtotime(date($start));
        $end = strtotime(date($end));
        $items = Request::param('items');
        //全局查询条件
        $where=[];

        if(!empty($cate_id)){
            $where[]=['p.cate_id', '=', $cate_id];
        } 
        if(!empty($type_id)){
            $where[]=['p.type_id', '=', $type_id];
        }
        if(!empty($status)){
            $where[]=['pa.status', '=', $status];
        }
        
        $uinfo = Db::name('users')->where('id',$this->admin_id)->find();
        
        //超级管理员
        if($this->admin_id == 1){
            if(!empty($zhandian_id)){
                
                //查询当前站点及所属下级站点
                $cate = Db::name('cate')->select();
                $xz = getChildsId($cate,$zhandian_id);
                
                $itemz = '';
                foreach($xz as $valxz){
                    $itemz .= $valxz['id'].',';
                }
                $idxzs = $itemz.$zhandian_id;
                $where[] = ['p.zhandian_id','in',$idxzs];
                
            }else{
                $where[] = ['p.zhandian_id','>',0];
            }
        }else{
            
            if(!empty($zhandian_id)){
                
                $rules = trim($uinfo['train_ruless'],',');
                if(in_array($zhandian_id,explode(',',$rules))){
                    //查询当前站点及所属下级站点
                    $cate = Db::name('cate')->select();
                    $xz = getChildsId($cate,$zhandian_id);
                    
                    $itemz = '';
                    foreach($xz as $valxz){
                        $itemz .= $valxz['id'].',';
                    }
                    $idxzs = $itemz.$zhandian_id;
                    $where[] = ['p.zhandian_id','in',$idxzs];
                }else{
                    $data_rt['status'] = 200;
                    $data_rt['msg'] = '无权限';
                    $data_rt['data'] = array();
                    return json_encode($data_rt,true);
                }
                
                //$where[]=['p.zhandian_id', '=', $zhandian_id];
            }else{
                $where[]=['p.zhandian_id', 'in', $uinfo['train_ruless']];
            }
        }
                
        if(isset($start)&&$start!=""&&isset($end)&&$end=="")
        {
            $where[] = ['pa.create_time','>=',$start];
        }
        if(isset($end)&&$end!=""&&isset($start)&&$start=="")
        {
            $where[] = ['pa.create_time','<=',$end];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end!="")
        {
            $where[] = ['pa.create_time','between',[$start,$end]];
        }

        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;


        if(!empty($keyword)){
            $whra[]=['username|mobile', 'like', '%'.$keyword.'%'];

            $uuid= Db::name('users')->field('id')->where($whra)->buildSql(true);
            //根据所有人的id in查询所有的成绩

            if(!empty($items)){
                $items = trim($items,'^');
                $itemids = explode('^',$items);
                $ids = '';
                foreach($itemids as $val){
                    
                    $item = sellist($ids,$val);
                    
                    $itemq = '';
                    foreach($item as $vals){
                        $itemq .= $vals['product_id'].',';
                    }
                    
                    $itemq = rtrim($itemq,',');
                    
                    $whrs[] = ['product_id','in',$itemq];
                }
                
                $itemid = Db::name('product_relation')->field('product_id')->where($whrs)->buildSql(true);
                
                $list = Db::name('product_apply')
                    ->alias('pa')
                    ->leftJoin('product p','pa.product_id = p.id')
                    ->field('pa.*,pa.create_time as add_time,pa.status as useless_status,p.type_id,p.cate_id,p.zhandian_uid,p.zhandian_id')
                    ->order('pa.status asc,add_time asc')
                    ->where($where)
                    ->where('pa.uid','exp','In '.$uuid)
                    ->where('p.id','exp', 'In '.$itemid)
                    ->select();
            }else{
                
                $list = Db::name('product_apply')
                    ->alias('pa')
                    ->leftJoin('product p','pa.product_id = p.id')
                    ->field('pa.*,pa.create_time as add_time,pa.status as useless_status,p.type_id,p.cate_id,p.zhandian_uid,p.zhandian_id')
                    ->order('pa.status asc,add_time asc')
                    ->where($where)
                    ->where('pa.uid','exp','In '.$uuid)
                    ->select();
            }
        }else{
            if(!empty($items)){
                
                //筛选
                $items = trim($items,'^');
                $itemids = explode('^',$items);
                
                $ids = '';
                    
                foreach($itemids as $val){
                  
                    $item = sellist($ids,$val);
                    
                    $itemq = '';
                    foreach($item as $vals){
                        $itemq .= $vals['product_id'].',';
                    }
                    
                    $itemq = rtrim($itemq,',');
                    
                    $whrs[] = ['product_id','in',$itemq];
                }
                $itemid = Db::name('product_relation')->field('product_id')->where($whrs)->buildSql(true);
                
                $list = Db::name('product_apply')
                    ->alias('pa')
                    ->leftJoin('product p','pa.product_id = p.id')
                    ->field('pa.*,pa.create_time as add_time,pa.status as useless_status,p.type_id,p.cate_id,p.zhandian_uid,p.zhandian_id')
                    ->order('pa.status asc,add_time asc')
                    ->where($where)
                    ->where('p.id','exp','In '.$itemid)
                    ->select();
            }else{
                
                $list = Db::name('product_apply')
                    ->alias('pa')
                    ->leftJoin('product p','pa.product_id = p.id')
                    ->field('pa.*,pa.create_time as add_time,pa.status as useless_status,p.type_id,p.cate_id,p.zhandian_uid,p.zhandian_id')
                    ->order('pa.status asc,add_time asc')
                    ->where($where)
                    ->select();
            }
        }
        
        foreach ($list as $keys => $vals){
            $group_name = self::select_name($vals['zhandian_id']);
                $arr = explode('/',$group_name);
                $arrs = array_reverse($arr);
                $group_list = implode('/',$arrs);
                $group_list = ltrim($group_list,'/');
                $list[$keys]['zhandian_name'] = $group_list;
                $list[$keys]['username'] = Db::name('users')->where('id',$vals['uid'])->value('username');
                $list[$keys]['mobile'] = Db::name('users')->where('id',$vals['uid'])->value('mobile');
                $list[$keys]['submit_username'] = Db::name('users')->where('id',$vals['zhandian_uid'])->value('username');
                $list[$keys]['submit_group_id'] = Db::name('users')->where('id',$vals['zhandian_uid'])->value('group_id');
                $list[$keys]['cate_name'] = Db::name('product_cate')->where('id',$vals['cate_id'])->value('title');
                $list[$keys]['type_name'] = Db::name('product_type')->where('id',$vals['type_id'])->value('title');
                
                $list[$keys]['apply_content_list'] = explode("\n",$vals['apply_content']);
               
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
    
    //查看报废详情
    public function useless_detail(){
        $id = Request::param('id');
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择报废记录';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $where['p.id'] = $id;
        }
        
         //$where['p.uid'] = $this->admin_id;  ->where('reply_uid',$this->admin_id)
        
        $painfo = Db::name('product_apply')->where('id',$id)->find();
       
        if($painfo){
            $where['p.id'] = $painfo['product_id'];
        
            $pinfo = Db::name('product')
                ->alias('p')
                ->leftJoin('product_cate pc','p.cate_id = pc.id')
                ->leftJoin('product_type pt','p.type_id = pt.id')
                ->leftJoin('users u','p.uid = u.id')
                ->leftJoin('cate c','p.zhandian_id = c.id')
                ->leftJoin('product_flow pf','p.id = pf.product_id')
                ->field('p.id,p.status,p.uid,p.is_new,p.leixing,p.reason,p.collect_time,p.cate_id,p.type_id,p.zhandian_id,p.json,pc.title as catename,pt.title as typename,u.username as name,c.title as zhandian_name,pf.apply_status as apply_status,pf.apply_content as apply_content')
                ->where($where)
                ->find();
            
            if($pinfo['status'] == 1){
                $pinfo['status_name'] = '在用';
            }else if($pinfo['status'] == 2){
                $pinfo['status_name'] = '闲置';
            }else{
                $pinfo['status_name'] = '报废';
            }
            if($pinfo['is_new'] == 1){
                $pinfo['is_new_name'] = '全新';
            }else{
                $pinfo['is_new_name'] = '非全新';
            }  
            if($pinfo['leixing'] == 1){
                $pinfo['leixing_name'] = '使用';
            }else{
                $pinfo['leixing_name'] = '管理';
            }  
            if($painfo['status'] == 1){
                $pinfo['useless_state_name'] = '待审核';
            }else if($painfo['status'] == 2){
                $pinfo['useless_state_name'] = '已通过';
            }else{
                $pinfo['useless_state_name'] = '已驳回';
            }
            
            
            $whr1['pr.product_id'] = $painfo['product_id'];
            $item_list = Db::name('product_relation')
                ->alias('pr')
                ->leftJoin('spec pc','pr.spec_id = pc.id')
                ->leftJoin('spec_item pt','pr.result = pt.id')
                ->field('pr.*,pc.title as spec_name,pc.leixing,pt.item as spec_item_name')
                ->where($whr1)
                ->order('pr.id asc')
                ->select();
            foreach ($item_list as $key => $val){
                if($val['leixing'] == 2){
                    $item_list[$key]['spec_item_name'] = $val['result'];
                }
            }
        
            $pinfo['item_list'] = $item_list;
            $pinfo['useless_reason'] = $painfo['apply_reason'];
            $pinfo['useless_content'] = $painfo['apply_content'];
            $pinfo['useless_status'] = $painfo['status'];
            $pinfo['ctime'] = date("Y-m-d",$pinfo['collect_time']);
            $pinfo['apply_content_list'] = explode("\n",$painfo['apply_content']);
            
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = 'success';
            $rs_arr['data'] = $pinfo;
            return json_encode($rs_arr,true);
            exit;
        }else{
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '暂无记录';
            return json_encode($rs_arr,true);
            exit;
        }
        
        
    }
    
    //处理报废申请
    public function useless_apply(){
        $id = Request::param('id');
        $status = Request::param('status');
        $reply_content = Request::param('reply_content');
        
        if(!empty($id)){
            $where[] = ['id','=',$id];
        }
        $where[] = ['reply_uid','=',$this->admin_id];
        
        $info = Db::name('product_apply')->where($where)->find();

        if(!$info){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '无权限';
            return json_encode($rs_arr,true);
            exit;
        }else{
            if($info['status'] == 2){
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '已通过！';
                return json_encode($rs_arr,true);
                exit;
            }elseif($info['status'] == 3){
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '已驳回！';
                return json_encode($rs_arr,true);
                exit;
            }else{
                $data['status'] = $status;
                $data['reply_content'] = $reply_content;
                if(Db::name('product_apply')->where($where)->update($data)){
                    if($status == 2){
                        $dataz['status'] = 3;
                        $dataz['useless_time'] = time();
                        Db::name('product')->where('id',$info['product_id'])->update($dataz);
                    }
                    $rs_arr['status'] = 200;
                    $rs_arr['msg'] = 'success';
                    return json_encode($rs_arr,true);
                    exit;
                }else{
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = 'fail';
                    return json_encode($rs_arr,true);
                    exit;
                }  
            }
        }
    }
    
     //单一设备报废记录
    public function useless_apply_list(){
        $id = Request::param('id');
        
        $where = [];
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择产品';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $where[] = ['pa.product_id','=',$id];
        }

        $list = Db::name('product_apply')
            ->alias('pa')
            ->leftJoin('users u','pa.reply_uid = u.id')
            ->field('pa.*,u.username as reply_name')->where($where)->order('update_time desc')->select();
   
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $list;
        return json_encode($rs_arr,true);
        exit;
    }
    
    
     //删除
    public function delPost(){
        
        $id = Request::param('id');
        
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择产品';
            return json_encode($rs_arr,true);
            exit;
        }
        
        $info = Db::name('product')->where('id',$id)->find();
        
        if($info){
            
            //添加删除记录
            $data['cate_id'] = $info['cate_id'];
            $data['type_id'] = $info['type_id'];
            $data['uid'] = $info['uid'];
            $data['zhandian_uid'] = $info['zhandian_uid'];
            $data['zhandian_id'] = $info['zhandian_id'];
            $data['json'] = $info['json'];
            $data['status'] = $info['status'];
            $data['reason'] = $info['reason'];
            $data['leixing'] = $info['leixing'];
            $data['is_lock'] = $info['is_lock'];
            $data['is_new'] = $info['is_new'];
            $data['collect_time'] = $info['collect_time'];
            $data['remark'] = $info['remark'];
            $data['delete_uid'] = $this->admin_id;
            $data['create_time'] = $info['create_time'];
            $data['update_time'] = $info['update_time'];
            $data['delete_time'] = time();
            $data['useless_time'] = $info['useless_time'];
            
            $ins = Db::name('product_delete')->insert($data);
            if($ins){
                 //删除修改记录
                Db::name('product_update')->where('product_id',$id)->delete();
                //删除流转记录
                Db::name('product_flow')->where('product_id',$id)->delete();
                //删除操作记录
                Db::name('product_apply')->where('product_id',$id)->delete();
                //删除关联记录
                Db::name('product_relation')->where('product_id',$id)->delete();
                //增加消息
                $adminname = Db::name('users')->where('id',$this->admin_id)->value('username');
                $datam['uid'] = $info['uid'];
                $datam['content'] = $adminname.'将您使用的 '.Db::name('product_cate')->where('id',$info['cate_id'])->value('title').' '.Db::name('product_type')->where('id',$info['type_id'])->value('title').' 删除';
                $datam['status'] = 1;
                $datam['create_time'] = date('Y-m-d H:i:s',time());
                Db::name('message')->insert($datam);
                //删除设备
                Db::name('product')->where('id',$id)->delete();
                
                
                
                $rs_arr['status'] = 200;
                $rs_arr['msg'] = 'success';
                return json_encode($rs_arr,true);
                exit;
                
            }else{
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = 'faild';
                return json_encode($rs_arr,true);
                exit;
            }
        }else{
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = 'not found';
            return json_encode($rs_arr,true);
            exit;
        }
    }
}
