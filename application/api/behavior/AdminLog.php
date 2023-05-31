<?php
namespace app\api\behavior;

class AdminLog
{
    public function run()
    {
        \app\api\model\AdminLog::record();
    }
}
