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
use app\common\model\Product as M;

class Product extends Base
{
    protected $validate = 'Product';

    //获取产品分类
    public function catelist(){
        $list = Db::name('product_cate')->field('id,title as text')->order('sort asc')->where('is_delete',1)->where('status',1)->select();
        
        // $lists = array();
        // $a = '';
        // foreach($list as $key => $val){
            
        //     $lists = Db::name('product_type')->where('catid',$val['id'])->where('is_delete',1)->order('id asc')->select();
        //     $b = array();
        //     foreach ($lists as $keys => $vals){
        //         $b[$keys]['name'] = $vals['title'];
        //         $b[$keys]['value'] = $vals['id'];
        //     }
            
        //     //   $a .= "'".$val['title']."': {
        //     //     name: '".$val['title']."',
        //     //     value: '".$val['id']."',
        //     //     list: ".json_encode($b)."
        //     //   },";
              
              
        //     $list[$key]['name'] = $val['title']; 
        //     $list[$key]['value'] = $val['id']; 
        //     $list[$key]['list'] = json_encode($b);  
        // }
        
        // $lists = array();
        // foreach($list as $key => $val){
        //     $lists[$key][$val['title']] = $list[$key];
        // }
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $list;
        return json_encode($rs_arr,true);
        exit;
    }

    //获取产品分类
    public function typelist(){
        
        $catid = Request::param('catid');
        if(empty($catid)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = 'catid不存在';
            return json_encode($rs_arr,true);
            exit;
        }else {
            $list = Db::name('product_type')->field('id,tiku_id,title as text')->where('catid',$catid)->where('is_delete',1)->order('sort asc')->select();
         
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = 'success';
            $rs_arr['data'] = $list;
            return json_encode($rs_arr,true);
            exit;
        }
 
    }

    //获取产品参数
    public function speclist(){
        $type_id = Request::param('type_id');
        if(empty($type_id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = 'typeid不存在';
            return json_encode($rs_arr,true);
            exit;
        }else {
            $list = Db::name('spec')->where('tiku_id',$type_id)->where('is_delete',1)->order('id asc')->select();
            foreach ($list as $key => $val){
                if($val['leixing'] == 1){
                    $itemlist = Db::name('spec_item')->field('id,item')->where('spec_id',$val['id'])->where('is_delete',1)->order('sort asc,id asc')->select();
                    foreach ($itemlist as $keys => $vals){
                        $itemlist[$keys]['is_checked'] = 0;
                    }
                    $list[$key]['item'] = $itemlist;
                }else{
                    $list[$key]['item'] = '';
                }
                $list[$key]['is_answer'] = 0;
            }
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = 'success';
            $rs_arr['data'] = $list;
            return json_encode($rs_arr,true);
            exit;
        }
    }

    //添加产品
    public function user_add(){
        echo apireturn(201, '请联系管理员添加！', '');
        die;
        
        $data = Request::param();
        if(empty($data['cate_id'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择产品';
            return json_encode($rs_arr,true);
            exit;
        }
        if(empty($data['type_id'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择模型';
            return json_encode($rs_arr,true);
            exit;
        }
        if(empty($data['zhandian_id'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择站点';
            return json_encode($rs_arr,true);
            exit;
        }
        if(empty($data['is_new'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择是否是设备';
            return json_encode($rs_arr,true);
            exit;
        }
        if(empty($data['status'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择使用状态';
            return json_encode($rs_arr,true);
            exit;
        }
        // if(empty($data['collect_time'])){
        //     $rs_arr['status'] = 201;
        //     $rs_arr['msg'] = '请选择领用日期';
        //     return json_encode($rs_arr,true);
        //     exit;
        // }
        
        if(empty($data['leixing'])){
            $data['leixing'] = 1;
        }

        $data['uid'] = $this->user_id;
        $data['zhandian_uid'] = $this->user_id;
        $product_id = Db::name('product')->insertGetId($data);
        if($product_id > 0){

            $list = json_decode($data['json'],true);
            if(count($list) > 0){
                //添加参数
                foreach ($list as $val){

                    $datas['product_id'] = $product_id;
                    $datas['spec_id'] = $val['id'];

                    if($val['leixing'] == 1){
                        foreach($val['item'] as $keys => $vals){
                            if($vals['is_checked'] ==1){
                                $datas['result'] = $vals['id'];
                            }
                        }
                    }else{
                        $datas['result'] = $val['item'];
                    }
                    $datas['is_answer'] = $val['is_answer'];
                    Db::name('product_relation')->insert($datas);
                }
                //设备流转添加记录
                $dataz['uid'] = $this->user_id;
                $dataz['product_id'] = $product_id;
                $dataz['is_new'] = $data['is_new'];
                $dataz['collect_time'] = time();
                $dataz['create_time'] = time();
                $dataz['update_time'] = time();
                $dataz['apply_status'] = 1;
                $dataz['apply_content'] = '本人添加';
                $dataz['apply_time'] = time();
                Db::name('product_flow')->insert($dataz);
                
                echo apireturn(200, '添加成功', '');
                die;
            }else{
                echo apireturn(201, '无参数', '');
                die;
            }

        }else{
            echo apireturn(201, '添加失败', '');
            die;
        }
    }
    
    //管理员添加
    public function admin_add(){
        
        $data = Request::param();
        if(empty($data['cate_id'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择设备分类';
            return json_encode($rs_arr,true);
            exit;
        }
        if(empty($data['type_id'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择模型';
            return json_encode($rs_arr,true);
            exit;
        }
        if(empty($data['zhandian_id'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择站点';
            return json_encode($rs_arr,true);
            exit;
        }
        if(empty($data['is_new'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择是否是设备';
            return json_encode($rs_arr,true);
            exit;
        }
        // if(empty($data['collect_time'])){
        //     $rs_arr['status'] = 201;
        //     $rs_arr['msg'] = '请选择领用日期';
        //     return json_encode($rs_arr,true);
        //     exit;
        // }
        if(empty($data['status'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择使用状态';
            return json_encode($rs_arr,true);
            exit;
        }else{
            if($data['status'] == 2){
                if(empty($data['reason'])){
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = '请选择闲置原因';
                    return json_encode($rs_arr,true);
                    exit;
                }
            }
        }
        if(empty($data['leixing'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择资产权限';
            return json_encode($rs_arr,true);
            exit;
        }
        if(empty($data['uid'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择负责人';
            return json_encode($rs_arr,true);
            exit;
        }
        
        $data['zhandian_uid'] = $this->user_id;
        $product_id = Db::name('product')->insertGetId($data);
        if($product_id > 0){

            $list = json_decode($data['json'],true);
            if(count($list) > 0){
                //添加参数
                foreach ($list as $val){

                    $datas['product_id'] = $product_id;
                    $datas['spec_id'] = $val['id'];

                    if($val['leixing'] == 1){
                        foreach($val['item'] as $keys => $vals){
                            if($vals['is_checked'] ==1){
                                $datas['result'] = $vals['id'];
                            }
                        }
                    }else{
                        $datas['result'] = $val['item'];
                    }
                    
                    $datas['is_answer'] = $val['is_answer'];
                    Db::name('product_relation')->insert($datas);
                }
                //设备流转添加记录
                $dataz['uid'] = $data['uid'];
                $dataz['product_id'] = $product_id;
                $dataz['is_new'] = $data['is_new'];
                $dataz['collect_time'] = time();
                $dataz['create_time'] = time();
                $dataz['update_time'] = time();
                $dataz['apply_uid'] = $this->user_id;
                if($data['uid'] == $this->user_id){
                    $dataz['apply_status'] = 1;
                    $dataz['apply_content'] = '本人添加';
                }
                Db::name('product_flow')->insert($dataz);
                
                echo apireturn(200, '添加成功', '');
                die;
            }else{
                echo apireturn(201, '无参数', '');
                die;
            }

        }else{
            echo apireturn(201, '添加失败', '');
            die;
        }
    }
    
    //管理员修改
    public function admin_upd(){
        
        $data = Request::param();
        if(empty($data['id'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择设备';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $pinfo = Db::name('product')->where('id',$data['id'])->find();   
        }
        if(!$pinfo){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '设备不存在';
            return json_encode($rs_arr,true);
            exit;  
        }
        if(empty($data['cate_id'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择设备分类';
            return json_encode($rs_arr,true);
            exit;
        }else{
            if($data['cate_id'] != $pinfo['cate_id']){
                $dataz['product_id'] = $pinfo['id'];
                $dataz['canshu'] = '设备分类';
                $dataz['old'] = Db::name('product_cate')->where('id',$pinfo['cate_id'])->value('title');
                $dataz['new'] = Db::name('product_cate')->where('id',$data['cate_id'])->value('title');
                $dataz['create_time'] = time();
                Db::name('product_update')->insert($dataz);
            }
        }
        
        if(empty($data['type_id'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择设备类型';
            return json_encode($rs_arr,true);
            exit;
        }else{
            if($data['type_id'] != $pinfo['type_id']){
                $dataz['product_id'] = $pinfo['id'];
                $dataz['canshu'] = '设备类型';
                $dataz['old'] = Db::name('product_type')->where('id',$pinfo['type_id'])->value('title');
                $dataz['new'] = Db::name('product_type')->where('id',$data['type_id'])->value('title');
                $dataz['create_time'] = time();
                Db::name('product_update')->insert($dataz);
            }
        }
        
        if(empty($data['zhandian_id'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择站点';
            return json_encode($rs_arr,true);
            exit;
        }else{
            if($data['zhandian_id'] != $pinfo['zhandian_id']){
                $dataz['product_id'] = $pinfo['id'];
                $dataz['canshu'] = '所属站点';
                $dataz['old'] = Db::name('cate')->where('id',$pinfo['zhandian_id'])->value('title');
                $dataz['new'] = Db::name('cate')->where('id',$data['zhandian_id'])->value('title');
                $dataz['create_time'] = time();
                Db::name('product_update')->insert($dataz);
            }
        }
        if(empty($data['is_new'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择是否是全新设备';
            return json_encode($rs_arr,true);
            exit;
        }else{
            if($data['is_new'] != $pinfo['is_new']){
                $dataz['product_id'] = $pinfo['id'];
                $dataz['canshu'] = '是否全新设备';
                if($pinfo['is_new'] == 1){
                    $old = '是';
                }else{
                    $old = '否';
                }    
                if($data['is_new'] == 1){
                    $new = '是';
                }else{
                    $new = '否';
                }
                $dataz['old'] = $old;
                $dataz['new'] = $new;
                $dataz['create_time'] = time();
                Db::name('product_update')->insert($dataz);
            }
        }
        // if(empty($data['collect_time'])){
        //     $rs_arr['status'] = 201;
        //     $rs_arr['msg'] = '请选择领用日期';
        //     return json_encode($rs_arr,true);
        //     exit;
        // }else{
            if($data['collect_time'] != $pinfo['collect_time']){
                $dataz['product_id'] = $pinfo['id'];
                $dataz['canshu'] = '领用日期';
                $dataz['old'] = date('Y-m-d',$pinfo['collect_time']);
                $dataz['new'] = date('Y-m-d',$data['collect_time']);
                $dataz['create_time'] = time();
                Db::name('product_update')->insert($dataz);
            }
        // }
        if(empty($data['status'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择使用状态';
            return json_encode($rs_arr,true);
            exit;
        }else{
            if($data['status'] != $pinfo['status']){
                $dataz['product_id'] = $pinfo['id'];
                $dataz['canshu'] = '使用状态';
                if($pinfo['status'] == 1){
                    $old = '在用';
                }else{
                    $old = '闲置';
                }    
                if($data['status'] == 1){
                    $new = '在用';
                }else{
                    $new = '闲置';
                }
                $dataz['old'] = $old;
                $dataz['new'] = $new;
                $dataz['create_time'] = time();
                Db::name('product_update')->insert($dataz);
                
            }
            if($data['reason'] != $pinfo['reason']){
                $dataz['product_id'] = $pinfo['id'];
                $dataz['canshu'] = '闲置原因';
                $dataz['old'] = $pinfo['reason'];
                $dataz['new'] = $data['reason'];
                $dataz['create_time'] = time();
                Db::name('product_update')->insert($dataz);
            }
        }
        
        
        
        if(empty($data['leixing'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择资产权限';
            return json_encode($rs_arr,true);
            exit;
        }else{
            if($data['leixing'] != $pinfo['leixing']){
                $dataz['product_id'] = $pinfo['id'];
                $dataz['canshu'] = '资产权限';
                if($pinfo['leixing'] == 1){
                    $old = '个人使用';
                }else{
                    $old = '站点资产管理';
                }    
                if($data['leixing'] == 1){
                    $new = '个人使用';
                }else{
                    $new = '站点资产管理';
                }
                $dataz['old'] = $old;
                $dataz['new'] = $new;
                $dataz['create_time'] = time();
                Db::name('product_update')->insert($dataz);
            }
        }
        if(empty($data['uid'])){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择负责人';
            return json_encode($rs_arr,true);
            exit;
        }else{
            if($data['uid'] != $pinfo['uid']){
                
                $dataz['product_id'] = $pinfo['id'];
                $dataz['canshu'] = '资产负责人';
                $dataz['old'] = Db::name('users')->where('id',$pinfo['uid'])->value('username');
                $dataz['new'] = Db::name('users')->where('id',$data['uid'])->value('username');
                $dataz['create_time'] = time();
                Db::name('product_update')->insert($dataz);
                
                $adminname = Db::name('users')->where('id',$this->user_id)->value('username');
                if($pinfo['leixing'] == 1){
                    $zhuangtai = '使用';
                }else{
                    $zhuangtai = '管理';
                }
                //增加消息
                $datam['uid'] = $pinfo['uid'];
                $datam['content'] = $adminname.'将您'.$zhuangtai.'的 '.Db::name('product_cate')->where('id',$pinfo['cate_id'])->value('title').' '.Db::name('product_type')->where('id',$pinfo['type_id'])->value('title').' 转给他人';
                $datam['status'] = 1;
                $datam['create_time'] = date('Y-m-d H:i:s',time());
                Db::name('message')->insert($datam);
            }
        }
        
        if($data['remark'] != $pinfo['remark']){
            $dataz['product_id'] = $pinfo['id'];
            $dataz['canshu'] = '设备存放地点';
            $dataz['old'] = $pinfo['remark'];
            $dataz['new'] = $data['remark'];
            $dataz['create_time'] = time();
            Db::name('product_update')->insert($dataz);
        }
            
        $data['update_time'] = time();
        $m = new M();
        $result = $m->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
            $rs_arr['msg'] = $result['msg'];
            return json_encode($rs_arr,true);
            exit;
        }else{
            
            $list = json_decode($data['json'],true);
            if(count($list) > 0){
                //添加参数
                //Db::name('product_relation')->where('product_id',$pinfo['id'])->delete();
                
                $specids = '';
                $result = '';
                foreach ($list as $key => $val){
                    
                    $specids .= $val['id'].',';
                    
                    $datas['product_id'] = $pinfo['id'];
                    $datas['spec_id'] = $val['id'];

                    if($val['leixing'] == 1){
                        foreach($val['item'] as $keys => $vals){
                            if($vals['is_checked'] ==1){
                                $result = $vals['id'];
                            }
                        }
                    }else{
                        $result = $val['item'];
                    }
                    
                    //查询参数有无
                    $sinfo = Db::name('product_relation')->where('product_id',$pinfo['id'])->where('spec_id',$val['id'])->find();
                    
                    if($sinfo){
                        //修改参数
                        if($result != $sinfo['result']){
                            $dataupd['product_id'] = $pinfo['id'];
                            $dataupd['canshu'] = $val['title'];
                            if($val['leixing'] == 1){
                                $dataupd['old'] = Db::name('spec_item')->where('id',$sinfo['result'])->value('item');
                                $dataupd['new'] = Db::name('spec_item')->where('id',$result)->value('item');
                            }else{
                                $dataupd['old'] = $sinfo['result'];
                                $dataupd['new'] = $result;
                            }
                            $dataupd['create_time'] = time();
                            Db::name('product_update')->insert($dataupd);
                
                            $upd['result'] = $result;
                            $upd['is_answer'] = $val['is_answer'];
                            Db::name('product_relation')->where('product_id',$pinfo['id'])->where('spec_id',$val['id'])->update($upd);
                        }
                    }else{
                        
                        $dataupd['product_id'] = $pinfo['id'];
                        $dataupd['canshu'] = $val['title'];
                        if($val['leixing'] == 1){
                            $dataupd['old'] = '新增';
                            $dataupd['new'] = Db::name('spec_item')->where('id',$result)->value('item');
                        }else{
                            $dataupd['old'] = '新增';
                            $dataupd['new'] = $result;
                        }
                        $dataupd['create_time'] = time();
                        Db::name('product_update')->insert($dataupd);
                        
                        //添加新参数
                        $datas['result'] = $result;
                        $datas['is_answer'] = $val['is_answer'];
                        Db::name('product_relation')->insert($datas);
                    }
                }
                
                
                $whrd = [];
                $specids = rtrim($specids,',');
                $whrd[] = ['spec_id','not in',$specids];
                $dlist = Db::name('product_relation')->where('product_id',$pinfo['id'])->where($whrd)->select();
                
                foreach($dlist as $key => $val){
                    $dataupd['product_id'] = $val['product_id'];
                    $dataupd['canshu'] = Db::name('spec')->where('id',$val['spec_id'])->value('title');
                    $leixings = Db::name('spec')->where('id',$val['spec_id'])->value('leixing');
                    if($leixings == 1){
                        $dataupd['old'] = Db::name('spec_item')->where('id',$val['result'])->value('item');
                        $dataupd['new'] = '已删除';
                    }else{
                        $dataupd['old'] = $val['result'];
                        $dataupd['new'] = '已删除';
                    }
                    $dataupd['create_time'] = time();
                    Db::name('product_update')->insert($dataupd);
                    Db::name('product_relation')->where('id',$val['id'])->delete();
                }
                
                
                
                //查询最后使用人
                $pfinfo = Db::name('product_flow')->where('product_id',$pinfo['id'])->order('id desc')->find();
                if($pfinfo['uid'] != $data['uid']){
                    $dataend['end_time'] = time();
                    $dataend['status'] = 2;
                    Db::name('product_flow')->where('id',$pfinfo['id'])->update($dataend);
                    
                    //设备流转添加记录
                    $datazz['uid'] = $data['uid'];
                    $datazz['product_id'] = $pinfo['id'];
                    $datazz['is_new'] = $data['is_new'];
                    $datazz['collect_time'] = time();
                    $datazz['create_time'] = time();
                    $datazz['update_time'] = time();
                    $datazz['apply_uid'] = $this->user_id;
                    Db::name('product_flow')->insert($datazz);
                }
                
                
                echo apireturn(200, '修改成功', '');
                die;
            }else{
                echo apireturn(201, '无参数', '');
                die;
            }
            
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = $result['msg'];
            return json_encode($rs_arr,true);
            exit;
        }
    }
    
    //获取用户的产品分类
    public function product_cate(){
      
        $list = Db::name('product')
                ->alias('p')
                ->leftJoin('product_cate pt','p.cate_id = pt.id')
                ->field('p.cate_id,pt.title as catename')
                ->where('p.uid',$this->user_id)
                ->group('p.cate_id')
                ->select();

        echo apireturn(200, 'success', $list);
        die;
    
    }

    //获取用户的产品列表
    public function product_list(){
        
        $cate_id = Request::param('cate_id');
        $status = Request::param('status');
        $leixing = Request::param('leixing');
        
        $where = [];
        if(!empty($cate_id)){
            $where[] = ['p.cate_id','in',$cate_id];
        }
        if(!empty($status)){
            $where[] = ['p.status','in',$status];
        }
        if(!empty($leixing)){
            $where[] = ['p.leixing','in',$leixing];
        }
        
        $where[] = ['p.uid','=',$this->user_id];
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;

        $list = Db::name('product')
            ->alias('p')
            ->leftJoin('product_cate pc','p.cate_id = pc.id')
            ->leftJoin('product_type pt','p.type_id = pt.id')
            ->field('p.id,p.cate_id,p.type_id,p.leixing,p.status,p.reason,pc.title as catename,pt.title as typename')
            ->where($where)
            ->order('p.status asc,p.leixing desc,p.id desc')
            ->select();
        foreach($list as $key => $val){
            //查询自己的申请记录
            // $whr1['pr.product_id'] = $val['id'];
            // $list[$key]['item_list'] = Db::name('product_relation')
            //     ->alias('pr')
            //     ->leftJoin('spec pc','pr.spec_id = pc.id')
            //     ->leftJoin('spec_item pt','pr.result = pt.id')
            //     ->field('pr.*,pc.title as spec_name,pt.item as spec_item_name')
            //     ->where($whr1)
            //     ->order('pr.id asc')
            //     ->select();
            $whr1['product_id'] = $val['id'];
            $whr1['uid'] = $this->user_id;
            $list[$key]['apply_status'] = Db::name('product_flow')->where($whr1)->order('id desc')->value('apply_status');
            $list[$key]['apply_id'] = Db::name('product_flow')->where($whr1)->order('id desc')->value('id');
            
        }
        
        $list1 = seacharr_by_value($list,'apply_status',0);
        $list2 = seacharr_by_value($list,'apply_status',1);
        
        
        $zycount = seacharr_by_value($list2,'status',1);
        $xzcount = seacharr_by_value($list2,'status',2);
        $bfcount = seacharr_by_value($list2,'status',3);
        
        $data_rt['zycount'] = count($zycount);
        $data_rt['xzcount'] = count($xzcount);
        $data_rt['bfcount'] = count($bfcount);
        
        $list1 = array_slice($list1,0,1000);
        $data_rt['djs_data'] = $list1;
        $data_rt['total'] = count($list2);
        $list2 = array_slice($list2,$b,$pageSize);
        $data_rt['yjs_data'] = $list2;

        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $data_rt;
        return json_encode($rs_arr,true);
        exit;
        
    }
    
    //获取管理员的产品列表
    public function product_lists(){
        $cate_id = Request::param('cate_id');
        $status = Request::param('status');
        $leixing = Request::param('leixing');
        $zhandian_id = Request::param('zhandian_id');
        $keyword = Request::param('keyword');
        
        $where = [];
        
        if(!empty($zhandian_id)){
            $uinfo = Db::name('users')->where('id',$this->user_id)->find();
            if($uinfo['group_device'] == 1 || $uinfo['group_device'] == 2 || $uinfo['group_device'] == 3 || $uinfo['group_device'] == 4){
            
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
                $ruless = explode(',',$uinfo['ruless']);
               
                if(in_array($zhandian_id,$ruless)){
                    $where[] = ['p.zhandian_id','=',$zhandian_id];
                }else{
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = '站点id有误';
                    return json_encode($rs_arr,true);
                    exit;
                }
            }
        }else{
            $uinfo = Db::name('users')->where('id',$this->user_id)->find();
            if($uinfo['group_device'] == 1 || $uinfo['group_device'] == 2 || $uinfo['group_device'] == 3 || $uinfo['group_device'] == 4){
                $where[] = ['p.zhandian_id','>',0];
            }else{
                $ruless = explode(',',$uinfo['ruless']);
                $where[] = ['p.zhandian_id','in',$uinfo['ruless']];
            }
        }
        if(!empty($status)){
            $where[] = ['p.status','in',$status];
        }
        if(!empty($leixing)){
            $where[] = ['p.leixing','in',$leixing];
        }
        if(!empty($cate_id)){
            $where[] = ['p.cate_id','in',$cate_id];
        }
        if(!empty($keyword)){
            $where[]=['u.username', 'like', '%'.$keyword.'%'];
        }
        //$where[] = ['p.uid','=',$this->user_id];
        
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;

        
        $list = Db::name('product')
            ->alias('p')
            ->leftJoin('product_cate pc','p.cate_id = pc.id')
            ->leftJoin('product_type pt','p.type_id = pt.id')
            ->leftJoin('users u','p.uid = u.id')
            ->field('p.id,p.cate_id,p.type_id,p.status,p.leixing,p.reason,p.create_time,pc.title as catename,pt.title as typename,u.username')
            ->where($where)
            ->order('p.status asc,p.create_time desc,p.id desc')
            ->select();
        foreach($list as $key => $val){
            //查询自己的申请记录
            $whr1['pr.product_id'] = $val['id'];
            $list[$key]['item_list'] = Db::name('product_relation')
                ->alias('pr')
                ->leftJoin('spec pc','pr.spec_id = pc.id')
                ->leftJoin('spec_item pt','pr.result = pt.id')
                ->field('pr.*,pc.title as spec_name,pt.item as spec_item_name')
                ->where($whr1)
                ->order('pr.id asc')
                ->select();
        }
        
        $zycount = seacharr_by_value($list,'status',1);
        $xzcount = seacharr_by_value($list,'status',2);
        $bfcount = seacharr_by_value($list,'status',3);
        
        $data_rt['zycount'] = count($zycount);
        $data_rt['xzcount'] = count($xzcount);
        $data_rt['bfcount'] = count($bfcount);

        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;

        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $data_rt;
        return json_encode($rs_arr,true);
        exit;
        
    }
    
    //查询产品详情
    public function product_detail(){
        $id = Request::param('id');
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择产品';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $where['p.id'] = $id;
        }

        // $where['p.uid'] = $this->user_id;

        $pinfo = Db::name('product')
            ->alias('p')
            ->leftJoin('product_cate pc','p.cate_id = pc.id')
            ->leftJoin('product_type pt','p.type_id = pt.id')
            ->leftJoin('users u','p.uid = u.id')
            ->leftJoin('cate c','p.zhandian_id = c.id')
            ->field('p.id,p.status,p.is_new,p.leixing,p.reason,p.collect_time,p.cate_id,p.type_id,p.remark,pc.title as catename,pt.title as typename,u.username as name,c.title as zhandian_name')
            ->where($where)
            ->find();
            
        
        //$whr1['uid'] = $this->user_id;
        $whr1['product_id'] = $id;
        //$whr1['status'] = 2;
        
        $pinfo['apply_status'] = Db::name('product_flow')->where($whr1)->order('id desc')->value('apply_status');
        $pinfo['apply_content'] = Db::name('product_flow')->where($whr1)->order('id desc')->value('apply_content');
    
        
        $pinfo['useless_status'] = Db::name('product_apply')->where($whr1)->order('id desc')->value('status');
        $pinfo['useless_content'] = Db::name('product_apply')->where($whr1)->order('id desc')->value('apply_content');
        $pinfo['useless_reason'] = Db::name('product_apply')->where($whr1)->order('id desc')->value('apply_reason');
    

        //查询自己的申请记录
        // $whr1['product_id'] = $id;
        // $whr1['uid'] = $this->user_id;
        // $apply = Db::name('product_apply')->where($whr1)->find();
        // if($apply){
        //     $pinfo['apply_num'] = 1;
        //     $pinfo['apply_leixing'] = $apply['leixing'];
        //     $pinfo['apply_status'] = $apply['status'];
        // }else{
        //     $pinfo['applynum'] = 0;
        //     $pinfo['apply_leixing'] = '';
        //     $pinfo['apply_status'] = '';
        // }


        $whr2['product_id'] = $id;
        $item_list = Db::name('product_relation')
            ->alias('pr')
            ->leftJoin('spec pc','pr.spec_id = pc.id')
            ->leftJoin('spec_item pt','pr.result = pt.id')
            ->field('pr.*,pc.title as spec_name,pc.leixing,pt.item as spec_item_name')
            ->where($whr2)
            ->order('pr.id asc')
            ->select();
        foreach ($item_list as $key => $val){
            if($val['leixing'] == 2){
                $item_list[$key]['spec_item_name'] = $val['result'];
            }
        }

        $pinfo['item_list'] = $item_list;
        if(!empty($pinfo['collect_time'])){
            $pinfo['ctime'] = date("Y-m-d",$pinfo['collect_time']);
        }else{
            $pinfo['ctime'] = '/';
        }
        
        
        $whereupd[] = ['product_id','=',$id];
     
        $pinfo['updnum'] = Db::name('product_update')
            ->where($whereupd)
            ->count();
            
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $pinfo;
        return json_encode($rs_arr,true);
        exit;
    }
    
    //普通用户接收设备
    public function apply(){
    
        $id = Request::param('id');
        $apply_status = Request::param('apply_status');
        $apply_content = Request::param('apply_content');
        
        
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择产品';
            return json_encode($rs_arr,true);
            exit;
        }

        if(empty($apply_status)){
            echo apireturn(201, '请确认接收/拒收', '');
            die;
        }
        //查询当前状态
        $info = Db::name('product_flow')->where('product_id',$id)->where('uid',$this->user_id)->order('id desc')->find();
        
        $adminname = Db::name('users')->where('id',$this->user_id)->value('username');
        
        if($info){
            if($info['apply_status'] == 1){
                echo apireturn(201, '已同意', '');
                die;
            }elseif($info['apply_status'] == 2){
                echo apireturn(201, '已拒绝', '');
                die;
            }else{
                //更新审批状态
                $data['apply_status'] = $apply_status;
                $data['apply_content'] = $apply_content;
                $data['update_time'] = time();
                $data['apply_time'] = time();
                
                Db::name('product_flow')->where('id',$info['id'])->where('uid',$this->user_id)->update($data);
                //$infos = Db::name('product_flow')->where('id',$info['id'])->where('uid',$this->user_id)->find();
             
                $pinfo = Db::name('product')->where('id',$info['product_id'])->find();
                if($apply_status == 2){
                    $dataz['uid'] = $pinfo['zhandian_uid'];
                    Db::name('product')->where('id',$info['product_id'])->update($dataz);
                    
                    //退回记录
                    $dataend['end_time'] = time();
                    $dataend['status'] = 2;
                    Db::name('product_flow')->where('id',$info['id'])->update($dataend);
                
                    //设备流转添加记录
                    $datazz['uid'] = $pinfo['zhandian_uid'];
                    $datazz['product_id'] = $info['product_id'];
                    $datazz['is_new'] = $info['is_new'];
                    $datazz['collect_time'] = time();
                    $datazz['create_time'] = time();
                    $datazz['update_time'] = time();
                    $datazz['apply_uid'] = $info['apply_uid'];
                    $datazz['apply_status'] = 1;
                    $datazz['apply_content'] = Db::name('users')->where('id',$this->user_id)->value('username').'拒收退回';
                    Db::name('product_flow')->insert($datazz);
                
                
                    //增加拒收消息
                    $datam['uid'] = $info['apply_uid'];
                    if(!empty($apply_content)){
                        $datam['content'] = $adminname.'拒收了您指派的 '.Db::name('product_cate')->where('id',$pinfo['cate_id'])->value('title').' '.Db::name('product_type')->where('id',$pinfo['type_id'])->value('title').',拒收原因：'.$apply_content.'。';
                    }else{
                        $datam['content'] = $adminname.'拒收了您指派的 '.Db::name('product_cate')->where('id',$pinfo['cate_id'])->value('title').' '.Db::name('product_type')->where('id',$pinfo['type_id'])->value('title').',拒收原因：无。';
                    }
                    $datam['status'] = 1;
                    $datam['create_time'] = date('Y-m-d H:i:s',time());
                    Db::name('message')->insert($datam);
                }else{
                    //增加接收消息
                    $datam['uid'] = $info['apply_uid'];
                    $datam['content'] = $adminname.'接收了您指派的 '.Db::name('product_cate')->where('id',$pinfo['cate_id'])->value('title').' '.Db::name('product_type')->where('id',$pinfo['type_id'])->value('title');
                    $datam['status'] = 1;
                    $datam['create_time'] = date('Y-m-d H:i:s',time());
                    Db::name('message')->insert($datam);
                }
                echo apireturn(200, '提交成功', '');
                die;
               
            }
        }else{
            echo apireturn(201, '错误的id', '');
            die;
        }

        
    }
 
    //查询所属站点
    public function zhandianlist(){

        $list = Db::name('cateuser')
            ->alias('cu')
            ->leftJoin('cate ca','cu.catid = ca.id')
            ->field('cu.*,ca.title as text')
            ->where('cu.uid',$this->user_id)
            ->where('cu.leixing',1)
            ->select();
       
        $data_rt['status'] = 200;
        $data_rt['msg'] = 'success';
        $data_rt['data'] = $list;
        //print_r($list);
        return json_encode($data_rt);
        exit;

    }
    
    //获取架构
    public function jiagou(){

        $parentid = input('parentid');
        
        $uinfo = Db::name('users')->where('id',$this->user_id)->find();
        if($uinfo['group_device'] == 1 || $uinfo['group_device'] == 2 || $uinfo['group_device'] == 3 || $uinfo['group_device'] == 4){

            $where=[];
            if($parentid){
                $where[]=['parentid', '=', $parentid];
            }else{
                $where[] = ['parentid','=','1'];
            }
           
            $list = Db::name('cate')->where($where)->order('sort asc')->select();


            foreach ($list as $key => $val){

                $wheres['parentid'] = $val['id'];
                $zzlist = Db::name('cate')->where($wheres)->select();

                $list[$key]['friend'] = count($zzlist);
                
                
                $wheress['id'] = $val['parentid'];
                $parentids = Db::name('cate')->where($wheress)->value('parentid');
                if($parentids){
                    $list[$key]['parentids'] = $parentids;
                }else{
                    $list[$key]['parentids'] = 1;
                }
                

            }


        }else{

            $where=[];
            $where[] = ['id','in',$uinfo['ruless']];

            $list = Db::name('cate')->where($where)->order('sort asc')->select();

            foreach ($list as $key => $val){
                
                $list[$key]['friend'] = 0;
                
            }


        }

        $data_rt['status'] = 200;
        $data_rt['msg'] = 'success';
        $data_rt['data'] = $list;
        //print_r($list);
        return json_encode($data_rt);
        exit;

    }
    
    //查询产品修改记录
    public function updlist(){
        $id = Request::param('id');
        
        $where = [];
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择产品';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $where[] = ['product_id','=',$id];
        }


        $list = Db::name('product_update')
            ->field('DATE_FORMAT(FROM_UNIXTIME(create_time),"%Y-%m-%d") as deta_time')
            ->where($where)
            ->order('create_time desc')
            ->group("DATE_FORMAT(FROM_UNIXTIME(create_time),'%Y-%m-%d')")
            ->select();
        
        foreach($list as $key => $val){
            $start = strtotime($val['deta_time']);
            $end = $start + 86400;
            
            unset($wheres);
            $wheres[] = ['create_time','>',$start];
            $wheres[] = ['create_time','<',$end];
            
            $list[$key]['list'] = Db::name('product_update')->where($where)->where($wheres)->order('create_time desc')->select();
        }
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $list;
        return json_encode($rs_arr,true);
        exit;
    }
    
    //查询站点名称
    public function catename(){
        $id = Request::param('id');
        
        $where = [];
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请输入id';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $where[] = ['id','=',$id];
        }

        $title = Db::name('cate')
            ->where($where)
            ->value('title');
            
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $title;
        return json_encode($rs_arr,true);
        exit;
    }
    
    //闲置类型
    public function reasonlist(){
        $arr = array(array('id'=>1,'text'=>'储备使用'),array('id'=>2,'text'=>'人员离职'),array('id'=>3,'text'=>'人员流动'),array('id'=>4,'text'=>'生产变动'),array('id'=>5,'text'=>'设备损坏'));
            
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $arr;
        return json_encode($rs_arr,true);
        exit;
    }
    
    //闲置处理方式
    public function useless_type(){
        $arr = array(array('id'=>1,'text'=>'卖废品'),array('id'=>2,'text'=>'拆机'),array('id'=>3,'text'=>'其他'));
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $arr;
        return json_encode($rs_arr,true);
        exit;
    }
    
    //获取报废审批管理员列表
    public function admin_list(){
        $ulist = Db::name('users')->field('id,username as text')->where('group_device',3)->select();
        if(count($ulist) > 0){
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = 'success';
            $rs_arr['data'] = $ulist;
            return json_encode($rs_arr,true);
            exit; 
        }else{
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '无报废审批员,请联系管理员';
            return json_encode($rs_arr,true);
            exit;
        }
        
    }
    
    //提交报废申请
    public function useless(){
        $product_id = Request::param('product_id');
        $apply_reason = Request::param('apply_reason');
        $apply_content = Request::param('apply_content');
        $reply_uid = Request::param('reply_uid');
        if(empty($product_id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择产品';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $data['product_id'] = $product_id;
        }
        if(!empty($apply_reason)){
            $data['apply_reason'] = $apply_reason;
        }
        if(!empty($apply_content)){
            $data['apply_content'] = $apply_content;
        }

        if(empty($reply_uid)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择审批人';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $uinfo = Db::name('users')->where('id',$reply_uid)->where('group_device',3)->find();
            
            $data['reply_uid'] = $reply_uid;
        }

        $num = Db::name('product_apply')->where('product_id',$product_id)->where('uid',$this->user_id)->where('status',1)->count();
        if($num > 0){
            echo apireturn(201, '申请中', '');
            die;
        }else{
            $data['uid'] = $this->user_id;
            $data['create_time'] = time();
            $data['update_time'] = time();
            Db::name('product_apply')->insert($data);

            echo apireturn(200, '提交成功', '');
            die;
        }
    }
    
    //获取负责人列表
    public function userlist(){
        //条件筛选
        $keyword = Request::param('keyword');
        $catid = Request::param('catid');
        $groupId = Request::param('group_device');
        $did = Request::param('did');

        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['u.username|u.mobile', 'like', '%'.$keyword.'%'];
        }
        
        $whr1=[];

        $uinfo = Db::name('users')->where('id',$this->user_id)->find();
        if($uinfo['group_device'] == 1 || $uinfo['group_device'] == 2 || $uinfo['group_device'] == 3 || $uinfo['group_device'] == 4){
            if(!empty($catid)){
                $whr1[]=['catid', '=', $catid];

                $uids = Db::name('cateuser')
                    ->where($whr1)
                    ->field('uid') 
                    ->select();

                $a = '';
                foreach($uids as $key => $val){
                    $a .= $val['uid'].',';
                }
                $where[]=['u.id', 'in', $a];

            }
        }else{
            if(!empty($catid)){
                if(in_array($catid,explode(',',$uinfo['ruless']))){
                    $whr1[]=['catid', '=', $catid];

                    $uids = Db::name('cateuser')
                        ->where($whr1)
                        ->field('uid')
                        ->select();

                    $a = '';
                    foreach($uids as $key => $val){
                        $a .= $val['uid'].',';
                    }
                    $where[]=['u.id', 'in', $a];
                }else{
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = '非管理员';
                    return json_encode($rs_arr,true);
                    exit;
                }

            }else{
                $whr1[] = ['catid','in',$uinfo['ruless']];
                $uids = Db::name('cateuser')
                    ->where($whr1)
                    ->field('uid')
                    ->select();

                $a = '';
                foreach($uids as $key => $val){
                    $a .= $val['uid'].',';
                }
                $where[]=['u.id', 'in', $a];
            }
        }
        
        $whrq=[];
        if(!empty($groupId)){
            $where[]=['u.group_device', '=', $groupId];
        }
        $members = array();
        if($did){
            $member = Db::name('daxuetang')
                ->where('id',$did)
                ->value('member');
            if($member){
                $members = explode(',',$member);
            }else{
                $members = array();
            }
        }
        $where[]=['u.id', '>', '1'];
        $where[]=['u.is_delete', '=', '1'];
       
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : config('page');
        
        $a = $pageSize*($page-1);
        
        $count = Db::name('users')
            ->alias('u')
            ->leftJoin('auth_group ag','ag.id = u.group_device')
            ->leftJoin('cateuser cu','cu.uid = u.id')
            ->leftJoin('cate c','c.id = u.id')
            ->field('u.id,u.username,u.mobile,ag.title as group_name')
            ->order('u.id ASC')
            ->group('u.id')
            ->where($where)
            ->count();
        
        //调取列表
        $list = Db::name('users')
            ->alias('u')
            ->leftJoin('auth_group ag','u.group_device = ag.id')
            ->field('u.id,u.username,u.mobile,ag.title as group_name')
            ->order('u.id ASC')
            ->limit($a.','.$pageSize)
            ->group('u.id')
            ->where($where)
            ->select();
        

        foreach ($list as $key => $val){
            $list[$key]['mobile'] = substr_replace($val['mobile'],'****',-8,-4);
        }
          
        $rlist['count'] = $count;
        $rlist['data'] = $list;
          
         
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $rlist;
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
    
    //查询设备详情
    public function product_details(){
        $id = Request::param('id');
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择产品';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $where['p.id'] = $id;
        }

        //$where['p.uid'] = $this->user_id;

        $pinfo = Db::name('product')
            ->alias('p')
            ->leftJoin('product_cate pc','p.cate_id = pc.id')
            ->leftJoin('product_type pt','p.type_id = pt.id')
            ->leftJoin('users u','p.uid = u.id')
            ->leftJoin('cate c','p.zhandian_id = c.id')
            ->leftJoin('product_flow pf','p.id = pf.product_id')
            ->field('p.id,p.status,p.uid,p.is_new,p.leixing,p.reason,p.collect_time,p.cate_id,p.type_id,p.zhandian_id,p.json,p.remark,pc.title as catename,pt.title as typename,u.username as name,c.title as zhandian_name,pf.apply_status as apply_status,pf.apply_content as apply_content,pt.tiku_id')
            ->where($where)
            ->find();
        
        $pinfo['zhandian_ids'] = Db::name('cate')->where('id',$pinfo['zhandian_id'])->value('parentid');
        
        //获取题目开始
        $list = Db::name('spec')->where('tiku_id',$pinfo['tiku_id'])->where('is_delete',1)->order('id asc')->select();
        foreach ($list as $key => $val){
            if($val['leixing'] == 1){
                $itemlist = Db::name('spec_item')->field('id,item')->where('spec_id',$val['id'])->where('is_delete',1)->order('sort asc,id asc')->select();
                foreach ($itemlist as $keys => $vals){
                    $xz = Db::name('product_relation')->where('spec_id',$val['id'])->where('product_id',$id)->value('result');
                    if($vals['id'] == $xz){
                        $itemlist[$keys]['is_checked'] = 1;
                    }else{
                        $itemlist[$keys]['is_checked'] = 0;
                    }
                }
                $list[$key]['item'] = $itemlist;
            }else{
                $list[$key]['item'] = Db::name('product_relation')->where('spec_id',$val['id'])->where('product_id',$id)->value('result');
            }
            $list[$key]['is_answer'] = 1;
        }
        
        $pinfo['json'] = json_encode($list);
        //获取题目结束
        
        if($pinfo['status'] == 1){
            $pinfo['status_name'] = '在用';
        }else{
            $pinfo['status_name'] = '闲置';
        }  
        if($pinfo['is_new'] == 1){
            $pinfo['is_new_name'] = '是';
        }else{
            $pinfo['is_new_name'] = '否';
        }  
        if($pinfo['leixing'] == 1){
            $pinfo['leixing_name'] = '个人使用';
        }else{
            $pinfo['leixing_name'] = '站点资产管理';
        }  
        
        $whr2['product_id'] = $id;
        $item_list = Db::name('product_relation')
            ->alias('pr')
            ->leftJoin('spec pc','pr.spec_id = pc.id')
            ->leftJoin('spec_item pt','pr.result = pt.id')
            ->field('pr.*,pc.title as spec_name,pc.leixing,pt.item as spec_item_name')
            ->where($whr2)
            ->order('pr.id asc')
            ->select();
        foreach ($item_list as $key => $val){
            if($val['leixing'] == 2){
                $item_list[$key]['spec_item_name'] = $val['result'];
            }
        }
        
        $pinfo['item_list'] = $item_list;
        
        if(!empty($pinfo['collect_time'])){
            $pinfo['ctime'] = date("Y-m-d",$pinfo['collect_time']);
        }else{
            $pinfo['ctime'] = '/';
        }

        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $pinfo;
        return json_encode($rs_arr,true);
        exit;
    }
    
    //首页统计接口
    public function zc_count(){
        
        $where = [];

        $uinfo = Db::name('users')->where('id',$this->user_id)->find();
        if($uinfo['group_device'] == 4){
            $where[] = ['zhandian_id','in',$uinfo['train_ruless']];
        }else{
            $where[] = ['zhandian_id','>',0];
        }
        //站点资产
        $zdcount = Db::name('product')->where($where)->count();
        
        //待接收
        $djscount = Db::name('product_flow')->where('status',1)->where('uid',$this->user_id)->where('apply_status',0)->count();
        
        //待审批报废
        $dbfcount = Db::name('product_apply')->where('status',1)->where('reply_uid',$this->user_id)->where('status',1)->count();
        
        $data['zdcount'] = $zdcount;
        $data['djscount'] = $djscount;
        $data['dbfcount'] = $dbfcount;
        
        
        //查询未读
        $count = Db::name('message')->where('uid',$this->user_id)->where('status',1)->count();
        $list = Db::name('message')->where('uid',$this->user_id)->order('create_time desc')->select();
        
        $data['wdcount'] = $count;
        $data['message_list'] = $list;
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $data;
        return json_encode($rs_arr,true);
        exit;
        
    }
    
    //设备报废记录
    public function useless_list(){
        $id = Request::param('id');
        
        $where = [];
        $wherea = [];
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择产品';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $where[] = ['product_id','=',$id];
            $wherea[] = ['pa.product_id','=',$id];
        }
        
        $list = Db::name('product_apply')
            ->field('DATE_FORMAT(FROM_UNIXTIME(create_time),"%Y-%m-%d") as deta_time')
            ->where($where)
            ->order('create_time desc')
            ->group("DATE_FORMAT(FROM_UNIXTIME(create_time),'%Y-%m-%d')")
            ->select();
        
        foreach($list as $key => $val){
            $start = strtotime($val['deta_time']);
            $end = $start + 86400;
            
            unset($wheres);
            $wheres[] = ['pa.create_time','>',$start];
            $wheres[] = ['pa.create_time','<',$end];
            
            $list[$key]['list'] = Db::name('product_apply')
                ->alias('pa')
                ->leftJoin('users u','pa.reply_uid = u.id')
                ->field('pa.*,u.username as reply_name')->where($where)->where($wheres)->order('create_time desc')->select();
        }
        
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $list;
        return json_encode($rs_arr,true);
        exit;
    }
    
    //设备报废记录追加备注
    public function useless_beizhu(){
        $id = Request::param('id');
        $apply_content = Request::param('apply_content');
        
        $where = [];
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择设备';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $where[] = ['id','=',$id];
        }
        
        $where[] = ['uid','=',$this->user_id];
        
        $info = Db::name('product_apply')->where($where)->find();

        if(!$info){
            
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '无权限';
            return json_encode($rs_arr,true);
            exit;
            
        }else{
            
            if($info['status'] != 2){
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '当前无法追加备注';
                return json_encode($rs_arr,true);
                exit;
            }else{
                $data['apply_content'] = $info['apply_content']."\n".$apply_content.'('.date('Y-m-d H:i:s',time()).')';
                Db::name('product_apply')->where($where)->update($data);
                
                $rs_arr['status'] = 200;
                $rs_arr['msg'] = 'success';
                return json_encode($rs_arr,true);
                exit;
            }
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
        $where[] = ['reply_uid','=',$this->user_id];
        
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
                $data['update_time'] = time();
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
    
    //报废申请列表
    public function useless_apply_list(){
        
        $status = Request::param('status');
        
        $uinfo = Db::name('users')->where('id',$this->user_id)->find();
        if($uinfo['group_device'] == 3){
            
            //全局查询条件
            $where=[];
            if(!empty($status) && $status > 0){
                $where[] = ['pa.status','=',$status];
            }
            
            //显示数量
            $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
            $page = Request::param('page') ? Request::param('page') : config('page');
            
            $a = $pageSize*($page-1);
        
            
            $where[] = ['pa.reply_uid','=',$this->user_id];

            $list = Db::name('product_apply')
                ->alias('pa')
                ->leftJoin('product p','pa.product_id = p.id')
                ->field('pa.id,pa.apply_content,pa.apply_reason,pa.status,pa.uid,pa.create_time,p.cate_id,p.type_id,p.zhandian_id')
                ->order('pa.status asc,pa.id ASC')
                ->limit($a.','.$pageSize)
                ->where($where)
                ->select();
                
            $count = Db::name('product_apply')
                ->alias('pa')
                ->leftJoin('product p','pa.product_id = p.id')
                ->field('pa.id,pa.apply_content,pa.apply_reason,pa.status,pa.uid,pa.create_time,p.cate_id,p.type_id,p.zhandian_id')
                ->order('pa.status asc,pa.id ASC')
                ->where($where)
                ->count();
            
            foreach ($list as $key => $val){
                $list[$key]['catename'] = Db::name('product_cate')->where('id',$val['cate_id'])->value('title');
                $list[$key]['typename'] = Db::name('product_type')->where('id',$val['type_id'])->value('title');
                $list[$key]['zhandian_name'] = Db::name('cate')->where('id',$val['zhandian_id'])->value('title');
                $list[$key]['username'] = Db::name('users')->where('id',$val['uid'])->value('username');
                if($val['status'] == 1){
                    $list[$key]['status_name'] = '待审批';
                }else if($val['status'] == 2){
                    $list[$key]['status_name'] = '已通过';
                }else{
                    $list[$key]['status_name'] = '已驳回';
                }
                $list[$key]['create_time'] = date('Y/m/d',$val['create_time']);
            }
            
            $data_rt['list'] = $list;
            $data_rt['count'] = $count;
            
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = 'success';
            $rs_arr['data'] = $data_rt;
            return json_encode($rs_arr,true);
            exit;
            
        }else{
            
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '无权限';
            return json_encode($rs_arr,true);
            exit;
            
        }
        
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
        
         //$where['p.uid'] = $this->user_id;
        
        $painfo = Db::name('product_apply')->where('id',$id)->where('reply_uid',$this->user_id)->find();
       
        if($painfo){
            $where['p.id'] = $painfo['product_id'];
        
            $pinfo = Db::name('product')
                ->alias('p')
                ->leftJoin('product_cate pc','p.cate_id = pc.id')
                ->leftJoin('product_type pt','p.type_id = pt.id')
                ->leftJoin('users u','p.uid = u.id')
                ->leftJoin('cate c','p.zhandian_id = c.id')
                ->leftJoin('product_flow pf','p.id = pf.product_id')
                ->field('p.id,p.status,p.uid,p.is_new,p.leixing,p.reason,p.collect_time,p.cate_id,p.type_id,p.zhandian_id,p.json,p.remark,pc.title as catename,pt.title as typename,u.username as name,c.title as zhandian_name,pf.apply_status as apply_status,pf.apply_content as apply_content')
                ->where($where)
                ->find();
            
            if($pinfo['status'] == 1){
                $pinfo['status_name'] = '在用';
            }else{
                $pinfo['status_name'] = '闲置';
            }  
            if($pinfo['is_new'] == 1){
                $pinfo['is_new_name'] = '是';
            }else{
                $pinfo['is_new_name'] = '否';
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
            
            
            if(!empty($pinfo['collect_time'])){
                $pinfo['ctime'] = date("Y-m-d",$pinfo['collect_time']);
            }else{
                $pinfo['ctime'] = '/';
            }

            
            $whereupd[] = ['product_id','=',$painfo['product_id']];
     
            $pinfo['updnum'] = Db::name('product_update')
                ->where($whereupd)
                ->count();
                
            
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
    
    public function message(){
        $whr['uid'] = $this->user_id;
        $whr['status'] = 1;
        $data['status'] = 2;
        Db::name('message')->where($whr)->update($data);
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        return json_encode($rs_arr,true);
        exit;
        
    }
}
