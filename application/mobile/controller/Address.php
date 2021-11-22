<?php


namespace app\mobile\controller;


class Address extends MobileApi
{
 //1. 只能有一个默认地址
// 2.生成新地址时， 如果没有默认地址， 当前地址自动设为默认


    public function index(){
        $params = input();
        $List = \app\common\model\Address::where('user_id',$params['user_id'])->order(['is_default_delivery'=>'desc','is_default_bill'=>'desc'])->select();
        foreach($List as $v){
            $total = \app\common\model\Order::where('default_delivery_address|default_bill_address', $v->id)->count();
            $v['is_used'] = $total >0 ?true:false;
        }
        $this->ok($List);
    }

    public function addAddress(){
        $params = input();
        $validate = $this->validate($params, [
            'consignee|收件人姓名' => 'require',
            'email|收件人邮箱' => 'require',
            'tel|收件人手机号码' => 'require',
            'country|所在国家' => 'require',
            'city|所在城市' => 'require',
            'postcode|邮编' => 'require',
            'street|街道名' => 'require',
            'house_number|房屋号' => 'require',
        ]);
        if($validate !== true){
            $this->fail($validate);
        }

        if(empty($params['adress_id'])){
            $params['create_by'] = 'app';
            $params['update_by'] = 'app';
            $address = \app\common\model\Address::create($params,true);
            $this->setDefault($address);
        } else{
            $address = \app\common\model\Address::update($params,['id'=>$params['adress_id']],true);
        }

        $data = \app\common\model\Address::where(['id'=>$address->id])->select();
        $this->ok($data);
    }

    private function setDefault($address){
        $count = \app\common\model\Address::where(['user_id'=>$address->user_id])->count();
        if($count == 1){
            \app\common\model\Address::update(['is_default_delivery'=>1,'is_default_bill'=>1],['id'=>$address->id],true);
        }
    }


    public function getEditAddress()
    {
        $address = \app\common\model\Address::find(input('id'));
        $this->ok($address);
    }

   public function getDefaultAddress()
   {
       $params = input();
       $where['user_id'] = $params['user_id'];
       if ($params['address_type'] == 't') {
           $where['is_default_delivery'] = 1;
       } else {
           $where['is_default_bill'] = 1;
       }
       $data = \app\common\model\Address::where($where)->find();
       if ($data) {
           $this->ok($data);
       } else {
           $this->ok(null);
       }

   }

       public function changeDefalut(){
           $params = input();
           if($params['type'] == 't'){
               \app\common\model\Address::update(['is_default_delivery'=>0],['user_id'=>$params['cur_user_id']],true);
               \app\common\model\Address::update(['is_default_delivery'=>1],['id'=>$params['id']],true);
           }else{
               \app\common\model\Address::update(['is_default_bill'=>0],['user_id'=>$params['cur_user_id']],true);
               \app\common\model\Address::update(['is_default_bill'=>1],['id'=>$params['id']],true);
           }

           $this->ok();
       }


    public function delete()
    {
        $params = input();
        $id = $params['id'];
        $this->checkAddress($id);
        $address =  \app\common\model\Address::find($id);
        if($address->is_default_delivery == 1 || $address->is_default_bill == 1){
            $this->fail('不可以删除默认地址');
        }
        \app\common\model\Address::destroy($id);
        $this->ok();
    }

    private function  checkAddress($id){
        //判断是否使用中
        $total = \app\common\model\Order::where('default_delivery_address|default_bill_address', $id)->count();
        if($total > 0){
            $this->fail('地址已经在订单里使用， 不可以更改或删除');
        }
    }

    public function getCountries(){
        $list = \app\common\model\Countries::where(['active'=>1])->order(['name'=>'asc'])->select();
        $this->ok($list);
    }



}