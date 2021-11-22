<?php

namespace app\common\model;

use think\Model;

class Skus extends Model
{
    protected $hidden = ['create_by', 'update_by','create_time', 'update_time', 'delete_time'];

    public function delivery()
    {
        return $this->hasMany('SkuDeliveries', 'sku_id', 'id');
    }

    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id', 'id')->bind(['goods_name'=>'name','goods_vat'=>'vat']);
    }

}
