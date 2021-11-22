<?php


namespace app\common\model;


use think\Model;

class Category extends Model
{
    protected $hidden = ['create_by', 'update_by','create_time', 'update_time', 'delete_time'];

    public function goods()
    {
        return $this->hasMany('Goods', 'cate_id', 'id')->field('id,cate_id,name,keywords');
    }
}