<?php

namespace app\common\model;
use think\Model;

class SkuDeliveries extends Model
{
    protected $hidden = ['create_by', 'update_by','create_time', 'update_time', 'delete_time'];

    //'1'=> '直邮中国', '2'=> '中国现货','3'=> '欧境快递','4'=> '大宗货运','5'=> '买家自取'

}

