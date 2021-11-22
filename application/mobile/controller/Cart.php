<?php

namespace app\mobile\controller;
use think\Request;


class Cart extends MobileApi
{

    public function addCart(){
        $params = input();

        $amount =$params['amount'];
        $where = [
            'user_id' => $params['user_id'],
            'sku_id' => $params['sku_id'],
            'sku_delivery_id' => $params['sku_delivery_id']
        ];
        $info = \app\common\model\Cart::where($where)->find();
        $skus = \app\common\model\Skus::find( $params['sku_id']);

        if($info){
            $amount= $info->amount + $amount;
            $this->checkStore($amount,$params['sku_delivery_id'],$skus);
            \app\common\model\Cart::update(['amount'=>$amount],['id'=>$info->id],true);
        }else{
            $this->checkStore($amount,$params['sku_delivery_id'],$skus);
            \app\common\model\Cart::create($params,true);
        }
        $cart =  \app\common\model\Cart::where($where)->find();
        $this->ok($cart);
    }

    //判断库存
    //sku_delivery_id   '1'=> '直邮中国', '2'=> '中国现货','3'=> '欧境快递','4'=> '大宗货运','5'=> '买家自取'
    public function checkStore($amount,$sku_delivery_id,$skus){
        if($sku_delivery_id == 1){
            if($amount > $skus->store_cn){
                $this->fail('中国库存不足, 不能购买直邮中国的商品', 500);
            }
        } else{
            if($amount > $skus->store_cn){
                $this->fail('德国库存不足, 不能欧洲境内发货的商品', 500);
            }
        }

    }

    public function getCart(){
        $user_id = input('user_id');

        $delivery_type = \app\common\model\Cart::where(['user_id'=>$user_id])->distinct(true)->field('delivery_type')->select();
        foreach($delivery_type as $type){
            $cart = \app\common\model\Cart::where(['user_id'=>$user_id,'delivery_type'=>$type['delivery_type']])->select();
            foreach($cart as $c){
                $c['sku']= \app\common\model\Skus::with('goods')->find($c->sku_id);
                $c['img'] = $this->getImg($c->sku_id,'goods_sku');
                $c['delivery'] =  \app\common\model\SkuDeliveries::find($c->sku_delivery_id);

            }
            $type['delivery_type_label'] = getDeliveryType($type->delivery_type);
            $type['list'] =$cart;
        }
        $this->ok($delivery_type);
 /*
        $cart = \app\common\model\Cart::where(['user_id'=>$user_id])->select();
        foreach($cart as $c){
            $c['sku']= \app\common\model\Skus::with('goods')->find($c->sku_id);
            $c['img'] = $this->getImg($c->sku_id,'goods_sku');
            $c['delivery'] =  \app\common\model\SkuDeliveries::find($c->sku_delivery_id);
            $c['delivery_type_label'] = getDeliveryType($c->delivery_type);
        }
        $delivery_type = \app\common\model\Cart::where(['user_id'=>$user_id])->distinct(true)->field('delivery_type')->select();
        $ouput =[];
        $ouput['delivery'] = $delivery_type;
        $ouput['cart'] = $cart;
        $this->ok($ouput); */

    }

    public function changeNum(){
        $params = input();
        \app\common\model\Cart::update(['amount' => $params['number']], ['id'=>$params['id']], true);
        $this->ok();
    }


    public function delCart(){
        \app\common\model\Cart::destroy(input('id'));
        $this->ok(input('id'));
    }

}
