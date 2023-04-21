<?php
namespace app\common\model;

class Template extends Base
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}
