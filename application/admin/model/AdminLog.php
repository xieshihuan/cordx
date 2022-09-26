<?php
/**
 * +----------------------------------------------------------------------
 * | 管理员日志模型
 * +----------------------------------------------------------------------
 */
namespace app\admin\model;

use think\facade\Request;
use think\facade\Session;
use think\Loader;
use think\Db;

class AdminLog extends Base {

    //关闭自动时间戳
    protected $autoWriteTimestamp = false;

    //管理员日志记录
    public static function record($params)
    {
        $route = Request::controller() . '/' . lcfirst(Request::action());

        $allows = [
            'Ad/index',             //
            'Adminlog/indexs',      //
            'Cateuser/index',       //
            'Cateuser/indexs',      //
            'Daxuetang/index',      //
            'Category/index',       //
            'Category/indexs',
            'Auth/admingroup',      //
            'Auth/admingroups',     //
            'Auth/adminrule',       //
            'Auth/groupaccess',     //
            'Auth/groupaccess',
            'Index/index',          //
            'Index/three',          //
            'Tiku/index',           //
            'Users/index',          //
            //'Login/checkLogin',
            'Login/sendCode',
            'Adminlog/index',
            'Users/groupAccess',
            'Users/getlist',
            'Test/zdlist',
            'Test/index',
            'Tikus/index',
            'Tikus/is_kaohe',
            
        ];
        if(!in_array($route, $allows)){

            $admin_id   = $params;
            $username   = Db('users')->where('id',$params)->value('username');
            $mobile   = Db('users')->where('id',$params)->value('mobile');

            $url        = Request::url();
            $title      = '';
            $content    = Request::param();
            $ip         = Request::ip();
            $useragent  = Request::server('HTTP_USER_AGENT');
            $create_time = time();

            //标题处理
            $auth = new \Auth\Auth();
            $titleArr = $auth->getBreadCrumb();
            if(is_array($titleArr)){
                foreach($titleArr as $k=>$v){
                    $title = '[' . $v['title'] . '] -> ' . $title;
                }
                $title = substr($title,0,strlen($title)-4);
            }
            //内容处理(过长的内容和涉及密码的内容不进行记录)
            if($content){
                foreach ($content as $k => $v)
                {
                    if (is_string($v) && strlen($v) > 200 || stripos($k, 'password') !== false)
                    {
                        unset($content[$k]);
                    }
                }
            }

            //插入数据
            self::create([
                'title'       => $title ? $title : '',
                'content'     => !is_scalar($content) ? json_encode($content) : $content,
                'url'         => $url,
                'admin_id'    => $admin_id,
                'username'    => $username,
                'mobile'      => $mobile,
                'useragent'   => $useragent,
                'ip'          => $ip,
                'create_time' =>$create_time
            ]);

        }
    }

    //删除
    public static function del($id){
        self::destroy($id);
        return ['error'=>0,'msg'=>'删除成功!'];
    }

    //批量删除
    public static function selectDel($id){
        self::destroy($id);
        return ['error'=>0,'msg'=>'删除成功!'];
    }

}