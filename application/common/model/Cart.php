<?php

namespace app\common\model;

use think\Model;

class Cart extends Model
{
    protected $hidden = ['create_by', 'update_by','create_time', 'update_time', 'delete_time'];

    public function sku()
    {
        return $this->belongsTo('Skus', 'sku_id', 'id');
    }

    public function delivery()
    {
        return $this->belongsTo('SkuDeliveries', 'sku_delivery_id', 'id');
    }
}
