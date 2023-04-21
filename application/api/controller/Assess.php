<?php
/**
 * +----------------------------------------------------------------------
 * | 新闻管理控制器
 * +----------------------------------------------------------------------
 */
namespace app\api\controller;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\Assess as M;

class Assess extends Base
{
    protected $validate = 'Assess';

    //列表
    public function index(){
        //条件筛选
        $qid = Request::param('qid');
        //全局查询条件
        $where=[];
        if(!empty($qid)){
            $where[]=['qid', '=', $qid];
        }
        $where[]=['uid', '=', $this->user_id];
        $where[]=['type', '=', 2];
        
        $tinfo = Db::name('test')->where('qid',$qid)->where('uid',$this->user_id)->find();
        if(!empty($tinfo)){
            if($tinfo['is_tijiao'] == 1){
                 echo apireturn(201,'本周考试已交卷,无法进入','');die;
            }
        }else{
            echo apireturn(201,'请先开始考试','');die;
        }
        
        $tid = Db::name('daxuetang')->where('id',$qid)->value('tiku_id');
        //全局查询条件
        $wheres=[];
        if(!empty($tid)){
            $wheres[]=['tiku_id', '=', $tid];
        }else{
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '题库id不存在';
            return json_encode($rs_arr,true);
            exit;
        }
        //调取列表
        
        $list = Db::name('assess_type')
                ->order('id ASC')
                ->where($wheres)
                ->where('parent_id',0)
                ->select();
        foreach ($list as $key => $val){
            if($val['score'] == 0){
                $lists = Db::name('assess_type')
                        ->order('id ASC')
                        ->where($wheres)
                        ->where('parent_id',$val['id'])
                        ->select();
                foreach ($lists as $keys => $vals){
                    $lists[$keys]['assess_score'] = 0;
                }
                
            }else{
                $list[$key]['assess_score'] = 0;
                $lists = array();
            }
            
            $list[$key]['beizhu'] = '';
            $list[$key]['erji'] = $lists;
        }
        
        //调取列表
        $pnum = Db::name('assess')
            ->alias('a')
            ->leftJoin('users u','a.uid = u.id')
            ->field('a.*,u.username as username')
            ->order('a.id DESC')
            ->where($where)
            ->count();
            
        $number = 2;
        
        if($pnum < $number){
            $i = 0;
            $number = $number - $pnum;
            for($i = 0;$i < $number;$i++){
                
                $m = new M();
                $data['qid'] = $qid;
                $data['uid'] = $this->user_id;
                $data['type'] = 2;
                $data['username'] = Db::name('users')->where('id',$this->user_id)->value('username');
                $data['answer'] = json_encode($list,true);
                $data['create_time'] = time();
                $data['update_time'] = time();
                $result =  $m->addPost($data);
            
            }
        }
        
        $list = Db::name('assess')
            ->alias('a')
            ->leftJoin('users u','a.uid = u.id')
            ->field('a.*,u.username as username')
            ->order('a.id DESC')
            ->where($where)
            ->select();
            foreach ($list as $k => $v){
                
                $answers = json_decode($v['answer'],true);
                $list[$k]['answers'] = $answers;
                
                $zongnum = 1;
                $onenum = 0;
                foreach($answers as $key =>$val){
                    
                    if($val['score'] == 0){
                        
                        $twonum = 0;
                        foreach ($val['erji'] as $keys => $vals){
                            //echo $vals['assess_score'].'-';
                            if($vals['assess_score'] > 0){
                                $twonum = $twonum + 1;
                            }
                            
                            //echo $twonum.' ';
                        }
                        if($twonum == 0){
                            $zongnum = 0;
                            $onenum = 0;
                        }else{
                            $onenum = $onenum + 1;
                        }
                        //echo '!'.$twonum.'!';
                       
                    }else{
                        if($val['assess_score'] > 0 ){
                            $onenum = $onenum + 1;
                        }
                    }
                }
                //die;
                if($onenum == 0){
                    $zongnum = 0;
                }
                if($zongnum > 0){
                    $list[$k]['is_submit'] = 1;
                }else{
                    $list[$k]['is_submit'] = 0;
                }
            }

        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $list;
        return json_encode($rs_arr,true);
        exit;
    }

    //选人
    public function choose(){
        $data = Request::param();
        
        $id = $data['id'];
        
        $otherid = $data['otherid'];
        
        $qid = Db::name('assess')->where('id',$id)->value('qid');
        
        $tid = Db::name('daxuetang')->where('id',$qid)->value('tiku_id');
        //全局查询条件
        $wheres=[];
        if(!empty($tid)){
            $wheres[]=['tiku_id', '=', $tid];
        }else{
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '题库id不存在';
            return json_encode($rs_arr,true);
            exit;
        }
        //调取列表
        $list = Db::name('assess_type')
                ->order('id ASC')
                ->where($wheres)
                ->where('parent_id',0)
                ->select();
        foreach ($list as $key => $val){
            if($val['score'] == 0){
                $lists = Db::name('assess_type')
                        ->order('id ASC')
                        ->where($wheres)
                        ->where('parent_id',$val['id'])
                        ->select();
                foreach ($lists as $keys => $vals){
                    $lists[$keys]['assess_score'] = 0;
                }
                
            }else{
                $list[$key]['assess_score'] = 0;
                $lists = array();
            }
            
            $list[$key]['beizhu'] = '';
            $list[$key]['erji'] = $lists;
        }
        
        $other = Db::name('users')->where('id',$data['otherid'])->count();
        if($other == 0){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '被评人不存在';
            return json_encode($rs_arr,true);
            exit;
        }else{
            if($data['otherid'] == $this->user_id){
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '不能评价自己';
                return json_encode($rs_arr,true);
                exit;
            }else{
                
                $counts = Db::name('assess')->where('otherid',$otherid)->where('uid',$this->user_id)->where('qid',$qid)->count();
                if($counts > 0){
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = '您已选择,请选择其他人';
                    return json_encode($rs_arr,true);
                    exit;
                }else{
                    $m = new M();
                    $data['update_time'] = time();
                    $data['usernames'] = Db::name('users')->where('id',$data['otherid'])->value('username');
                    $data['answer'] = json_encode($list,true);
                    $data['beizhu'] = null;
                    
                    $result = $m->editPost($data);
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
        }
            
    }
    //评分
    public function editPost(){
        $data = Request::param();
        $validate = new \app\common\validate\Assess();
        $result = $validate->scene('edit')->check($data);
        if (!$result) {
            // 验证失败 输出错误信息
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = $validate->getError();
            return json_encode($rs_arr,true);
            exit;
        }else{
            $m = new M();
            $datas['id'] = $data['id'];
            $datas['answer'] = $data['answer'];
            $datas['status'] = 1;
            $datas['update_time'] = time();
            $result = $m->editPost($datas);
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

    //评分
    public function beizhu(){
        $beizhu = Request::param('beizhu');
        
        $list = json_decode($beizhu,true);
        
        if(!empty($list)){
            foreach ($list as $key => $val){
                
                $datas['beizhu'] = $val['beizhu'];
                $datas['update_time'] = time();
                Db::name('assess')->where('id',$val['id'])->update($datas);
                
            }
        }
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        return json_encode($rs_arr,true);
        exit;
    
    }
    
    //删除
    public function del(){
        if(Request::isPost()) {
            $id = Request::post('id');
            if( empty($id) ){
                $rs_arr['status'] = 500;
                $rs_arr['msg'] = 'ID不存在';
                return json_encode($rs_arr,true);
                exit;
            }
            $m = new M();
            $m->del($id);

            $rs_arr['status'] = 200;
            $rs_arr['msg'] ='success';
            return json_encode($rs_arr,true);
            exit;
        }
    }
    
}
