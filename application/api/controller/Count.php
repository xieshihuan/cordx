<?php
namespace app\api\controller;
use think\Db;
use think\facade\Request;

class Count extends Base
{

    //大学堂列表
    public function tlist(){


        $tlist = Db::name('daxuetang')->where('start', '<= time', time())->order('id asc')->select();

        if($tlist){
            echo apireturn(200,'success',$tlist);die;
        }else{
            $tlist = array();
            $data_rt['status'] = 200;
            $data_rt['msg'] = 'success';
            $data_rt['data'] = $tlist;
            return json_encode($data_rt);
            exit;

        }
    }

    //查看站点列表
    public function index(){

        $parentid = input('parentid');
        $qid = input('qid');
        
        //查题库id
        $tiku_id = Db::name('daxuetang')->where('id',$qid)->value('tiku_id');
        $tiku_score = Db::name('tiku')->where('tiku_id',$tiku_id)->sum('score');
        
        $uinfo = Db::name('users')->where('id',$this->user_id)->find();
        if($uinfo['group_id'] == 1 || $uinfo['group_id'] == 2 || $uinfo['group_id'] == 7){

            $where=[];
            if($parentid){
                $where[]=['parentid', '=', $parentid];
            }else{
                $where[] = ['parentid','=','1'];
            }
            if(empty($qid)){
                $nid = Db::name('daxuetang')->order('id desc')->value('id');
                if($nid){
                    $qid = $nid;
                }else{
                    $qid = 1;
                }
            }

            $list = Db::name('cate')->where($where)->order('sort asc')->select();


            foreach ($list as $key => $val){
                
                $zpnum = Db::name('cateuser')->where('catid',$val['id'])->count();
                
                $whra['catid'] = $val['id'];
                
                $whra['qid'] = $qid;
                
                $uuid = Db::name('zdrecord_list')->field('uid')->where($whra)->buildSql(true);

                $tlist = Db::name('test')->where('uid','exp','In '.$uuid)->where('qid',$qid)->where('is_tijiao',1)->select();

                //获取站点考试分数
                $zscore = Db::name('test')->where('uid','exp','In '.$uuid)->where('qid',$qid)->where('is_tijiao',1)->sum('score');

                //获取站点考试人数
                $pnumss = Db::name('zdrecord_list')->field('uid')->where($whra)->select();
                $ddss = '';
                foreach($pnumss as $keys => $vals){
                    $ddss .= $vals['uid'].',';
                }
                $ddsss = rtrim($ddss,',');

                $ddsss = explode(',',$ddsss);

                $ddsss = array_unique($ddsss);

                $dnumss = count($ddsss);

                $wheres['parentid'] = $val['id'];
                $zzlist = Db::name('cate')->where($wheres)->select();

                $num = count($tlist);
                if($zscore > 0){
                    $avg_score = round($zscore/$dnumss,2);
                }else{
                    $avg_score = 0;
                }

                if($parentid == 1){
                    $parent_ids = 1;
                    $parent_name = Db::name('cate')->where('id',1)->value('title');
                    $father_name = Db::name('cate')->where('id',1)->value('title');
                }else{
                    $parent_ids = Db::name('cate')->where('id',$val['parentid'])->value('parentid');
                    $parent_name = Db::name('cate')->where('id',$parent_ids)->value('title');
                    $father_name = Db::name('cate')->where('id',$val['parentid'])->value('title');

                }
                
                
                $list[$key]['father_name'] = $father_name;
                $list[$key]['parent_name'] = $parent_name;
                $list[$key]['num'] = $num;
                $list[$key]['zscore'] = $zscore;
                $list[$key]['avg_score'] = floatval($avg_score);
                $list[$key]['zznum'] = count($zzlist);
                $list[$key]['pnum'] = $zpnum;
                $list[$key]['tiku_score'] = $tiku_score;

                $list[$key]['parent_ids'] = $parent_ids;

                $ids = '';
                if(!empty($tlist)){
                    foreach ($tlist as $keys => $vals){
                        $ids .= $vals['uid'].',';
                    }
                    $ids = rtrim($ids,',');
                    $ids = '/'.$ids.'/';
                }else{
                    //$parent_ids
                    $ids = null;
                }

                $list[$key]['ids'] = $ids;

            }


        }else{

            $where=[];
            $where[] = ['id','in',$uinfo['ruless']];

            if(empty($qid)){
                $nid = Db::name('daxuetang')->order('id desc')->value('id');
                if($nid){
                    $qid = $nid;
                }else{
                    $qid = 1;
                }
            }

            $list = Db::name('cate')->where($where)->order('sort asc')->select();

            foreach ($list as $key => $val){

                $whra['catid'] = $val['id'];
                $uuid= Db::name('zdrecord_list')->field('uid')->where($whra)->buildSql(true);

                $tlist = Db::name('test')->where('uid','exp','In '.$uuid)->where('qid',$qid)->where('is_tijiao',1)->select();

                //获取站点考试总分
                $zscore = Db::name('test')->where('uid','exp','In '.$uuid)->where('qid',$qid)->where('is_tijiao',1)->sum('score');

                //获取站点考试人数
                $pnumss = Db::name('zdrecord_list')->field('uid')->where($whra)->select();
                $ddss = '';
                foreach($pnumss as $keys => $vals){
                    $ddss .= $vals['uid'].',';
                }
                $ddsss = rtrim($ddss,',');

                $ddsss = explode(',',$ddsss);

                $ddsss = array_unique($ddsss);

                $dnumss = count($ddsss);

                $parent_name = Db::name('cate')->where('id',$val['parentid'])->value('title');
                $father_name = Db::name('cate')->where('id',$val['id'])->value('title');
                $num = count($tlist);
                if($zscore > 0){
                    $avg_score = round($zscore/$dnumss,2);
                }else{
                    $avg_score = 0;
                }

                
                $list[$key]['father_name'] = $father_name;
                $list[$key]['parent_name'] = $parent_name;
                $list[$key]['num'] = $num;  //答题人数
                $list[$key]['zscore'] = $zscore;  //总分数
                $list[$key]['avg_score'] = floatval($avg_score); //平均分
                $list[$key]['zznum'] = 0;
                $list[$key]['tiku_score'] = $tiku_score;
                //人数
                $list[$key]['pnum'] = $dnumss;

                $ids = '';
                if(!empty($tlist)){

                    foreach ($tlist as $keys => $vals){
                        $ids .= $vals['uid'].',';
                    }
                    $ids = rtrim($ids,',');
                    $ids = '/'.$ids.'/';
                }else{
                    //$parent_ids
                    $ids = null;
                }

                $list[$key]['ids'] = $ids;
            }


        }

        $timeKey  = array_column($list,'avg_score');
        array_multisort($timeKey, SORT_ASC, $list);


        //所有人数
        // $a = 0;
        // $b = 0;
        // $c = 0;
        $d = '';
        foreach($list as $key => $val){
            // $a = $a+$val['pnum'];
            // $b = $b+$val['num'];
            // $c = $c+$val['zscore'];
            if($val['ids'] != ''){
                $d .= trim($val['ids'],'/').',';
            }
        }

        $whraa['catid'] = $parentid;
        $uuids= Db::name('cateuser')->field('uid')->where($whraa)->buildSql(true);
        $zscores = Db::name('test')->where('uid','exp','In '.$uuids)->where('qid',$qid)->where('is_tijiao',1)->sum('score');
        
        $dtnum = Db::name('test')->where('uid','exp','In '.$uuids)->where('qid',$qid)->where('is_tijiao',1)->count();


        $pnums = Db::name('cateuser')->field('uid')->where($whraa)->select();
        $dds = '';
        foreach($pnums as $key => $val){
            $dds .= $val['uid'].',';
        }
        $ddss = rtrim($dds,',');

        $ddss = explode(',',$ddss);

        $ddss = array_unique($ddss);

        $dnumss = count($ddss);

        if($zscores > 0){
            $avgscore = round($zscores/$dnumss,2);
        }else{
            $avgscore = 0;
        }

        if($d != ''){
            $dd = rtrim($d,',');

            $dd = explode(',',$dd);

            $dd = array_unique($dd);

            $dnum = count($dd);
        }else{
            $dnum = 0;
        }

        //$pnums = array_unique($pnums);
        
        
        $datas['tiku_score'] = $tiku_score;
        
        $datas['zdnum'] = count($list);
        $datas['pnum'] = $dtnum;
        //$datas['znum'] = $dnumss;
        $datas['avgscore'] = floatval($avgscore);
        // $datas['ppnum'] = $a;
        $datas['zscore'] = $zscores;
        $datas['zpnum'] = $dnumss;

        //查询站点直属人员
        $whraaa['leixing'] = 1;
        $whraaa['catid'] = $parentid;
        $uuidss= Db::name('cateuser')->field('uid')->where($whraaa)->buildSql(true);
        $tlistss = Db::name('users')->field('id,username')->where('id','exp','In '.$uuidss)->select();
        $psocre = 0;
        foreach ($tlistss as $keyss => $valss){
            
            $tlistss[$keyss]['uid'] = $valss['id'];
            $tlistss[$keyss]['title'] = $valss['username'];
            $tlistss[$keyss]['parentid'] = $parentid;
            $tlistss[$keyss]['sort'] = 0;
            $tlistss[$keyss]['level'] = 0;
            $tlistss[$keyss]['moduleid'] = 1;
            $tlistss[$keyss]['status'] = 1;
            $tlistss[$keyss]['parent_name'] = '';
            $tlistss[$keyss]['num'] = 0;
            $tlistss[$keyss]['zscore'] = 0;
            $tlistss[$keyss]['zznum'] = -1;
            $tlistss[$keyss]['pnum'] = 0;
            $tlistss[$keyss]['parent_ids'] = 0;
            $tlistss[$keyss]['ids'] = '';
            $tlistss[$keyss]['tiku_score'] = $tiku_score;

            $pinfo = Db::name('test')->where('uid',$valss['id'])->where('qid',$qid)->find();
            if($pinfo['is_tijiao'] == 1){
                $tlistss[$keyss]['avg_score'] = floatval($pinfo['score']);
            }else{
                $tlistss[$keyss]['avg_score'] = 0;
            }
            if($pinfo){
                $tlistss[$keyss]['is_kaohe'] = 1;
            }else{
                $tlistss[$keyss]['is_kaohe'] = 0;
            }
        }
        
        
        $tlistsss = array_filter($tlistss,function($element){
        
        return $element['is_kaohe'] == 1;  //只保留$arr数组中的age元素为22的数组元素
        
        });
        
        $timeKeys  = array_column($tlistsss,'avg_score');
        array_multisort($timeKeys, SORT_ASC, $tlistsss);
        
        if($uinfo['group_id'] == 1 || $uinfo['group_id'] == 2 || $uinfo['group_id'] == 7){
            $arrnew = array_merge($list,$tlistsss);
        }else{
            $arrnew = $list;
        }
        $data_rt['status'] = 200;
        $data_rt['msg'] = 'success';
        $data_rt['data'] = $arrnew;
        $data_rt['cdata'] = $datas;


        //print_r($list);
        return json_encode($data_rt);
        exit;

    }

    public function indexs(){
        $id = input('id');
        $qid = input('qid');
        
        $tiku_id = Db::name('daxuetang')->where('id',$qid)->value('tiku_id');
        $tiku_score = Db::name('tiku')->where('tiku_id',$tiku_id)->sum('score');
        
        $uinfo = Db::name('users')->where('id',$this->user_id)->find();
        if($uinfo['group_id'] == 1 || $uinfo['group_id'] == 2 || $uinfo['group_id'] == 7){

            $where=[];
            if(empty($id)){
                $data_rt['status'] = 201;
                $data_rt['msg'] = '请选择站点';
                return json_encode($data_rt,true);
            }else{
                $where[]=['catid', '=', $id];
            }
            if(empty($qid)){
                $qid = Db::name('daxuetang')->order('id desc')->value('id');
                if($nid){
                    $qid = $nid;
                }else{
                    $qid = 1;
                }
            }
            $where[]=['qid', '=', $qid];

            $list= Db::name('zdrecord_list')->where($where)->select();
            
            foreach ($list as $key => $val){

                $whra['uid'] = $val['uid'];
                $zscore = Db::name('test')->where($whra)->where('qid',$qid)->where('is_tijiao',1)->value('score');
                if(empty($zscore)){
                    $zscore = 0;
                    $num = 0;
                }else{
                    $num = 1;
                }
                $whrb['id'] = $val['uid'];
                $username = Db::name('users')->where($whrb)->value('username');

                $list[$key]['title'] = $username;
                $list[$key]['num'] = $num;
                $list[$key]['zscore'] = floatval($zscore);
                $list[$key]['avg_score'] = floatval($zscore);
                $list[$key]['zznum'] = -1;
                
                $list[$key]['pnum'] = Db::name('test')->where($whra)->where('qid',$qid)->count();
                
                $parent_ids = Db::name('cate')->where('id',$val['catid'])->value('parentid');
                
                $list[$key]['parent_ids'] = $parent_ids;
                
                $parent_name = Db::name('cate')->where('id',$parent_ids)->value('title');
                
                $list[$key]['parent_name'] = $parent_name;
                
                $list[$key]['father_name'] = $parent_ids = Db::name('cate')->where('id',$val['catid'])->value('title');
                
                $list[$key]['tiku_score'] = $tiku_score;
            }

            $timeKey  = array_column($list,'avg_score');
            array_multisort($timeKey, SORT_ASC, $list);
            
        }else{

            $where=[];

            if(empty($id)){
                $data_rt['status'] = 201;
                $data_rt['msg'] = '请选择站点';
                return json_encode($data_rt,true);
            }else{
                if(in_array($id,explode(',',$uinfo['ruless']))){
                    $where[]=['catid', '=', $id];
                }else{
                    $data_rt['status'] = 201;
                    $data_rt['msg'] = '无权限';
                    return json_encode($data_rt,true);
                }
            }

            if(empty($qid)){
                $nid = Db::name('daxuetang')->order('id desc')->value('id');
                if($nid){
                    $qid = $nid;
                }else{
                    $qid = 1;
                }
            }
            
            $where[]=['qid', '=', $qid];
            
            $list= Db::name('zdrecord_list')->where($where)->select();
            
            foreach ($list as $key => $val){

                $whra['uid'] = $val['uid'];
                $zscore = Db::name('test')->where($whra)->where('qid',$qid)->where('is_tijiao',1)->value('score');
                if(empty($zscore)){
                    $zscore = 0;
                    $num = 0;
                }else{
                    $num = 1;
                }
                $whrb['id'] = $val['uid'];
                $username = Db::name('users')->where($whrb)->value('username');

                $list[$key]['title'] = $username;
                $list[$key]['num'] = $num;
                $list[$key]['zscore'] = floatval($zscore);
                $list[$key]['avg_score'] = floatval($zscore);
                $list[$key]['zznum'] = -1;
                $list[$key]['pnum'] = Db::name('test')->where($whra)->where('qid',$qid)->count();
                
                $parent_ids = Db::name('cate')->where('id',$val['catid'])->value('parentid');
                
                $list[$key]['parent_ids'] = $parent_ids;
                
                $parent_name = Db::name('cate')->where('id',$parent_ids)->value('title');
                
                $list[$key]['parent_name'] = $parent_name;
                
                $list[$key]['tiku_score'] = $tiku_score;
            }

            $timeKey  = array_column($list,'zscore');
            array_multisort($timeKey, SORT_ASC, $list);
        }
        
        //所有人数
        $pnums = count($list);
        $b = 0;
        $c = 0;
        foreach($list as $key => $val){
            $c = $c+$val['zscore'];
            $b = $b+$val['num'];
        }
        if($c > 0){
            $avgscore = round($c/$pnums,2);
        }else{
            $avgscore = 0;
        }
        
        $zpnum = Db::name('cateuser')->where('catid',$id)->count();
        
        $datas['zdnum'] = 1;
        $datas['pnum'] = $b;
        //$datas['zpnum'] = $pnums;
        $datas['zpnum'] = $zpnum;
        $datas['avgscore'] = $avgscore;
        $datas['tiku_score'] = $tiku_score;

        $data_rt['status'] = 200;
        $data_rt['msg'] = 'success';
        $data_rt['data'] = $list;
        $data_rt['cdata'] = $datas;

        //print_r($list);
        return json_encode($data_rt);
        exit;
    }


}
