<?php
/**
 * +----------------------------------------------------------------------
 * | 会员列表控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\Cateuser as M;

class Cateuser extends Base
{
    protected $validate = 'Cateuser';
    
    public function check(){
        $catid = Request::param('catid');
        
        if(!empty($catid)){
            $count = Db::name('cate')->where('id',$catid)->count();
            if($count > 0){
                
                //判断
                $uinfo = Db::name('users')->where('id',$this->admin_id)->find();
                if($uinfo['group_id'] > 2){
                    $array = explode(',',$uinfo['ruless']);
                    if(!in_array($catid,$array)){
                        $rs_arr['status'] = 201;
                		$rs_arr['msg'] = '无权限';
                		return json_encode($rs_arr,true);
                		exit;
                    }else{
                        $rs_arr['status'] = 200;
                		$rs_arr['msg'] = 'success';
                		return json_encode($rs_arr,true);
                		exit;
                    }
                }else{
                    $rs_arr['status'] = 200;
            		$rs_arr['msg'] = 'success';
            		return json_encode($rs_arr,true);
            		exit;
                }
        
            }else{
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '组织不存在';
        		return json_encode($rs_arr,true);
        		exit;
            }
            
        }else{
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择组织';
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    //列表
    public function index(){
        
        $catid = Request::param('catid');
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['u.mobile|u.username', 'like', '%'.$keyword.'%'];
        }
        if(!empty($catid)){
            $where[]=['c.catid', '=', $catid];
        }
        
        $where[]=['c.leixing', '=', 1];
       
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        
        //调取列表
        $list = Db::name('cateuser')
            ->alias('c')
            ->leftJoin('users u','u.id = c.uid')
            ->field('c.*,u.username as username,u.mobile as mobile')
            ->order('c.id DESC')
            ->where($where)
            ->paginate($pageSize,false,['query' => request()->param()]);
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $list;
		
		return json_encode($rs_arr,true);
		exit;
    }

    //添加保存
    public function addPost(){
        if(Request::isPost()) {
            $data = Request::param();
            $result = $this->validate($data,$this->validate);
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            }else{
                
                $whr['uid'] = $data['uid'];
                $whr['catid'] = $data['catid'];
                $whr['leixing'] = 1;
                $num = Db::name('cateuser')->where($whr)->count();
                
                if($num > 0){
                    $rs_arr['status'] = 500;
            		$rs_arr['msg'] = 'repeat';
            		return json_encode($rs_arr,true);
            		exit;
                }else{
                    $whr1['id'] = $data['uid'];
                    $num1 = Db::name('users')->where($whr1)->count();
                    
                    if($num1 == 0){
                        $rs_arr['status'] = 500;
                		$rs_arr['msg'] = '会员不存在';
                		return json_encode($rs_arr,true);
                		exit;
                    }
                    
                    $whr2['id'] = $data['catid'];
                    $num2 = Db::name('cate')->where($whr2)->count();
                    
                    if($num2 == 0){
                        $rs_arr['status'] = 500;
                		$rs_arr['msg'] = '组织不存在';
                		return json_encode($rs_arr,true);
                		exit;
                    }
                    
                    
                    $cuid = Db::name('cateuser')->insertGetId($data);
                    if($cuid){
                        
                        //更新会员上级绑定关系
                        $this->updsj($cuid,$cuid);
                        
                        $rs_arr['status'] = 200;
                		$rs_arr['msg'] = 'success';
                		return json_encode($rs_arr,true);
                		exit;
                    }else{
                        $rs_arr['status'] = 500;
                		$rs_arr['msg'] = 'error';
                		return json_encode($rs_arr,true);
                		exit;
                    }
                }
            }
        }
    }
    
    public function updsj($id,$oneid){
    
        $whr['id'] = $id;
        $cinfo = Db::name('cateuser')->where($whr)->find();
        if($cinfo['level'] > 1){
            //获取上级id
            $whr1['id'] = $cinfo['catid'];
            $sid = Db::name('cate')->where($whr1)->value('parentid');
            
            //获取上级级别
            $whr2['id'] = $sid;
            $level = Db::name('cate')->where($whr2)->value('level');
            
            $data['uid'] = $cinfo['uid'];
            $data['catid'] = $sid;
            $data['level'] = $level;
            $data['leixing'] = 2;
            $data['oneid'] = $oneid;
            $data['create_time'] = time();
            $data['update_time'] = time();
            
            $cuid = Db::name('cateuser')->insertGetId($data);
            
            $this->updsj($cuid,$oneid);
            
        }
        
    }
    

    //移出组织
    public function del(){
        if(Request::isPost()) {
            $id = Request::post('id');
            if(empty($id)){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = 'ID不存在';
        		return json_encode($rs_arr,true);
        		exit;
            }
            
            $m = new M();
            $m->del($id);
            
            Db('cateuser')->where('oneid',$id)->delete();
            
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = 'success';
    		return json_encode($rs_arr,true);
    		exit;
        }
    }

}
