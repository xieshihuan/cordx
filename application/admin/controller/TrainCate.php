<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
use think\facade\Request;


//实例化默认模型
use app\common\model\TrainCate as TC;

class TrainCate extends Base
{
    
    protected $validate = 'TrainCate';
    
     //权限列表
    public function index(){
       
        
        $list = Db::name('train_cate')->where('parentid',0)->where('is_delete',1)->order('sort asc,id asc')->select();
        if(count($list) > 0){
            foreach ($list as $key => $val){
                $lists = Db::name('train_cate')
                ->where('parentid',$val['id'])
                ->where('is_delete',1)
                ->order('sort asc,id asc')
                ->select();
                if(count($lists) > 0){
                    $list[$key]['children'] = $lists;
                }else{
                    $list[$key]['children'] = '';
                }
            }
        }else{
            $list = array();
        }
        $data_rt['status'] = 200;
        $data_rt['msg'] = '获取成功';
        $data_rt['data'] = $list;
        return json_encode($data_rt);
        exit;
    
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
                $level = Db::name('train_cate')->where($where)->value('level');
                
                $data['level'] = $level+1;
                $data['create_time'] = time();
                $data['update_time'] = time();
                
                if($data['parentid'] > 0){
                    $dataz['sort'] = 1;
                    $dataz['title'] = $data['title'];
                    $dataz['type'] = $data['type'];
                    $id = Db::name('template')->insertGetId($dataz);
                    //模型
                    $data['module_id'] = $id;
                }
                
                
                if(!empty($data['periods'])){
                    $periods = '';
                    foreach ($data['periods'] as $key => $val){
                        $periods .= $val.',';
                    }
                    $periods = rtrim($periods,',');
                     
                    $data['periods'] = $periods;
                }
                
                
                
                $result = TC::create($data);
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
            $data['update_time'] = time();
            
            if(!empty($data['periods'])){
                $periods = '';
                foreach ($data['periods'] as $key => $val){
                    $periods .= $val.',';
                }
                $periods = rtrim($periods,',');
                 
                $data['periods'] = $periods;
            }
            
            $module_id = Db::name('train_cate')->where('id',$data['id'])->value('module_id');
            if($module_id > 0){
                $dataz['title'] = $data['title'];
                $dataz['type'] = $data['type'];
                Db::name('template')->where('id',$module_id)->update($dataz);
            }
            
            $result = TC::where('id' ,'=', $data['id'])
                ->update($data);
        
            $data_rt['status'] = 200;
            $data_rt['msg'] = '修改成功';
            return json_encode($data_rt);
            exit;
        
        }
    }
    
    
    //删除
    public function del(){
        if(Request::isPost()) {
            $id = Request::post('id');
            if(empty($id)){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = 'ID不存在';
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                $data = Db::name('train_cate')->where('id',$id)->find();
            }
            
            if($data['level'] == 1){
                $upd['status'] = 2;
                $upd['is_delete'] = 2;
                Db::name('train_cate')->where('parentid',$id)->where('level',2)->update($upd);
                
                $list = Db::name('train_cate')->where('parentid',$id)->where('level',2)->select();
                foreach ($list as $key => $val){
                    if($val['module_id'] > 0){
                        $dataz['is_delete'] = 2;
                        Db::name('template')->where('id',$val['module_id'])->update($dataz);
                        Db::name('template')->where('parentid',$val['module_id'])->update($dataz);
                    }
                }
            }
            
            $module_id = Db::name('train_cate')->where('id',$data['id'])->value('module_id');
            if($module_id > 0){
                $dataz['is_delete'] = 2;
                Db::name('template')->where('id',$module_id)->update($dataz);
                Db::name('template')->where('parentid',$module_id)->update($dataz);
            }
            
            $upd['is_delete'] = 2;
            $upd['status'] = 2;
            Db::name('train_cate')->where('id',$id)->update($upd);

            $rs_arr['status'] = 200;
            $rs_arr['msg'] ='success';
            return json_encode($rs_arr,true);
            exit;
        
        }
    }
   
    
}
