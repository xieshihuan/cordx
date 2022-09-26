<?php
/**
 * +----------------------------------------------------------------------
 * | 管理员日志行为
 * +----------------------------------------------------------------------
 */
namespace app\admin\behavior;

class AdminLog
{
    public function run($params = '')
    {
        if (empty($params)){
            throw new BaseException(['msg' => '日志信息不能为空']);
        }
        \app\admin\model\AdminLog::record($params);
    }
}
