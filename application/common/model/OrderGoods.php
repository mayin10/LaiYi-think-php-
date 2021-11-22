<?php

namespace app\common\model;

use think\Model;

class OrderGoods extends Model
{
    protected $hidden = ['create_time', 'update_time', 'delete_time'];

    public function getStatusAttr($value)
    {
        $pay_status = ['待付款', '待发货', '待收货', '待评价', '已完成', '已取消 ', '已退货', '已退款', '已付款'];
        return $pay_status[$value];
    }


    public function sku(){
        return $this->belongsTo('Skus','sku_id', 'id');
    }

    public function delivery(){
        return $this->belongsTo('SkuDeliveries','sku_delivery_id', 'id');
    }
}
