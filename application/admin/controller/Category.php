<?php

namespace app\admin\controller;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\Cate as C;
use app\common\model\Module as M;

class Category extends Base
{
    protected $validate = 'Cate';
    
     //权限列表
    public function index(){
        
        if(Request::isPost()){
            
            $data = Request::post();
            
            $parentid = $data['parentid'];
            
            $where=[];
            if($parentid){
                $where[]=['parentid', '=', $parentid];
            }else{
                $where[] = ['parentid','=','0'];
            }
            
            $list = Db::name('cate')->where($where)->order('sort asc')->select();
            
            foreach ($list as $key => $val){
                
                $list[$key]['value'] = $val['id'];
                $list[$key]['label'] = $val['title'];
                
                $num = Db::name('cate')->where(['parentid'=>$val['id']])->count();
                if($num > 0){
                    $list[$key]['children'] = self::get_trees($val['id']);
                }else{
                    $list[$key]['children'] = '';
                }
                
            }
            
            $data_rt['status'] = 200;
            $data_rt['msg'] = '获取成功';
            $data_rt['data'] = $list;
            return json_encode($data_rt);
            exit;
        }
    }
    
    public function indexs(){
        
        if(Request::isPost()){
            
            $data = Request::post();
            
            $parentid = $data['parentid'];
            
            $where=[];
            if($parentid){
                $where[]=['parentid', '=', $parentid];
            }else{
                $where[] = ['parentid','=','0'];
            }
            
            $list = Db::name('cate')->where($where)->order('sort asc')->select();
            
            foreach ($list as $key => $val){
                
                $list[$key]['value'] = $val['id'];
                $list[$key]['label'] = $val['title'];
                
                $num = Db::name('cate')->where(['parentid'=>$val['id']])->count();
                if($num > 0){
                    $list[$key]['children'] = self::get_trees($val['id']);
                }else{
                    $list[$key]['children'] = '';
                }
                
            }
            
            $data_rt['status'] = 200;
            $data_rt['msg'] = '获取成功';
            $data_rt['data'] = $list;
            return json_encode($data_rt);
            exit;
        }
    }
    
    public function get_trees($pid = 0){
      
        $list = Db::name('cate')->where(['parentid'=>$pid])->order('sort asc')->select();
        
        foreach ($list as $key => $val){
            
        
            $list[$key]['value'] = $val['id'];
            $list[$key]['label'] = $val['title'];
            
            $num = Db::name('cate')->where(['parentid'=>$val['id']])->count();
            
            if($num > 0){
                $list[$key]['children'] = self::get_trees($val['id']);
            }else{
                $list[$key]['children'] = '';
            }
            
        }
        
        return $list;
    }



    //添加保存
    public function addPost(){
        
        if(Request::isPost()){
            $data = Request::except('file');
            
         
            $result = $this->validate($data,$this->validate);
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            }else{
                $where['id'] = $data['parentid'];
                $level = Db::name('cate')->where($where)->value('level');
                
                $data['level'] = $level+1;
                $result = C::create($data);
                if($result->id){
                    $data_rt['status'] = 200;
                    $data_rt['msg'] = '添加成功';
                    return json_encode($data_rt);
                    exit;
                }else{
                    $data_rt['status'] = 500;
                    $data_rt['msg'] = '添加失败';
                    return json_encode($data_rt);
                    exit;
                }
            }
        }
    }

    //修改保存
    public function editPost(){
        if(Request::isPost()) {
            $data = Request::except('file');
            $where['id'] = $data['parentid'];
            $level = Db::name('cate')->where($where)->value('level');
            
            $data['level'] = $level+1;
            $result = C::where('id' ,'=', $data['id'])
                ->update($data);
        
            $data_rt['status'] = 200;
            $data_rt['msg'] = '修改成功';
            return json_encode($data_rt);
            exit;
        
        }
    }
    
    
    //删除栏目
    public function del(){
        if(Request::isPost()) {
            $id = Request::post('id');
            if(empty($id) ){
                $data_rt['status'] = 201;
                $data_rt['msg'] = 'ID不存在';
                return json_encode($data_rt);
                exit;
            }else{
                $num = Db::name('cateuser')->where(['catid'=>$id])->count();
                if($num > 0){
                    $data_rt['status'] = 201;
                    $data_rt['msg'] = '该架构下有用户，禁止删除';
                    return json_encode($data_rt);
                    exit;
                }else{
                    C::destroy($id);
                    $data_rt['status'] = 200;
                    $data_rt['msg'] = '删除成功';
                    return json_encode($data_rt);
                    exit;
                }
                
            }
             
        }
    }
    
    
    //成员列表
    public function ulist(){
        
        if(Request::isPost()){
            
            $data = Request::post();
            
            $parentid = $data['parentid'];
            
            $where=[];
            if($parentid){
                $where[]=['parentid', '=', $parentid];
            }else{
                $where[] = ['parentid','=','0'];
            }
            
            $list = Db::name('cate')->where($where)->order('sort asc')->select();
            
            foreach ($list as $key => $val){
                
                $num = Db::name('cate')->where(['parentid'=>$val['id']])->count();
                if($num > 0){
                    $list[$key]['children'] = self::get_trees($val['id']);
                }else{
                    $list[$key]['children'] = '';
                }
                
            }
            
            $data_rt['status'] = 200;
            $data_rt['msg'] = '获取成功';
            $data_rt['data'] = $list;
            return json_encode($data_rt);
            exit;
        }
    }
}
