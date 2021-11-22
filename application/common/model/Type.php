<?php
namespace app\common\model;


use think\Model;

class Type extends Model
{
    protected $hidden = ['create_by', 'update_by','create_time', 'update_time', 'delete_time'];

    public function goods()
    {
        return $this->hasMany('Goods', 'type_id', 'id')->field('id,type_id,name,keywords');
    }
}

