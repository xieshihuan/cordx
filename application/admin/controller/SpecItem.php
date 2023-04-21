<?php
/**
 * +----------------------------------------------------------------------
 * | 新闻管理控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\SpecItem as M;

class SpecItem extends Base
{
    protected $validate = 'SpecItem';

    //列表
    public function index(){
        //条件筛选
        $keyword = Request::param('keyword');
        $spec_id = Request::param('spec_id');
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['item', 'like', '%'.$keyword.'%'];
        }
        if(!empty($spec_id)){
            $where[]=['spec_id', '=', $spec_id];
        }

        $where[] = ['is_delete', '=' ,1];

        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $this->view->assign('pageSize', page_size($pageSize));

        //调取列表
        $list = Db::name('spec_item')
            ->order('id asc')
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
        $spec_id = Request::param('spec_id');
        $itemlist = Request::param('itemlist');
        $num = Db::name('product_relation')->where('spec_id',$spec_id)->count();
        // if($num > 0){
        //     $rs_arr['status'] = 201;
        //     $rs_arr['msg'] = '此参数禁止修改';
        //     return json_encode($rs_arr,true);
        //     exit;
        // }else{
            //删除之前记录 20221025修改为不删除之前的记录
            //Db::name('spec_item')->where('spec_id',$spec_id)->delete();

            $itemlist = explode('^',$itemlist);
            if(count($itemlist) == 0){
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '选项不能为空';
                return json_encode($rs_arr,true);
                exit;
            }
            
            foreach ($itemlist as $key => $val){
                $whr['item'] = $val;
                $whr['spec_id'] = $spec_id;
                $num = Db::name('spec_item')->where($whr)->count();
                if($num == 0){
                    //添加新纪录
                    $dataadd['item'] = $val;
                    $dataadd['spec_id'] = $spec_id;
                    Db::name('spec_item')->insert($dataadd);
                }
            }
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = '修改成功';
            return json_encode($rs_arr,true);
            exit;
        //}

    }

}
