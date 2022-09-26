<?php
/**
 * +----------------------------------------------------------------------
 * | 广告管理控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\Test as M;

class Test extends Base
{
    protected $validate = 'Test';
    
    //列表
    public function index(){

        $data = Request::param();

        $start = $data['start'];
        $end = $data['end'];
        $start = strtotime(date($start));
        $end = strtotime(date($end));
        $keyword = $data['keyword'];
        $catid = $data['catid'];
        $qid = $data['qid'];
        $status = $data['status'];
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        $whr['id'] = $this->admin_id;
        $gid = Db::name('users')->where($whr)->value('group_id');
        $ruless = Db::name('users')->where($whr)->value('ruless');
        if($gid == 1 || $gid == 2 || $gid == 7){
            if(!empty($catid)){
                $whra[]=['catid', '=', $catid];
                $uuid= Db::name('cateuser')->field('uid')->where($whra)->buildSql(true);
            }else{
                $whra[] = ['catid','in','1'];
                $uuid= Db::name('cateuser')->field('uid')->where($whra)->buildSql(true);
            }
        }else{
            if(!empty($catid)){
                if(in_array($catid,explode(',',$ruless))){
                    $whra[]=['catid', '=', $catid];
                    $uuid= Db::name('cateuser')->field('uid')->where($whra)->buildSql(true);
                }else{
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = '无权限';
                    return json_encode($rs_arr,true);
                    exit;
                }
            }else{
                $whra[] = ['catid','in',$ruless];
                $uuid= Db::name('cateuser')->field('uid')->where($whra)->buildSql(true);
            }
        }

        if(!empty($qid)){
            $where[]=['t.qid', '=', $qid];
        }
        if(!empty($status)){
            $where[]=['t.status', '=', $status];
        }

        if(isset($start)&&$start!=""&&isset($end)&&$end=="")
        {
            $where[] = ['t.update_time','>=',$start];
        }
        if(isset($end)&&$end!=""&&isset($start)&&$start=="")
        {
            $where[] = ['t.update_time','<=',$end];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end!="")
        {
            $where[] = ['t.update_time','between',[$start,$end]];
        }


        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;

        //调取列表
        $list = Db::name('test')
            ->alias('t')
            ->leftJoin('users u','t.uid = u.id')
            ->leftJoin('daxuetang d','t.qid = d.id')
            ->field('t.*,u.username as username,u.mobile as mobile,u.country as country,d.title as title,d.exam_name as exam_name,d.exam_name_beizhu as exam_name_beizhu')
            ->order('qid desc,score ASC,id ASC')
            ->where('t.uid','exp','In '.$uuid)
            ->where($where)
            ->select();

        foreach ($list as $key => $val){
            $whrb['uid'] = $val['uid'];
            $znum = Db::name('test')->where($whrb)->count();
            
            $zscore = Db::name('tiku')->sum('score');
            $whrbb['score'] = $zscore;
            $whrbb['uid'] = $val['uid'];
            $zfnum = Db::name('test')->where($whrbb)->count();
            if($zfnum > 0){
                $list[$key]['manfenlv'] = round($zfnum/$znum*100,2);
            }else{
                $list[$key]['manfenlv'] = 0;
            }

            $whrab['uid'] = $val['uid'];
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

            $list[$key]['clist'] = $clist;
            $list[$key]['score'] = floatval($val['score']);
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
        $info = Db::name('cate')->where($whr)->find();
        $str .= $info['title'].'/';

        if($id != 1){
            $str .= self::select_name($info['parentid']);
        }

        return $str;
    }

    public function zdlist(){
        $id = input('id');
        $qid = input('qid');

        $start = input('start');
        $end = input('end');
        $start = strtotime(date($start));
        $end = strtotime(date($end));

        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;

        $uinfo = Db::name('users')->where('id',$this->admin_id)->find();
        if($uinfo['group_id'] == 1 || $uinfo['group_id'] == 2 || $uinfo['group_id'] == 7){

            $where=[];
            if(!empty($id)){
                $where[]=['catid', '=', $id];
            }

            if(!empty($qid)){
                $where[]=['qid', '=', $qid];
            }

            if(isset($start)&&$start!=""&&isset($end)&&$end=="")
            {
                $where[] = ['update_time','>=',$start];
            }
            if(isset($end)&&$end!=""&&isset($start)&&$start=="")
            {
                $where[] = ['update_time','<=',$end];
            }
            if(isset($start)&&$start!=""&&isset($end)&&$end!="")
            {
                $where[] = ['update_time','between',[$start,$end]];
            }

            $list= Db::name('zdrecord')->where($where)->select();

            foreach ($list as $key => $val){
                //查询上级站点
                $whra['id'] = $val['catid'];
                $list[$key]['name'] =  Db::name('cate')->where($whra)->value('title');
                $sjid = Db::name('cate')->where($whra)->value('parentid');
                if($sjid > 0){
                    $whraa['id'] = $sjid;
                    $list[$key]['topname'] = Db::name('cate')->where($whraa)->value('title');
                }else{
                    $list[$key]['topname'] = '无';
                }
                //平均分
                if($val['number']>0){
                    $list[$key]['avg_score'] = round($val['score']/$val['number'],2);
                }
                //考试时间
                $whrb['id'] = $val['qid'];
                $list[$key]['kaohetime'] = Db::name('daxuetang')->where($whrb)->value('title');
                $list[$key]['exam_name'] =  Db::name('daxuetang')->where($whrb)->value('exam_name');
                $list[$key]['exam_name_beizhu'] =  Db::name('daxuetang')->where($whrb)->value('exam_name_beizhu');
            }
            
            $list = array_filter($list, function ($v) {
                return $v['name'];
            });
            
            $timeKey  = array_column($list,'avg_score');
            array_multisort($timeKey, SORT_ASC, $list);
        }else{

            $where=[];

            if(empty($id)){
                $where[]=['catid', 'in', $uinfo['ruless']];
            }else{
                if(in_array($id,explode(',',$uinfo['ruless']))){
                    $where[]=['catid', '=', $id];
                }else{
                    $data_rt['status'] = 201;
                    $data_rt['msg'] = '无权限';
                    return json_encode($data_rt,true);
                }
            }

            if(!empty($qid)){
                $where[]=['qid', '=', $qid];
            }
            if(isset($start)&&$start!=""&&isset($end)&&$end=="")
            {
                $where[] = ['update_time','>=',$start];
            }
            if(isset($end)&&$end!=""&&isset($start)&&$start=="")
            {
                $where[] = ['update_time','<=',$end];
            }
            if(isset($start)&&$start!=""&&isset($end)&&$end!="")
            {
                $where[] = ['update_time','between',[$start,$end]];
            }

            $list= Db::name('zdrecord')->where($where)->select();

            foreach ($list as $key => $val){

                //查询上级站点
                $whra['id'] = $val['catid'];
                $sjid = Db::name('cate')->where($whra)->value('parentid');
                $list[$key]['name'] =  Db::name('cate')->where($whra)->value('title');
                if($sjid > 0){
                    $whraa['id'] = $sjid;
                    $list[$key]['topname'] = Db::name('cate')->where($whraa)->value('title');
                }else{
                    $list[$key]['topname'] = '无';
                }
                //平均分
                if($val['number']>0){
                    $list[$key]['avg_score'] = round($val['score']/$val['number'],2);
                }
                //考试时间
                $whrb['id'] = $val['qid'];
                $list[$key]['kaohename'] =  Db::name('daxuetang')->where($whrb)->value('title');
                $list[$key]['exam_name'] =  Db::name('daxuetang')->where($whrb)->value('exam_name');
                $list[$key]['exam_name_beizhu'] =  Db::name('daxuetang')->where($whrb)->value('exam_name_beizhu');


            }
            
            $list = array_filter($list, function ($v) {
                return $v['name'];
            });
            
            $timeKey  = array_column($list,'avg_score');
            array_multisort($timeKey, SORT_ASC, $list);
        }



        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;

        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $data_rt;
        //print_r($list);
        return json_encode($rs_arr);
        exit;
    }
}
