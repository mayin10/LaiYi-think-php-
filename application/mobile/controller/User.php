<?php


namespace app\mobile\controller;

class User extends MobileApi
{





    public function addAddress(){
        $params = input();
        $where = [
            'client_id' => $params['client_id'],
            'consignee' => $params['consignee'],
            'country' => $params['country'],
            'city' => $params['city'],
            'postcode' => $params['postcode'],
            'street' => $params['street'],
            'house_number' => $params['house_number']
        ];
        $address = \app\common\model\Address::where($where)->find();

        if($address){
            $this->fail('已经存在相同的地址', 403);
        }else{
            $where['tel'] = $params['tel'];
            \app\common\model\Address::create($where,true);
        }
        $this->ok('add');
    }

    public function changeAddress(){
        $params = input();
        $user_id =$params['user_id'];
        $id =$params['id'];
        $is_default =$params['is_default'];
        \app\common\model\Address::update(['is_default' => $is_default], ['id'=>$id, 'user_id'=>$user_id]);
        $this->ok('changeAddress');
    }

    public function delAddress(){
        $params = input();
        $id =$params['id'];
        $user_id =$params['user_id'];
        \app\common\model\Cart::where('id', $id)->where('user_id',$user_id)->delete();
        $this->ok($id);
    }

}