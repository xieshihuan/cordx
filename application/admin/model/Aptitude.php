<?php
namespace app\admin\model;

class Aptitude extends Base
{
    public static function cmp_sequence_score($a, $b)
    {
        if ($a['status'] == $b['status']) {
            return ($a['number'] < $b['number']) ? -1 : 1;
        }
        return ($a['status'] < $b['status']) ? -1 : 1;
    }
}