<?php
/**
 * +----------------------------------------------------------------------
 * | 首页控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use app\admin\model\Users as U;
use app\admin\model\AuthRule;
use app\common\model\Users;
use think\Db;
use think\facade\Env;
use think\facade\Session;
use think\facade\Request;
use think\facade\validate;

use app\common\model\Img as M;

class Index extends Base
{
    
    //获取左侧导航
    public function index()
    {
        $authtoken = Request::param('authtoken');
        $admin_id = Db::name('users')->where('token',$authtoken)->value('id');
        
        $group_id = Db::name('auth_group_access')->where('uid',$admin_id)->value('group_id');
            
        $rules = Db::name('auth_group')
            ->where('id',$group_id)
            ->value('rules');
            $rules = explode(',',$rules);
            
        $authRule = AuthRule::where('status',1)
            ->order('sort asc')
            ->select()
            ->toArray();

        $menus = array();
        
        
        foreach ($authRule as $key=>$val){
            $authRule[$key]['href'] = url($val['name']);
            if($val['pid']==0){
                if($admin_id!=1){
                    
                    if(in_array($val['id'],$rules)){
                        $menus[] = $val;
                    }
                }else{
                    $menus[] = $val;
                }
            }
        }
        foreach ($menus as $k=>$v){
            $menus[$k]['children']=[];
            foreach ($authRule as $kk=>$vv){
                if($v['id']==$vv['pid']){
                    if($admin_id!=1) {
                        if (in_array($vv['id'],$rules)) {
                            $menus[$k]['children'][] = $vv;
                        }
                    }else{
                        $menus[$k]['children'][] = $vv;
                    }
                }
            }
        }
        
        $data_rt['status'] = 200;
        $data_rt['msg'] = '获取成功';
        $data_rt['data'] = $menus;
        
        return json_encode($data_rt,true);
    }
    
     //获取三级导航
    public function three()
    {
        
        $authtoken = Request::param('authtoken');
        $pid = Request::param('pid');
        $admin_id = Db::name('users')->where('token',$authtoken)->value('id');
        $group_id = Db::name('auth_group_access')->where('uid',$admin_id)->value('group_id');
            
        $rules = Db::name('auth_group')
            ->where('id',$group_id)
            ->value('rules');
            $rules = explode(',',$rules);
            
        $authRule = AuthRule::where('status',1)->where('pid',$pid)->where('auth_open',1)
            ->order('sort asc')
            ->select()
            ->toArray();
        
        $menus = array();
        
        
        foreach ($authRule as $key=>$val){
            $authRule[$key]['href'] = url($val['name']);
        
            if($admin_id!=1){
                
                if(in_array($val['id'],$rules)){
                    $menus[] = $val;
                }
            }else{
                $menus[] = $val;
            }
            
        }
        // foreach ($menus as $k=>$v){
        //     $menus[$k]['children']=[];
        //     foreach ($authRule as $kk=>$vv){
        //         if($v['id']==$vv['pid']){
        //             if($admin_id!=1) {
        //                 if (in_array($vv['id'],$rules)) {
        //                     $menus[$k]['children'][] = $vv;
        //                 }
        //             }else{
        //                 $menus[$k]['children'][] = $vv;
        //             }
        //         }
        //     }
        // }
        
        $data_rt['status'] = 200;
        $data_rt['msg'] = '获取成功';
        $data_rt['data'] = $menus;
        
        return json_encode($data_rt,true);
    }

  
    //上传文件
    public function upload(){
        //file是传文件的名称，这是webloader插件固定写入的。因为webloader插件会写入一个隐藏input，不信你们可以通过浏览器检查页面
        $file = request()->file('images');
        $info = $file->validate(['ext' => 'jpg,png,gif,jpeg'])->move('uploads');

        if($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
            //echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            $url =  "/uploads/".$info->getSaveName();
            
            $url = str_replace("\\","/",$url);
            
            $result['code'] =200;
            $result['msg'] = '上传成功';
            $result['data'] = $url;
            return json_encode($result,true);
            
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getFilename();
        }else{
            // 上传失败获取错误信息
            $result['code'] = 500;
            $result['msg'] = $file->getError();
            return json_encode($result,true);
        }
    }
    
    //上传文件
    public function uploads(){
        //file是传文件的名称，这是webloader插件固定写入的。因为webloader插件会写入一个隐藏input，不信你们可以通过浏览器检查页面
        $file = request()->file('images');
        $info = $file->validate(['ext' => 'jpg,png,gif,jpeg'])->move('uploads/thumb');

        if($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
            //echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            $url =  "/uploads/thumb/".$info->getSaveName();
            
            $url = str_replace("\\","/",$url);
            
            $data['thumb'] = $url;
            
            $id = Db::name('img')->insertGetId($data);
            $where['id'] = $id;
            $pinfo = Db::name('img')->field('id,thumb')->where($where)->find();
            
            $request = Request::instance();
            $domain = $request->domain();
            
            $pinfo['thumb'] = $domain.$pinfo['thumb'];
    
            $result['code'] =200;
            $result['msg'] = '上传成功';
            $result['data'] = $pinfo;
            return json_encode($result,true);
            
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getFilename();
        }else{
            // 上传失败获取错误信息
            $result['code'] = 500;
            $result['msg'] = $file->getError();
            return json_encode($result,true);
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
            $path = Db::name('img')->where($whr)->value('thumb');
            
            $paths = Env::get('root_path').'public'.$path;
         
            if (file_exists($paths)) {
                @unlink($paths);//删除
            }
            
            $m = new M();
            $m->del($id);
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] ='success';
    		return json_encode($rs_arr,true);
    		exit;
        }
    }


    //wangEditor
    public function wangEditor(){
        // 获取上传文件表单字段名
        $fileKey = array_keys(request()->file());
        for($i=0 ; $i<count($fileKey) ; $i++){
            // 获取表单上传文件
            $file = request()->file($fileKey[$i]);
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->validate(['ext' => 'jpg,png,gif,jpeg'])->move('uploads');
            if($info){
                $path[]='/uploads/'.str_replace('\\','/',$info->getSaveName());
            }
        }

        if($path){
            $result['errno'] = 0;
            $result["data"] =  $path;
            return json_encode($result);

        }else{
            // 上传失败获取错误信息
            $result['code'] =1;
            $result['msg'] = '图片上传失败!';
            $result['data'] = '';
            return json_encode($result,true);
        }
    }

    //ckeditor
    public function ckeditor(){
        // 获取上传文件表单字段名
        $fileKey = array_keys(request()->file());
        for($i=0 ; $i<count($fileKey) ; $i++){
            // 获取表单上传文件
            $file = request()->file($fileKey[$i]);
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->validate(['ext' => 'jpg,png,gif,jpeg'])->move('uploads');
            if($info){
                $path[]='/uploads/'.str_replace('\\','/',$info->getSaveName());
            }
        }
    
        if($path){
            $result['uploaded'] = true;
            $result["url"] =  $path;
            return json_encode($result);

        }else{
            // 上传失败获取错误信息
            $result['uploaded'] =false;
            $result['url'] = '';
            return json_encode($result,true);
        }
    }

    //清除缓存
    public function clear(){
        $R = Env::get('runtime_path');
        if ($this->_deleteDir($R)) {
            $result['msg'] = '清除缓存成功!';
            $result['code'] = 1;
        } else {
            $result['msg'] = '清除缓存失败!';
            $result['code'] = 0;
        }
        $result['url'] = url('admin/index/index');
        return $result;
    }

    //执行删除
    private function _deleteDir($R)
    {
        $handle = opendir($R);
        while (($item = readdir($handle)) !== false) {
            if ($item != '.' and $item != '..') {
                if (is_dir($R . '/' . $item)) {
                    $this->_deleteDir($R . '/' . $item);
                } else {
                    if($item!='.gitignore'){
                        if (!unlink($R . '/' . $item)){
                            return false;
                        }
                    }
                }
            }
        }
        closedir($handle);
        return true;
        //return rmdir($R); //删除空的目录
    }
    
    
    
    
    //修改密码
    public function resetPass(){
        $authtoken = Request::param('authtoken');
        $oldpassword = Request::param('oldpassword');
        $newpassword = Request::param('newpassword');
        $newpassword2 = Request::param('newpassword2');
        $info = Db::name('users')->where('token',$authtoken)->find();
        
        if($newpassword != $newpassword2){
            
            $data_rt['status'] = 500;
            $data_rt['msg'] = '两次密码不一致';
            
        }else{
            
            if(md5(trim($oldpassword).'core2022') != $info['password']){
                $data_rt['status'] = 500;
                $data_rt['msg'] = '旧密码不正确';
            }else{
                
                $where['id'] = $info['id'];
                $data['password'] = md5(trim($newpassword).'core2022');
                if(U::update($data,$where)){
                    $data_rt['status'] = 200;
                    $data_rt['msg'] = '修改成功';
                }else{
                    $data_rt['status'] = 500;
                    $data_rt['msg'] = '修改失败';
                }
            
            }
            
        }
        
        return json_encode($data_rt,true);
        
    }

}
