<?php
namespace app\api\controller;
use think\Controller;
use think\facade\Request;
use think\facade\Cache;
use think\facade\Env;
use think\Db;
use think\image;

use app\common\model\Train as T;

class Train extends Base
{
    //获取任务列表
    public function index(){
        
        $uinfo = Db::name('users')->where('id',$this->user_id)->find();
        
        $periods = '0,'.$uinfo['period'];
        
        $wherep[] = ['period','in',$periods];
        
        $parentid = Request::param('parentid');
        if($parentid > 0){
            $list = Db::name('train_cate')->where($wherep)->where('id',$parentid)->where('status',1)->where('level',1)->order('sort asc')->select();
        }else{
            $list = Db::name('train_cate')->where($wherep)->where('parentid',$parentid)->where('status',1)->order('sort asc')->select();
        }
     
        $train_num = 0;
        $train_num_one = 0;
        $train_status = 0;

        //未传图数量
        $train_photo = 0;
        if(count($list) > 0){
            foreach($list as $key => $val){
                
                $list_two = Db::name('train_cate')->where($wherep)->where('parentid',$val['id'])->where('status',1)->order('sort asc')->select();
                if($val['level'] == 1){
                    $periods = explode(',',$val['periods']);
                }else{
                    $train_periods = Db::name('train_cate')->where('id',$parentid)->where('status',1)->value('periods');
                    $periods = explode(',',$train_periods);
                }
                
                if(in_array($uinfo['period'],$periods)){
                    if(count($list_two) > 0){
                        
                        $znum = 0;
                        $wnum = 0;
                        $dshnum = 0;
                        $wspnum = 0;
                        
                        $shznum = 0;
                        $shwnum = 0;
                        $shdshnum = 0;
                        
                        //文章视频查看状态统计
                        $wartnum = 0;
                        foreach($list_two as $keys => $vals){
                            
                            //开启传图的任务
                            if($vals['is_photo'] == 1){
                                //查询是否上传过图片
                                $photonum = Db::name('train_image')->where('catid',$vals['id'])->where('uid',$this->user_id)->count();
                                if($photonum == 0){
                                    $train_photo++;
                                }
                            }
                
                            //任务状态
                            $apply_status = Db::name('train_apply')->where('catid',$vals['id'])->where('uid',$this->user_id)->value('status');
                            $list_two[$keys]['apply_status'] = $apply_status;
                            
                            //重要任务统计
                            if($vals['is_import'] == 1){
                                if($apply_status == 2){
                                    $wnum++;
                                }
                                if($apply_status == 1){
                                    $dshnum++;
                                }
                                $znum++;
                            }
                            
                            if($vals['is_submit'] == 1){
                                if($apply_status == 2){
                                    //通过的
                                    $shwnum++;
                                }
                                if($apply_status == 1){
                                    //待审核的
                                    $shdshnum++;
                                }
                                //全部的
                                $shznum++;
                            }
                            
                            
                            //查模版类型
                            
                            $module_type = Db::name('template')->where('id',$vals['module_id'])->value('type');
                            $list_two[$keys]['module_type'] = $module_type;
                            $templist = Db::name('template')->where('parentid',$vals['module_id'])->where('is_delete',1)->select();
                            $list_two[$keys]['module_num'] = count($templist);
                            
                            if(count($templist) > 0 && $module_type == 4){
                                foreach($templist as $keyss => $valss){
                                    if(Db::name('train_status')->where('template_id',$valss['id'])->where('uid',$this->user_id)->value('status') != 2){
                                        $wspnum++;                              
                                    }
                                    if(Db::name('train_status')->where('template_id',$valss['id'])->where('uid',$this->user_id)->count() > 0){
                                        $wartnum++;
                                    }
                                }
                            }
                            
                            //查询文章是否看过
                            if(Db::name('train_status')->where('template_id',$vals['module_id'])->where('uid',$this->user_id)->where('status',1)->count() > 0){
                                $wartnum++;
                            }
                            
                        }
                        //$list[$key]['znum'] = count($list_two);
                        //$list[$key]['wnum'] = $wnum;
                       
                        //进度状态
                        if($znum == 0){
                            $list[$key]['scale'] = 0;
                            $list[$key]['import_num'] = 0;
                        }else{
                            $list[$key]['scale'] = intval(($wnum/$znum)*100);
                            $list[$key]['import_num'] = $znum;
                        }
                        
                        if($wnum > 0){
                            if($wnum < $znum){
                                $list[$key]['wstatus'] = 2;
                                
                                $train_num++;
                            }else{
                                
                                //查询随机抽考成绩
                                $whrz1['uid'] = $this->user_id;
                                $whrz1['tid'] = $val['tiku_id'];
                                $whrz1['catid'] = $val['id'];
                                if($val['tiku_id'] > 0 && $val['is_exam'] == 1){
                
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
                                
                            }
                        }else{
                            if($znum > 0){
                                if($wartnum > 0){
                                    $list[$key]['wstatus'] = 2;
                                }else{
                                    $list[$key]['wstatus'] = 1;
                                }
                                
                                $train_num++;
                            }else{
                                $list[$key]['wstatus'] = 1;
                            }
                        }
                        
                        if($val['is_submit'] == 1){
                                
                            if($shwnum > 0){
                                if($shwnum < $shznum){
                                    if($shznum - $shwnum != $shdshnum){
                                        if($wspnum == 0){
                                            if($train_photo > 0){
                                                $list[$key]['submit_status'] = 2; 
                                            }else{
                                                $list[$key]['submit_status'] = 1; 
                                            }
                                        }else{
                                            $list[$key]['submit_status'] = 2; 
                                        }                                 
                                    }else{
                                        $list[$key]['submit_status'] = 2;     
                                    }
                                }else{
                                    $list[$key]['submit_status'] = 2;     
                                }
                            }else{
                                if($shznum > 0){
                                    if($wspnum == 0){
                                        if($shznum - $shwnum != $shdshnum){
                                            if($train_photo > 0){
                                                $list[$key]['submit_status'] = 2; 
                                            }else{
                                                $list[$key]['submit_status'] = 1; 
                                            }
                                        }else{
                                            $list[$key]['submit_status'] = 2; 
                                        }
                                    }else{
                                        $list[$key]['submit_status'] = 2; 
                                    }  
                                }else{
                                    $list[$key]['submit_status'] = 2;  
                                }
                            }
                            
                            
                        }else{
                            $list[$key]['submit_status'] = 2;
                        }
                        
                        $list[$key]['clist'] = $list_two;
                        
                    }else{
                        
                        foreach($list_two as $keys => $vals){
                            //查模版类型
                            $list_two[$keys]['module_type'] = Db::name('template')->where('id',$vals['module_id'])->value('type');
                            $list_two[$keys]['module_num'] = Db::name('template')->where('parentid',$vals['module_id'])->where('is_delete',1)->count();
                            $list_two[$keys]['apply_status'] = 2;
                            
                        }
                        //$list[$key]['znum'] = 0;
                        //$list[$key]['wnum'] = 0;
                        $list[$key]['scale'] = 0;
                        $list[$key]['clist'] = $list_two;
                        
                        $list[$key]['wstatus'] = 3;
                        $list[$key]['submit_status'] = 2; 
                                 
                    }
                    
                }else{
                    foreach($list_two as $keys => $vals){
                        //查模版类型
                        $list_two[$keys]['module_type'] = Db::name('template')->where('id',$vals['module_id'])->value('type');
                        $list_two[$keys]['module_num'] = Db::name('template')->where('parentid',$vals['module_id'])->where('is_delete',1)->count();
                        $list_two[$keys]['apply_status'] = 2;
                    }
                    //$list[$key]['znum'] = count($list_two);
                    //$list[$key]['wnum'] = count($list_two);
                    $list[$key]['scale'] = 100;
                    $list[$key]['clist'] = $list_two;
                    $list[$key]['submit_status'] = 2; 
                    $list[$key]['wstatus'] = 3; 
                    
                }
                
                //查询随机抽考成绩

                if($parentid > 0){
                    $tlist = Db::name('train_cate')->where('id',$parentid)->where('status',1)->where('level',1)->select();
                }else{
                    $tlist = Db::name('train_cate')->where('id',$val['id'])->where('status',1)->order('sort asc')->select();
                }
                
                foreach($tlist as $key1 => $val1){
                    $whrz['uid'] = $this->user_id;
                    $whrz['tid'] = $val1['tiku_id'];
                    $whrz['catid'] = $val['id'];
                    if($val1['tiku_id'] > 0){
                        $traininfo = Db::name('traintest')->where($whrz)->find();
                        if($traininfo){
                            $cnum = Db::name('traintest')->where($whrz)->value('cnum');
                            $score = Db::name('traintest')->where($whrz)->value('score');
                            $status = Db::name('traintest')->where($whrz)->value('status');
                            if($status == 1){
                                $list[$key]['cnum'] = null;
                                $train_num++;
                            }else{
                                $list[$key]['cnum'] = $cnum;
                            }
                            if($cnum != 0){
                                $train_num++;
                            }
                        }else{
                            $train_num++;
                        }
                    }
                    
                }
                
            }
        }else{
            $list = array();
        }
        
        
        //人员统计
        if($uinfo['group_ids'] == 7){
            $pnum1 = Db::name('users')->where('period',1)->where('is_delete',1)->count();
            $pnum2 = Db::name('users')->where('period',2)->where('is_delete',1)->count();
            $pnum3 = Db::name('users')->where('period',3)->where('is_delete',1)->count();
        }else{
            $where=[];
            if(empty(trim($uinfo['period_ruless']))){
                $pnum1 = 0;
                $pnum2 = 0;
                $pnum3 = 0;
                
            }else{
                $where[] = ['catid','in',$uinfo['period_ruless']];
                $uuid= Db::name('cateuser')->field('uid')->where($where)->buildSql(true);
            
                $pnum1 = Db::name('users')->where('id','exp','In '.$uuid)->where('period',1)->where('is_delete',1)->count();
                $pnum2 = Db::name('users')->where('id','exp','In '.$uuid)->where('period',2)->where('is_delete',1)->count();
                $pnum3 = Db::name('users')->where('id','exp','In '.$uuid)->where('period',3)->where('is_delete',1)->count();
                
            }
            
        }
        
        //待办任务
        $daiban = Db::name('train_apply')
            ->where('apply_uid',$this->user_id)
            ->where('status',1)
            ->group('unionid')
            ->count();
        
        if($uinfo['period'] == 3){
            $train_num++;
        }
        
        //待办任务
        $message_list = Db::name('train_message')
            ->alias('m')
            ->leftJoin('users u','m.uid = u.id')
            ->field('m.*,u.username as username')
            ->where('m.apply_uid',$this->user_id)
            ->where('m.status',1)
            ->select();
        $message_text = '';
        if(count($message_list) > 0){
            foreach($message_list as $key => $val){
                $message_text = $message_text.$val['username'].$val['content'].' ';
            }
        }else{
            $message_text = '暂无未读消息';
        }
        
        $pnum['jianxi'] = $pnum1;
        $pnum['shixi'] = $pnum2;
        $pnum['zhengshi'] = $pnum3;
        $pnum['daiban'] = $daiban;
        $pnum['train_num'] = $train_num;
        $pnum['message_text'] = $message_text;
        
        $pnum['train_photo'] = $train_photo;
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $list;
        $rs_arr['tongji'] = $pnum;
        return json_encode($rs_arr,true);
        exit;
        
    }
    
    //获取模版内容
    public function module(){
        
        $id = Request::param('id');
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = 'id not found';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $info = Db::name('train_cate')->where('id',$id)->where('status',1)->find();
            if($info['level'] == 1){
                $periods = explode(',',$info['periods']);
            }else{
                $train_periods = Db::name('train_cate')->where('id',$info['parentid'])->where('status',1)->value('periods');
                $periods = explode(',',$train_periods);
            }
            
            if($info['is_photo'] == 1){
                //查询是否上传过图片
                $photonum = Db::name('train_image')->where('catid',$info['id'])->where('uid',$this->user_id)->count();
                if($photonum > 0){
                    $train_photo = 0;
                    $photo_list = Db::name('train_image')->field('id,sort,url')->where('catid',$info['id'])->where('uid',$this->user_id)->order('sort asc')->select();
                }else{
                    $train_photo = 1;
                    $photo_list = array();
                }
            }else{
                $train_photo = 0;
                $photo_list = array();
            }
            
            if(count($photo_list) > 0){
                foreach ($photo_list as $key => $val){
                    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                    $photo_list[$key]['url'] = $http_type.$_SERVER['HTTP_HOST'].$val['url'];
                }
            }
            
            $info['photo_list'] = $photo_list;
                
            $uinfo = Db::name('users')->where('id',$this->user_id)->find();
            if($info['module_id']){
                
                $info['wartnum'] = Db::name('train_status')->where('template_id',$info['module_id'])->where('uid',$this->user_id)->where('status',1)->count();
                
                //获取模版内容
                $tlist = Db::name('template')->where('parentid',$info['module_id'])->where('is_delete',1)->order('sort asc')->select();
                
                $types = Db::name('template')->where('id',$info['module_id'])->where('is_delete',1)->value('type');
                
                $wspnum = 0;            
                foreach($tlist as $key => $val){
                        $tlist[$key]['status'] = Db::name('train_status')->where('template_id',$val['id'])->where('uid',$this->user_id)->value('status');
                        
                        if(Db::name('train_status')->where('template_id',$val['id'])->where('uid',$this->user_id)->value('status') != 2 && $types == 4){
                                        $wspnum++;                              
                                    }
                    }
                $info['tlist'] = $tlist;
                
                //审批列表
                $apply_id = Db::name('train_apply')
                    ->where('catid',$info['id'])
                    ->where('uid',$this->user_id)
                    ->value('id');
                
                if($apply_id > 0){
                    $apply_list = Db::name('train_record')
                    ->alias('tr')
                    ->leftJoin('users u','tr.apply_uid = u.id')
                    ->field('tr.*,tr.beizhu as content,u.username as username')
                    ->where('apply_id',$apply_id)
                    ->order('apply_time desc')
                    ->select();
                }else{
                    $apply_list = array();
                }    
                    
                $info['apply_list'] = $apply_list;
                
                //审批状态
                $apply_status = Db::name('train_apply')
                    ->where('catid',$info['id'])
                    ->where('uid',$this->user_id)
                    ->value('status');
                
                $info['apply_status'] = $apply_status;
                
                if(in_array($uinfo['period'],$periods) && $apply_status != 2 && $info['is_submit'] == 1 && $wspnum == 0 && $train_photo == 0){
                    if($apply_status == 1){
                        $info['submit_status'] = 2;
                    }else if($apply_status == 3){
                        $info['submit_status'] = 1;
                    }else{
                        $info['submit_status'] = 1;
                    }
                }else{
                    $info['submit_status'] = 2;
                }
                    
                $rs_arr['status'] = 200;
                $rs_arr['msg'] = 'success';
                $rs_arr['data'] = $info;
                return json_encode($rs_arr,true);
                exit; 
            }else{
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '该任务不存在！';
                return json_encode($rs_arr,true);
                exit; 
            }
        }
        
    }
    
    //保存
    public function apply(){
        $id = Request::param('id');
        $apply_uid = Request::param('apply_uid');
        $content = Request::param('content');
        $type = Request::param('type');
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = 'id not found';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $info = Db::name('train_cate')->where('id',$id)->where('status',1)->find();
            if($info){
                //生成唯一id
                $unionid = md5(time().$this->user_id);
                
                if($info['level'] == 2){
                    //二级任务
                    
                    //开启审批的提交
                    if($info['is_submit'] == 1){
                           
                        $tinfo = Db::name('train_apply')->where('uid',$this->user_id)->where('catid',$id)->where('type',$type)->find();
                        if($tinfo){
                            //拒绝后的重新提交审批
                            if($tinfo['status'] == 3){
                                $data['apply_uid'] = $apply_uid;
                                $data['period'] = Db::name('users')->where('id',$this->user_id)->value('period');
                                $data['content'] = $content;
                                $data['apply_time'] = time();
                                $data['unionid'] = $unionid;
                                $data['status'] = 1;
                                $data['type'] = $type;
                                Db::name('train_apply')->where('id',$tinfo['id'])->update($data);
                            
                                $rs_arr['status'] = 200;
                                $rs_arr['msg'] = 'success';
                                return json_encode($rs_arr,true);
                                exit;
                            }else{
                                $rs_arr['status'] = 201;
                                $rs_arr['msg'] = '请勿重复申请';
                                return json_encode($rs_arr,true);
                                exit;
                            }
                            
                        }else{
                            
                            $data['uid'] = $this->user_id;
                            $data['apply_uid'] = $apply_uid;
                            $data['period'] = Db::name('users')->where('id',$this->user_id)->value('period');
                            $data['catid'] = $id;
                            $data['content'] = $content;
                            $data['unionid'] = $unionid;
                            $data['apply_time'] = time();
                            $data['status'] = 1;
                            $data['type'] = $type;
                            $id = Db::name('train_apply')->insertGetId($data);
                            if($id > 0){
                                $rs_arr['status'] = 200;
                                $rs_arr['msg'] = 'success';
                                return json_encode($rs_arr,true);
                                exit;
                            }else{
                                $rs_arr['status'] = 201;
                                $rs_arr['msg'] = 'field';
                                return json_encode($rs_arr,true);
                                exit;
                            }
                            
                        }
                        
                     
                    }
                    
                }else{
                    
                    //一级任务
                    
                    if($type == 1){
                        $list = Db::name('train_cate')->where('parentid',$id)->where('status',1)->select();
                        foreach ($list as $key => $val){
                            if($val['is_submit'] == 1){
                                $tinfo = Db::name('train_apply')->where('uid',$this->user_id)->where('catid',$val['id'])->where('type',$type)->find();
                                if($tinfo){
                                    if($tinfo['status'] == 3){
                                        $data['apply_uid'] = $apply_uid;
                                        $data['period'] = Db::name('users')->where('id',$this->user_id)->value('period');
                                        $data['content'] = $content;
                                        $data['apply_time'] = time();
                                        $data['unionid'] = $unionid;
                                        $data['status'] = 1;
                                        $data['type'] = $type;
                                        Db::name('train_apply')->where('id',$tinfo['id'])->update($data);
                                    }
                                }else{
                                    $data['uid'] = $this->user_id;
                                    $data['apply_uid'] = $apply_uid;
                                    $data['period'] = Db::name('users')->where('id',$this->user_id)->value('period');
                                    $data['catid'] = $val['id'];
                                    $data['content'] = $content;
                                    $data['unionid'] = $unionid;
                                    $data['apply_time'] = time();
                                    $data['status'] = 1;
                                    $data['type'] = $type;
                                    Db::name('train_apply')->insert($data);
                                }
                            }
                        }
                        
                        $rs_arr['status'] = 200;
                        $rs_arr['msg'] = 'success';
                        return json_encode($rs_arr,true);
                        exit;
                    }else{
                        $tinfo = Db::name('train_apply')->where('uid',$this->user_id)->where('catid',$id)->where('type',$type)->find();
                        if($tinfo){
                            //拒绝后的重新提交审批
                            if($tinfo['status'] == 1){
                                $rs_arr['status'] = 201;
                                $rs_arr['msg'] = '请勿重复申请';
                                return json_encode($rs_arr,true);
                                exit;
                                
                            }else{
                                $data['apply_uid'] = $apply_uid;
                                $data['period'] = Db::name('users')->where('id',$this->user_id)->value('period');
                                $data['content'] = $content;
                                $data['apply_time'] = time();
                                $data['status'] = 1;
                                $data['type'] = $type;
                                Db::name('train_apply')->where('id',$tinfo['id'])->update($data);
                            
                                $rs_arr['status'] = 200;
                                $rs_arr['msg'] = 'success';
                                return json_encode($rs_arr,true);
                                exit;
                            }
                            
                        }else{
                            
                            $data['uid'] = $this->user_id;
                            $data['apply_uid'] = $apply_uid;
                            $data['period'] = Db::name('users')->where('id',$this->user_id)->value('period');
                            $data['catid'] = $id;
                            $data['content'] = $content;
                            $data['unionid'] = $unionid;
                            $data['apply_time'] = time();
                            $data['status'] = 1;
                            $data['type'] = $type;
                            $id = Db::name('train_apply')->insertGetId($data);
                            if($id > 0){
                                $rs_arr['status'] = 200;
                                $rs_arr['msg'] = 'success';
                                return json_encode($rs_arr,true);
                                exit;
                            }else{
                                $rs_arr['status'] = 201;
                                $rs_arr['msg'] = 'field';
                                return json_encode($rs_arr,true);
                                exit;
                            }
                            
                        }
                    }  
                }  
            }else{
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = 'id not found2';
                return json_encode($rs_arr,true);
                exit;
            }
        }
    }
    
    //管理员代办列表
    public function apply_list(){
        $status = Request::param('status');
        $keyword = Request::param('keyword');
        
        $where = [];
        if(!empty($status)){
            $where[] = ['ta.status','=',$status];
        }
        if(!empty($keyword)){
            $where[]=['u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        $list = Db::name('train_apply')->alias('ta')
            ->leftJoin('users u','u.id = ta.uid')
            ->where('apply_uid',$this->user_id)
            ->where($where)
            ->field('ta.*,u.username as username,u.mobile as mobile')
            ->group('unionid')
            ->order('apply_time desc')
            ->select();
        foreach ($list as $key => $val){
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
            
            $list[$key]['username'] = Db::name('users')->where('id',$val['uid'])->value('username');
            $list[$key]['mobile'] = Db::name('users')->where('id',$val['uid'])->value('mobile');
            $topid = Db::name('train_cate')->where('id',$val['catid'])->value('parentid');
            $list[$key]['apply_time'] = date('Y-m-d H:i:s',$val['apply_time']);
            
            if($val['type'] == 2){
                $list[$key]['topname'] = Db::name('train_cate')->where('id',$val['catid'])->value('title').' - 答题申请';
            }else{
                if(Db::name('train_apply')->where('apply_uid',$this->user_id)->where('unionid',$val['unionid'])->count() == 1){
                    $list[$key]['topname'] = Db::name('train_cate')->where('id',$topid)->value('title').' - '.Db::name('train_cate')->where('id',$val['catid'])->value('title');
                }else{
                    $list[$key]['topname'] = Db::name('train_cate')->where('id',$topid)->value('title');
                }
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
    
    //查站点
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

    //管理员待办详情
    public function apply_detail(){
        $unionid = Request::param('unionid');
        
        $where = [];
        if(!empty($unionid)){
            $where[] = ['unionid','=',$unionid];
        }
        
        $tinfo = Db::name('train_apply')->where('apply_uid',$this->user_id)->where($where)->find();
    
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
        $topid = Db::name('train_cate')->where('id',$tinfo['catid'])->value('parentid');
        if($tinfo['type'] == 1){
            $tinfo['topname'] = Db::name('train_cate')->where('id',$topid)->value('title');
        }else{
            $tinfo['topname'] = Db::name('train_cate')->where('id',$tinfo['catid'])->value('title').' - 答题申请';
        }
        
        $tinfo['apply_time'] = date('Y-m-d H:i:s',$tinfo['apply_time']);
        
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
            ->where('apply_id',$tinfo['id'])
            ->where($where)
            ->order('id desc')
            ->select();
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $tinfo;
        return json_encode($rs_arr,true);
        exit;
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
            $catid = Db::name('train_apply')->where('unionid',$unionid)->value('catid');
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
                
                $testid = Db::name('traintest')->where('uid',$val['uid'])->where('tid',$tiku_id)->where('catid',$catid)->value('id');
                
                Db::name('traincache')->where('testid',$testid)->delete();
                
                Db::name('traintest')->where('uid',$val['uid'])->where('tid',$tiku_id)->where('catid',$catid)->delete();
            }
        }
        
        
        $rlist = Db::name('train_apply')->where('unionid',$unionid)->select();
        foreach ($rlist as $keys => $vals){
            $data['uid'] = $uid;
            $data['apply_id'] = $vals['id'];
            $data['unionid'] = $unionid;
            $data['status'] = $status;
            $data['beizhu'] = $content;
            $data['apply_uid'] = $this->user_id;
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
    
    //随机抽题
    public function randtiku(){
        $catid = Request::param('catid');
        $number = 5;
        $cate = Db::name('train_cate')->where('id',$catid)->find();
        
        if($cate){
            if($cate['is_exam'] > 1){
                echo apireturn(201,'无需考核','');die;
            }
        }else{
            echo apireturn(201,'not found','');die;
        }
        
        //判断有没有生成考核记录
        $whrs['tid'] = $cate['tiku_id'];
        $whrs['uid'] = $this->user_id;
        $whrs['catid'] = $catid;
        $tinfo =  Db::name('traintest')->where($whrs)->find();
        
        if($tinfo){
            
            if($tinfo['is_tijiao'] == 1){
                echo apireturn(201,'已交卷,无法答题','');die;
            }
            
            $whrzz[] = ['t.testid','in',$tinfo['id']];
            $tlist = Db::name('traincache')
                ->alias('t')
                ->leftJoin('tiku tk','t.tid = tk.id')
                ->field('t.id,tk.*')
                ->where($whrzz)
                ->select();
        
            // $whrzz[] = ['testid','in',$tinfo['id']];
            // $ttid= Db::name('traincache')->field('tid')->where($whrzz)->buildSql(true);
            
            // $where['tiku_id'] = $cate['tiku_id'];
            // $tlist = Db::name('tiku')->where('id','exp','In '.$ttid)->where($where)->select();
            
            foreach($tlist as $key => $val){
                $is_answer = Db::name('traincache')->where('tid',$val['id'])->where('testid',$tinfo['id'])->value('is_answer');
                $re = Db::name('traincache')->where('tid',$val['id'])->where('testid',$tinfo['id'])->value('result');
                if($is_answer > 0){
                    $tlist[$key]['results'] = $re;
                    
                    $tlist[$key]['is_answer'] = $is_answer;
                    
                    if($is_answer > 0){
                        if(Db::name('traincache')->where('tid',$val['id'])->where('testid',$tinfo['id'])->value('score') > 0){
                            $tlist[$key]['is_error'] = 2;
                        }else{
                            $tlist[$key]['is_error'] = 1;
                        }
                        
                    }else{
                        $tlist[$key]['is_error'] = 0;
                    }
                    
                    
                    $json = Db::name('traincache')->where('tid',$val['id'])->where('testid',$tinfo['id'])->value('json');
                    
                    $tlist[$key]['hang'] = json_decode($json,true);
                    
                }else{
                    $tlist[$key]['results'] = '';
                    $tlist[$key]['is_answer'] = 0;

                    if($val['type_id'] != 4){
                        $hlist = explode("\n", $val['z_result']);
                    }else{
                        $hlist = fg($val['question']);
                        $llist = explode("\n", $val['result']);
                    }

                    $arr = [];
                    $i = 0;
                    foreach ($hlist as $k=>$v) {
                        $arr[$k]['key'] = $v;
                        $arr[$k]['value'] = '';
                        $arr[$k]['is_checked'] = 0;
                        if($v == '_'){
                            $lllist = explode("^", $llist[$i]);
                            $arr[$k]['length'] = strlen(preg_replace("#[^\x{00}-\x{ff}]#u", '*', $lllist[0])) + 1;
                            $i = $i + 1;
                        }
                    }
                    
                    $tlist[$key]['hang']  = $arr;
                    
                    $tlist[$key]['is_error'] = 0;
                    
                }
                $alist = fg($val['question']);
                    
                $arrs = [];
                foreach ($alist as $k=>$v) {
                    $arrs[$k]['key'] = $v;
                }
            
                $tlist[$key]['title']  = $arrs;
            }
            
            $data_rt['tinfo'] = $tinfo;
            $data_rt['tlist'] = $tlist;
            echo apireturn(200,/*$dd['title']*/'daxuetang',$data_rt);die;
            
        }else{
            
            $where['tiku_id'] = $cate['tiku_id'];
            $tlist = Db::name('tiku')->where($where)->orderRand()->limit('0,'.$number)->select();
        
            $data['uid'] = $this->user_id;
            $data['catid'] = $catid;
            $data['score'] = 0;
            $data['mscore'] = $number;
            $data['tid'] = $cate['tiku_id'];
            $data['cnum'] = 0;
            $data['status'] = 1;
            $data['is_tijiao'] = 0;
            $data['create_time'] = time();
            $data['update_time'] = time();
            $id = Db::name('traintest')->insertGetId($data);
            $data['id'] = $id;
            
            foreach($tlist as $key => $val){
                $tlist[$key]['score'] = floatval($val['score']);

                if($val['type_id'] != 4){
                    $hlist = explode("\n", $val['z_result']);
                }else{
                    $hlist = fg($val['question']);
                    $llist = explode("\n", $val['result']);
                }

                $arr = [];
                $i = 0;
                foreach ($hlist as $k=>$v) {
                    $arr[$k]['key'] = $v;
                    $arr[$k]['value'] = '';
                    $arr[$k]['is_checked'] = 0;
                    if($v == '_'){
                        $lllist = explode("^", $llist[$i]);
                        $arr[$k]['length'] = strlen(preg_replace("#[^\x{00}-\x{ff}]#u", '*', $lllist[0])) + 1;
                        $i = $i + 1;
                    }
                }
                    
                
                $tlist[$key]['hang']  = $arr;
                $tlist[$key]['hangnum']  = count($arr);
                $tlist[$key]['is_answer'] = 0;
                
                $alist = fg($val['question']);
                
                $arrs = [];
                foreach ($alist as $k=>$v) {
                    $arrs[$k]['key'] = $v;
                }
                
                $tlist[$key]['title']  = $arrs;
                
                $tlist[$key]['is_error'] = 0;
                
                
                $reslut = '';
                
                foreach ($arr as $keys => $vals){
                    if($val['type_id'] < 4){
                        if($vals['is_checked'] == 1){
                            $reslut .= yingshe($keys)."\n";
                        }
                    }else{
                        if($val['type_id'] == 4){
                            if($vals['key'] == '_'){
                                $reslut .= $vals['value']."\n";
                            }
                        }else{
                            $reslut .= $vals['key'].$vals['value']."\n";
                        }
                    }
                }
                $reslut = rtrim($reslut,"\n");
                
                $dataz['testid'] = $id;
                $dataz['tid'] = $val['id'];
                $dataz['is_answer'] = 0;
                $dataz['json'] = json_encode($arr);
                $dataz['result'] = $reslut;
                $dataz['repeat'] = 0;
                $dataz['create_time'] = time();
                $dataz['update_time'] = time();
                Db::name('traincache')->insert($dataz);
            }
            
            $data_rt['tinfo'] = $data;
            $data_rt['tlist'] = $tlist;
            echo apireturn(200,/*$dd['title']*/'daxuetang',$data_rt);die;
        }
        
        
    }
    
    
    
    //保存
    public function savecache(){

        $testid = $this->request->param('testid');
        
        $json = $this->request->param('json');
         
        $json = json_decode($json,true);
        
        
        if(empty($testid)){
            echo apireturn(201,'testid不能为空','');
            die;
        }else{
            $whrq['id'] = $testid;
            $whrq['uid'] = $this->user_id;
            $tinfos = Db::name('traintest')->where($whrq)->find();
            if(empty($tinfos)){
                echo apireturn(201,'请先开始答题','');
                die;
            }
            
        }
    
        $whrq['id'] = $testid;
        $whrq['uid'] = $this->user_id;
        $status = Db::name('traintest')->where($whrq)->value('status');
        if($status == 2){
            echo apireturn(201,'已交卷，请勿重复提交','');
            die;
        }
        
        
        foreach ($json as $key => $val){
            $whr['testid'] = $testid;
            $whr['tid'] = $val['id'];
            
            $hang = $val['hang'];
            
            $reslut = '';
            
            foreach ($hang as $keys => $vals){
                if($val['type_id'] < 4){
                    if($vals['is_checked'] == 1){
                        $reslut .= yingshe($keys)."\n";
                    }
                }else{
                    if($val['type_id'] == 4){
                        if($vals['key'] == '_'){
                            $reslut .= $vals['value']."\n";
                        }
                    }else{
                        $reslut .= $vals['key'].$vals['value']."\n";
                    }
                }
            }
            $reslut = rtrim($reslut,"\n");
            $tinfo = Db::name('tiku')->where('id',$val['id'])->find();
            
            $aa = zhuan($reslut);

            if($tinfo['type_id'] == 2){
                $arr = explode("\n",$tinfo['result']);
                array_multisort($arr,SORT_ASC);
                $bb = implode("\n",$arr);
            }else{
                $bb = zhuan($tinfo['result']);
            }

            $cuo = 0;
            if($tinfo['type_id'] == 4){

                $arra = explode("\n",$aa);
                $arrb = explode("\n",$bb);
                foreach ($arra as $keys => $vals){
                    $zhengque = explode('^',$arrb[$keys]);
                    $zhengque = array_map("trim",$zhengque);
                    if(!in_array($vals,$zhengque)){
                        $cuo = $cuo + 1;
                    }
                }
                if($cuo > 0){
                    $data['score'] = 0;
                }else{
                    $data['score'] = $tinfo['score'];
                    $repeat = 100;
                }
            }else{
                if($aa == $bb){
                    $data['score'] = $tinfo['score'];
                }else{
                    $data['score'] = 0;
                }
            }
            similar_text($aa,$bb,$repeat);
            
            $tinfos = Db::name('traincache')->where($whr)->find();
            
            if($tinfos){
                $data['json'] = json_encode($hang);
                $data['is_answer'] = $val['is_answer'];
                $data['result'] = htmlspecialchars($reslut);
                $data['repeat'] = $repeat;
                $data['update_time'] = time();
                Db::name('traincache')->where($whr)->update($data);
            }else{
                $data['testid'] = $testid;
                $data['tid'] = $val['id'];
                $data['is_answer'] = $val['is_answer'];
                $data['json'] = json_encode($hang);
                $data['result'] = $reslut;
                $data['repeat'] = $repeat;
                $data['create_time'] = time();
                $data['update_time'] = time();
                Db::name('traincache')->insert($data);
            }
        }
        echo apireturn(200,'保存成功','');
        die;
    }
    
    
    public function subpaper(){
        
        $testid = $this->request->param('testid');
        $json = $this->request->param('json');
        
        
        if(empty($testid)){
            echo apireturn(201,'testid不能为空','');
            die;
        }else{
            $whrq['id'] = $testid;
            $whrq['uid'] = $this->user_id;
            $tinfos = Db::name('traintest')->where($whrq)->find();
            if(empty($tinfos)){
                echo apireturn(201,'请先开始答题','');
                die;
            }
        }
        
        if(empty($json)){
            echo apireturn(201,'答案不能为空','');
            die;
        }
        
        if($tinfos['status'] == 2){
            echo apireturn(201,'已交卷，请勿重复提交','');
            die;
        }

        $json = json_decode($json,true);

        foreach ($json as $key => $val){
            
            $hang = $val['hang'];
               
            $reslut = '';
            foreach ($hang as $keys => $vals){
                if($val['type_id'] < 4){
                    if($vals['is_checked'] == 1){
                        $reslut .= yingshe($keys)."\n";
                    }
                }else{
                    if($val['type_id'] == 4){
                        if($vals['key'] == '_'){
                            $reslut .= $vals['value']."\n";
                        }
                    }else{
                        $reslut .= $vals['key'].$vals['value']."\n";
                    }
                }
            }
            $reslut = rtrim($reslut,"\n");

            $tinfo = Db::name('tiku')->where('id',$val['id'])->find();
            
            $aa = zhuan($reslut);

            if($tinfo['type_id'] == 2){
                $arr = explode("\n",$tinfo['result']);
                array_multisort($arr,SORT_ASC);
                $bb = implode("\n",$arr);
            }else{
                $bb = zhuan($tinfo['result']);
            }

            $cuo = 0;
            if($tinfo['type_id'] == 4){

                $arra = explode("\n",$aa);
                $arrb = explode("\n",$bb);
                foreach ($arra as $keys => $vals){
                    $zhengque = explode('^',$arrb[$keys]);
                    $zhengque = array_map("trim",$zhengque);
                    if(!in_array($vals,$zhengque)){
                        $cuo = $cuo + 1;
                    }
                }
                if($cuo > 0){
                    $data['score'] = 0;
                }else{
                    $data['score'] = $tinfo['score'];
                    $repeat = 100;
                }
            }else{
                if($aa == $bb){
                    $data['score'] = $tinfo['score'];
                }else{
                    $data['score'] = 0;
                }
            }
            similar_text($aa,$bb,$repeat);
            
            if(Db::name('traincache')->where('testid',$testid)->where('tid',$val['id'])->find()){
                
                $data['repeat'] = $repeat;
                Db::name('traincache')->where('testid',$testid)->where('tid',$val['id'])->update($data);
            }else{
                $data['testid'] = $testid;
                $data['tid'] = $val['id'];
                $data['result'] = $reslut;
                $data['json'] = json_encode($hang);
                $data['repeat'] = $repeat;
                $data['create_time'] = time();
                $data['update_time'] = time();
                Db::name('traincache')->insert($data);
            }
            
        }
        //
        $score = Db::name('traincache')->where('testid',$testid)->sum('score');
        //错题数量
        $cnum = Db::name('traincache')->where('testid',$testid)->where('score',0)->count();
        $rnum = Db::name('traincache')->where('testid',$testid)->where('score',1)->count();
        
        $whrz['id'] = $testid;
        $datas['score'] = $score;
        $datas['status'] = 2;
        $datas['cnum'] = $cnum;
        $datas['is_tijiao'] = 1;
        
        if(Db::name('traintest')->where($whrz)->update($datas)){
            
            $data_rt['score'] = $score;
            $data_rt['cnum'] = $cnum;
            $data_rt['rnum'] = $rnum;
            $data_rt['update_time'] = time();
            echo apireturn(200,'交卷成功',$data_rt);
            die;
            
        }else{
            echo apireturn(201,'交卷失败','');
            die;
        }
        
    }
    
    //评价
    public function assess(){
        $catid = $this->request->param('catid');
        $star = $this->request->param('star');
        $content = $this->request->param('content');
      
        if(empty($catid)){
            echo apireturn(201,'请选择任务','');
            die;
        }else{
            $info = Db::name('train_cate')->where('id',$catid)->where('level',2)->find();
            if(!$info){
                echo apireturn(201,'任务不存在','');
                die;
            }else{
                if($info['is_assess'] != 1){
                    echo apireturn(201,'该任务禁止评价','');
                    die;
                }
            }
        }
        if(empty($star)){
            echo apireturn(201,'星级不能为空','');
            die;
        }
        if(empty($content)){
            echo apireturn(201,'评价内容不能为空','');
            die;
        }
        
        $data['oneid'] = $info['parentid'];
        $data['twoid'] = $catid;
        $data['star'] = $star;
        $data['content'] = $content;
        $data['uid'] = $this->user_id;
        $data['addtime'] = date('Y-m-d H:i:s',time());
        $ins = Db::name('train_assess')->insert($data);
        
        if($ins){
            echo apireturn(200,'评价成功','');
            die;
        }else{
            echo apireturn(201,'评价失败','');
            die;
        }
        
    }
    
    //修改文章和视频状态
    public function upd_status(){
        $template_id = $this->request->param('template_id');
        $status = $this->request->param('status');
        if(empty($template_id)){
            echo apireturn(201,'模版id不能为空','');
            die;
        }
        if(empty($status)){
            echo apireturn(201,'请选择状态','');
            die;
        }
        $where['uid'] = $this->user_id;
        $where['template_id'] = $template_id;
        $num = Db::name('train_status')->where($where)->count();
        if($num == 0){
            $data['template_id'] = $template_id;
            $data['uid'] = $this->user_id;
            $data['status'] = $status;
            $data['create_time'] = time();
            Db::name('train_status')->insert($data);
            
            echo apireturn(200,'add success','');
            die;
        }else{
            $whr['template_id'] = $template_id;
            $whr['uid'] = $this->user_id;
            $datas['status'] = $status;
            $datas['create_time'] = time();
            Db::name('train_status')->where($whr)->update($datas);
            
            echo apireturn(200,'edit success','');
            die;
        } 
        
    }
    
    //查询审批人
    public function apply_ulist(){
        
        $catelist = Db::name('cateuser')->where('uid',$this->user_id)->where('leixing',1)->select();
        $ulist = array();
        foreach($catelist as $key => $val){
            
            $catid = $val['catid'];
            $whr = [];
            $whr[] = ['group_ids','>',0];
            $list = Db::name('users')->where($whr)->where("find_in_set($catid,period_ruless)")->field('id,username as text')->select();
            //合并数组
            $ulist = array_merge($ulist,$list);
            
        }
        //数组去重
        $ulist = array_values(array_unique($ulist,SORT_REGULAR));
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $ulist;
        return json_encode($rs_arr,true);
        exit;
    }
    
    //提交晋级申请
    public function message(){
        $apply_uid = Request::param('apply_uid');
        if(empty($apply_uid)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择审批人';
            return json_encode($rs_arr,true);
            exit;
        }else{
            
            $uinfo = Db::name('users')->where('id',$this->user_id)->find();
            
            $new =  $uinfo['period']+1;
            if($new == 2){
                $content = '申请进入实习期';
            }else{
                $content = '申请成为正式员工';
            }
            
            if($uinfo['period'] == 1){
                $start_time = strtotime($uinfo['period_time1']);
            }else if($uinfo['period'] == 2){
                $start_time = strtotime($uinfo['period_time2']);
            }
                        
            if($uinfo['period'] == 3){
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '您是正式员工，无法申请';
                return json_encode($rs_arr,true);
                exit;
            }else{
             
                $tinfo = Db::name('train_message')->where('uid',$this->user_id)->where('period',$uinfo['period'])->find();
                if($tinfo){
                    //拒绝后的重新提交审批
                    if($tinfo['status'] == 2){
                        $data['apply_uid'] = $apply_uid;
                        $data['uid'] = $uinfo['id'];
                        $data['period'] = $uinfo['period'];
                        $data['content'] = $content;
                        $data['start_time'] = $start_time;
                        $data['create_time'] = time();
                        $data['status'] = 1;
                        Db::name('train_message')->insert($data);
                    
                        $rs_arr['status'] = 200;
                        $rs_arr['msg'] = 'success';
                        return json_encode($rs_arr,true);
                        exit;
                    }else{
                        $rs_arr['status'] = 201;
                        $rs_arr['msg'] = '请勿重复申请';
                        return json_encode($rs_arr,true);
                        exit;
                    }
                }else{
                    
                    $data['apply_uid'] = $apply_uid;
                    $data['uid'] = $uinfo['id'];
                    $data['period'] = $uinfo['period'];
                    $data['content'] = $content;
                    $data['start_time'] = $start_time;
                    $data['create_time'] = time();
                    $data['status'] = 1;
                    Db::name('train_message')->insert($data);
                    
                    $rs_arr['status'] = 200;
                    $rs_arr['msg'] = 'success';
                    return json_encode($rs_arr,true);
                    exit;
                }
                    
            } 
        }
    }
    
    //消息列表
    public function message_list(){
        $status = Request::param('status');
        $keyword = Request::param('keyword');
        
        $where = [];
        if(!empty($status)){
            $where[] = ['ta.status','=',$status];
        }
        if(!empty($keyword)){
            $where[]=['u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        $list = Db::name('train_message')->alias('ta')
            ->leftJoin('users u','u.id = ta.uid')
            ->where('apply_uid',$this->user_id)
            ->where($where)
            ->field('ta.*,u.username as username,u.mobile as mobile')
            ->order('status asc,create_time desc')
            ->select();
            
        foreach ($list as $key => $val){
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
            
            $list[$key]['username'] = Db::name('users')->where('id',$val['uid'])->value('username');
            $list[$key]['mobile'] = Db::name('users')->where('id',$val['uid'])->value('mobile');
            $list[$key]['start_time'] = date('Y-m-d H:i:s',$val['start_time']);
            $list[$key]['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
            $list[$key]['apply_time'] = date('Y-m-d H:i:s',$val['apply_time']);
            
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
    
    //消息详情
    public function message_detail(){
    
        $id = Request::param('id');
        
        $where = [];
        if(!empty($id)){
            $where[] = ['id','=',$id];
        }
        
        $tinfo = Db::name('train_message')->where('apply_uid',$this->user_id)->where($where)->find();
        if(empty($tinfo)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = 'not found';
            return json_encode($rs_arr,true);
            exit;
        }
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
       
        $day = intval((time()-$tinfo['start_time'])/86400);
       
        $tinfo['username'] = Db::name('users')->where('id',$tinfo['uid'])->value('username');
        $tinfo['mobile'] = Db::name('users')->where('id',$tinfo['uid'])->value('mobile');
        $tinfo['start_time'] = date('Y-m-d',$tinfo['start_time']);
        $tinfo['create_time'] = date('Y-m-d H:i:s',$tinfo['create_time']);
        $tinfo['apply_time'] = date('Y-m-d H:i:s',$tinfo['apply_time']);
        $tinfo['day'] = $day;
        
        
        $data['status'] = 2;
        $data['apply_time'] = time();
        Db::name('train_message')->where('id',$id)->update($data);
        
        $tinfo['task_list'] = Db::name('train_cate')->field('title')
            ->where('parentid',0)
            ->where('is_delete',1)
            ->order('sort asc')
            ->select();
            
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $tinfo;
        return json_encode($rs_arr,true);
        exit;
        
        
    }
    
     // 上传图片
    public function uploadimg()
    {
        $file = request()->file('file');
        if ($file) {
            $info = $file->move('public/upload/weixin/');
            if ($info) {
                $file = $info->getSaveName();
                $res = ['errCode'=>0,'errMsg'=>'图片上传成功','file'=>$file];
                return json($res);
            }
        }
        else{
            // 上传失败获取错误信息
            echo $file->getError();
        }
    }
    
    //上传文件
    public function uploads(){
        if(Request::isPost()) {
            $catid = Request::param('catid');
            
            //file是传文件的名称，这是webloader插件固定写入的。因为webloader插件会写入一个隐藏input，不信你们可以通过浏览器检查页面
            $file = request()->file('images');
            
            $num = Db::name('train_image')->where('uid',$this->user_id)->where('catid',$catid)->count();
            //限制小于等于9张
            if($num < 10){
                    
                $info = $file->validate(['ext' => 'jpg,png,gif,jpeg,heif'])->move('uploads/trainimg');
                
                if(empty($catid)){
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = '请选择分类';
                    return json_encode($rs_arr,true);
                    exit;
                }
            
                $url =  "/uploads/trainimg/".$info->getSaveName();
                $url = str_replace("\\","/",$url);
                
                $imageType = $info->getExtension();
                
                //压缩图片
                if($imageType == 'jpg'){
                    // 获取完整路径
                    $image =  $_SERVER['DOCUMENT_ROOT'].$url;
                    // 加载图片资源
                    $src = @imagecreatefromjpeg($image);
                    list($width,$height) = getimagesize($image); //获取图片的高度
                    $newwidth = $width;   //宽高可以设置, 楼主是想让它的宽高不变才没赋值
                    $newheight = $height;
                    if($newwidth > 5000){
                        $bili = 50;
                    }elseif($newwidth > 2560){
                        $bili = 70;
                    }else{
                        $bili = 100;
                    }
                    $tmp = imagecreatetruecolor($newwidth,$newheight); //生成新的宽高
                    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height); //缩放图像
                    $output = imagejpeg($tmp, $image, $bili); //第三个参数(0~100);越大越清晰,图片大小也高;   png格式的为(1~9)
                    // imagedestroy($tmp); 
                }elseif($imageType == 'png'){
                    $image =  $_SERVER['DOCUMENT_ROOT'].$url;
                    $src = @imagecreatefrompng($image);
                    list($width,$height) = getimagesize($image);
                    $newwidth = $width;
                    $newheight = $height;
                    if($newwidth > 5000){
                        $bili = 5;
                    }elseif($newwidth > 2560){
                        $bili = 7;
                    }else{
                        $bili = 9;
                    }
                    $tmp = imagecreatetruecolor($newwidth,$newheight);
                    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
                    $output = imagepng($tmp, $image, $bili);  //这个图片的第三参数(1~9)
                    // imagedestroy($tmp);
                }
                
                $data['uid'] = $this->user_id;
                $data['catid'] = $catid;
                $data['url'] = $url;
                $data['sort'] = 1;
                
                Db::name('train_image')->insert($data);
             
            }
            
            $photo_list = Db::name('train_image')->where('uid',$this->user_id)->where('catid',$catid)->select();
            foreach ($photo_list as $key => $val){
                $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                $photo_list[$key]['url'] = $http_type.$_SERVER['HTTP_HOST'].$val['url'];
            }
            
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = '提交成功';
            $rs_arr['data'] = $photo_list;
            return json_encode($rs_arr,true);
            exit;
            
        }
    }
    
    public function uploads_del(){
        if(Request::isPost()) {
            $id = Request::post('id');
            if(empty($id)){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = 'ID不存在';
        		return json_encode($rs_arr,true);
        		exit;
            }
            
            $whr['id'] = $id;
            $path = Db::name('train_image')->where($whr)->value('url');
            
            $paths = Env::get('root_path').'public'.$path;
         
            if (file_exists($paths)) {
                @unlink($paths);//删除
            }
            
            Db::name('train_image')->where($whr)->delete();
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] ='success';
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    
    //查询随机抽考弹窗
    public function train_status(){
        
        $tiku_id = Request::param('tiku_id');
        
        $train_status = Db::name('users')->where('id',$this->user_id)->value('train_status');
        
        if($train_status > 0){
            $msg = '已阅读';
        }else{
            $msg = 'success';
            $notes = Db::name('tikus')->field('testnotes')->where('id',$tiku_id)->find();
            if($notes){
                $data['notes'] = explode("\n", $notes['testnotes']);;
            }else{
                $data['notes'] = '';
            }
        }
        $data['train_status'] = $train_status;
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = $msg;
        $rs_arr['data'] = $data;
		return json_encode($rs_arr,true);
		exit;
    }
    
    //随机抽考弹窗状态
    public function upd_train_status(){
        
        $train_status = Db::name('users')->where('id',$this->user_id)->value('train_status');
        
        if($train_status == 0){
            $data['train_status'] = 1;
            Db::name('users')->where('id',$this->user_id)->update($data);
        }
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
		return json_encode($rs_arr,true);
		exit;
    }
}