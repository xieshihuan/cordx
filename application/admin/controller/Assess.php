<?php
/**
 * +----------------------------------------------------------------------
 * | 广告管理控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use app\common\model\AdType;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\Assess as M;

class Assess extends Base
{
    protected $validate = 'Assess';

    //列表
    public function index(){
        
        $data = Request::param();
        
        $username = Request::param('username');
        $start = Request::param('start');
        $end = Request::param('end');
        $start = strtotime(date($start));
        $end = strtotime(date($end));
        $catid = Request::param('catid');
        $qid = Request::param('qid');
        $type = Request::param('type');
        $one = Request::param('one');
        $two = Request::param('two');
        $three = Request::param('three');
        $beizhu = Request::param('beizhu');
        
        //全局查询条件
        $where=[];
        if(!empty($username)){
            $where[]=['a.usernames', 'like', '%'.$username.'%'];
        }
        if(!empty($qid)){
            $where[]=['a.qid', '=', $qid];
        } 
        if(!empty($type)){
            $where[]=['a.type', '=', $type];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end=="")
        {
            $where[] = ['a.update_time','>=',$start];
        }
        if(isset($end)&&$end!=""&&isset($start)&&$start=="")
        {
            $where[] = ['a.update_time','<=',$end];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end!="")
        {
            $where[] = ['a.update_time','between',[$start,$end]];
        }
        
        if(!empty($beizhu)){
            if($beizhu == 2){
                $where[]=['a.beizhu', '=', null];
            }else{
                $where[]=['a.beizhu', 'not in', null];
            }
        }
        
        $where[] = ['a.is_tijiao','=',1];
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;
        $a = $page-1;
        $b = $a * $pageSize;
        
        $whr['id'] = $this->admin_id;
        $ruless = Db::name('users')->where($whr)->value('ruless');
        if($this->admin_id == 1){
            if(!empty($catid)){
                $whra[]=['catid', '=', $catid];
                $uuid= Db::name('cateuser')->field('uid')->where($whra)->buildSql(true);
                $list = Db::name('assess')->alias('a')
                ->leftJoin('daxuetang d','a.qid = d.id')
                ->field('a.*,d.exam_name as exam_name,d.exam_name_beizhu as exam_name_beizhu')
                ->where('a.otherid','exp','In '.$uuid)
                ->where($where)
                ->order('a.qid desc,a.update_time desc')
                ->select();
            }else{
                $whra[] = ['catid','in','1'];
                $uuid= Db::name('cateuser')->field('uid')->where($whra)->buildSql(true);
                $list = Db::name('assess')->alias('a')
                ->leftJoin('daxuetang d','a.qid = d.id')
                ->field('a.*,d.exam_name as exam_name,d.exam_name_beizhu as exam_name_beizhu')
                ->where('a.otherid','exp','In '.$uuid)
                ->where($where)
                ->order('a.qid desc,a.update_time desc')
                ->select();
            }
        }else{
            if(!empty($catid)){
                if(in_array($catid,explode(',',$ruless))){
                    $whra[]=['catid', '=', $catid];
                    $uuid= Db::name('cateuser')->field('uid')->where($whra)->buildSql(true);
                    $list = Db::name('assess')->alias('a')
                    ->leftJoin('daxuetang d','a.qid = d.id')
                    ->field('a.*,d.exam_name as exam_name,d.exam_name_beizhu as exam_name_beizhu')
                    ->where('a.otherid','exp','In '.$uuid)
                    ->where($where)
                    ->order('a.qid desc,a.update_time desc')
                    ->select();
                }else{
                    $rs_arr['status'] = 200;
                    $rs_arr['msg'] = '无权限';
                    $rs_arr['data'] = array();
                    return json_encode($rs_arr,true);
                    exit;
                }
            }else{
                $whra[] = ['catid','in',$ruless];
               
                $uuids = Db::name('cateuser')->field('uid')->where($whra)->buildSql(true);
                $list = Db::name('assess')->alias('a')
                ->leftJoin('daxuetang d','a.qid = d.id')
                ->field('a.*,d.exam_name as exam_name,d.exam_name_beizhu as exam_name_beizhu')
                ->where('a.otherid','exp','In '.$uuids)
                ->where($where)
                ->order('a.qid desc,a.update_time desc')
                ->select();
            }
        }
        
        foreach ($list as $key => $val){
            
            $answers = json_decode($val['answer'],true);
            $list[$key]['answers'] = $answers;
            $list[$key]['one'] = $answers[0]['assess_score'];
            $list[$key]['two'] = $answers[1]['assess_score'];
            $list[$key]['three'] = $answers[2]['assess_score'];
            
            if($val['beizhu'] != null){
                $list[$key]['beizhu'] = explode("\n",$val['beizhu']);
            }else{
                $list[$key]['beizhu'] = array();
            }
        }
        
        if(!empty($one)){
            $list = seacharr_by_value($list,'one',$one);
        } 
        if(!empty($two)){
            $list = seacharr_by_value($list,'two',$two);
        } 
        if(!empty($three)){
            $list = seacharr_by_value($list,'three',$three);
        }
        
        $data_rt['total'] = count($list);
        $lists = array_slice($list,$b,$pageSize);
        foreach($lists as $key => $val){
            if($val['otherid'] != 1){
                $whraa['uid'] = $val['otherid'];
                $whraa['leixing'] = 1;
                $clist = Db::name('cateuser')
                ->where($whraa)
                ->select();
                foreach ($clist as $keys => $vals){
                    $group_name = self::select_name($vals['catid']);
                    $arr = explode('/',$group_name);
                    $arrs = array_reverse($arr);
                    $group_list = ltrim(implode('/',$arrs),'/');
                    $clist[$keys]['group_name'] = $group_list;
                } 
            }else{
                $clist = array();
            }
            
            $lists[$key]['clist'] = $clist;
        }
        $data_rt['data'] = $lists;

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
    
}
