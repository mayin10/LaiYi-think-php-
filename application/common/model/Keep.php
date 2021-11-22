<?php

namespace app\common\model;

use think\Model;

class Keep extends Model
{
    protected $hidden = ['create_time', 'update_time', 'delete_time'];

    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id', 'id');

    }
}
