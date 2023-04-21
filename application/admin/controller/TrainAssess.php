<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
use think\facade\Request;


//实例化默认模型
use app\common\model\TrainAssess as TA;

class TrainAssess extends Base
{
    
    protected $validate = 'TrainAssess';
    
     //权限列表
    public function index(){
       
        $keyword = Request::param('keyword');
        $catid = Request::param('catid');
        
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        if(!empty($catid)){
            $where[] = ['ta.twoid' ,'=', $catid];
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        //调取列表
        $list = Db::name('train_assess')
            ->alias('ta')
            ->leftJoin('train_cate tc1','tc1.id = ta.oneid')
            ->leftJoin('train_cate tc2','tc2.id = ta.twoid')
            ->leftJoin('users u','u.id = ta.uid')
            ->field('ta.*,u.username as username,tc1.title as onename,tc2.title as twoname')
            ->order('ta.id DESC')
            ->where($where)
            ->select();
       
        foreach ($list as $key => $val){
                
            $whra['uid'] = $val['uid'];
            $whra['leixing'] = 1;
            $clist = Db::name('cateuser')->field('catid')
            ->where($whra)
            ->select();
            if(is_array($clist)){
                foreach ($clist as $keys => $vals){
                    
                    $group_name = self::select_name($vals['catid']);
                    
                    $arr = explode('/',$group_name);
                    $arrs = array_reverse($arr);
                    
                    $group_list = implode('/',$arrs);
                    $group_list = ltrim($group_list,'/');
                    $clist[$keys]['group_name'] = $group_list;
                } 
                
                $list[$key]['clist'] = $clist;
            }else{
                $list[$key]['clist'] = array();
            }
            
            
        }
        
        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $data_rt;
        return json_encode($rs_arr,true);
        exit;
    
    }
    
    
    public function select_name($id){
        $str = '';
        $whr['id'] = $id;
        $info = Db::name('cate')->field('id,parentid,title')->where($whr)->find();
        $str .= $info['title'].'/';
        
        if($id != 1){
            $str .= self::select_name($info['parentid']);
        }
        
        return $str;
    }
    

}
