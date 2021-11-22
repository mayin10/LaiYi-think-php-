<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class Admin extends Model
{
    //定义 管理员-档案的关联  一个管理员有一个档案
    public function profile()
    {
        //第二个参数外键 默认是admin_id; 第三个参数主键默认是id
        return $this->hasOne('Profile', 'uid', 'id');
//        return $this->hasOne('Profile', 'uid', 'id')->bind('idnum');
    }

    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    protected $insert = ['password'=>'123456'];

    protected $hidden = ['create_time', 'update_time', 'delete_time'];

    public function shop(){
        return $this->belongsTo('Shop')->bind(['shop_name'=>'name_cn']);
    }
    public function user(){
        return $this->belongsTo('User');
    }
    public function myShop(){
        return $this->belongsTo('Shop');
    }

    public function role(){
        return $this->belongsTo('Role')->bind('role_name');
    }


    public function getLastLoginTimeAttr($value){
        return date('Y-m-d H:i:s', $value);
    }

    public function setPasswordAttr($value)
    {
        return encrypt_password($value);
    }

}
