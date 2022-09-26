<?php
/**
 * +----------------------------------------------------------------------
 * | 管理员日志控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use think\Db;
use think\facade\Request;
//实例化默认模型
use app\admin\model\AdminLog as M;
use think\facade\Session;

class Adminlog  extends Base
{
    //列表
    public function index(){

        //条件筛选
        $keyword = Request::param('keyword');
        $start = Request::param('start');
        $end = Request::param('end');
        $start = strtotime(date($start));
        $end = strtotime(date($end));
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['username|url', 'like', '%'.$keyword.'%'];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end=="")
        {
            $where[] = ['create_time','>=',$start];
        }
        if(isset($end)&&$end!=""&&isset($start)&&$start=="")
        {
            $where[] = ['create_time','<=',$end];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end!="")
        {
            $where[] = ['create_time','between',[$start,$end]];
        }
        //非超级管理员只能查看自己的日志
        if($this->admin_id>1){
            $where[]=['admin_id', '=', $this->admin_id];
        }
        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        
        //调取列表
        $list = M::where($where)
            ->order('id DESC')
            ->paginate($pageSize,false,['query' => request()->param()]);
        foreach($list as $k=>$v){
            $useragent = explode('(',$v['useragent']);
            $list[$k]['useragent']=$useragent[0];
        }
        
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $list;
		return json_encode($rs_arr,true);
		exit;
    }

    //查看
    public function edit(){
        $id = Request::param('id');
        if( empty($id) ){
            return ['error'=>1,'msg'=>'ID不存在'];
        }
        $info = M::get($id);
        $this->view->assign('info', $info);
        return $this->view->fetch();
    }

    //删除
    public function del(){
        if(Request::isPost()) {
            $id = Request::post('id');
            if( empty($id) ){
                return ['error'=>1,'msg'=>'ID不存在'];
            }
            return M::del($id);
        }
    }

    //批量删除
    public function selectDel(){
        if(Request::isPost()) {
            $id = Request::post('id');
            if (empty($id)) {
                return ['error'=>1,'msg'=>'ID不存在'];
            }
            return M::selectDel($id);
        }
    }


}
