<?php


namespace app\common\model;

use think\Model;
class Address extends Model
{

    protected $hidden = ['create_by', 'update_by','create_time', 'update_time', 'delete_time'];


    public function user(){
        return $this->belongsTo('User');
    }

    public function getCountryAttr($value){

        $Country = \app\common\model\Countries::where(['active'=>1, 'code'=>$value])->find();
        if($Country){
            return  $Country['name'];
        }
        return $value;
    }

}