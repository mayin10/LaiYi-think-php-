<?php

namespace app\common\model;

use think\Model;

class Order extends Model
{
    protected $hidden = [ 'update_time', 'delete_time'];




    public function daddress()
    {
        return $this->belongsTo('Address', 'default_delivery_address', 'id');
    }

    public function baddress()
    {
        return $this->belongsTo('Address', 'default_bill_address', 'id');
    }

    public function orderGoods()
    {
        return $this->hasMany('OrderGoods');
    }

    public function user()
    {
        return $this->belongsTo('User');
    }
}
