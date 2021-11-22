<?php

namespace app\common\model;

use think\Model;

class Goods extends Model
{
    protected $hidden = ['create_by', 'update_by', 'update_time', 'delete_time'];

    public function cate()
    {
        return $this->belongsTo('Category', 'cate_id', 'id')->bind(['cate_name'=>'name']);
    }

    public function type()
    {
        return $this->belongsTo('Type', 'type_id', 'id')->bind(['type_name'=>'name']);
    }


    public function group()
    {
        return $this->belongsTo('Group', 'group_id', 'id')->bind(['group_name'=>'cate_name']);
    }

    public function brand()
    {
        return $this->belongsTo('Brand', 'brand_id', 'id')->bind(['brand_name'=>'name']);
    }

    public function skus()
    {
        return $this->hasMany('Skus', 'goods_id', 'id');
    }

}
