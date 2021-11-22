<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;
class User extends Model
{
    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    protected $insert = ['password'=>'123456'];

    protected $hidden = ['create_time', 'update_time', 'delete_time'];

    public function shop(){
        return $this->belongsTo('Shop')->bind(['shop_name'=>'name']);
    }

    public function getLastLoginTimeAttr($value){
        return date('Y-m-d H:i:s', $value);
    }

    public function setPasswordAttr($value)
    {
        return encrypt_password($value);
    }
}
