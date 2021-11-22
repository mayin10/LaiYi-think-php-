<?php


namespace app\adminapi\controller;

use think\Request;
class Order extends BaseApi
{
    public function index()
    {
        $params = input();
        $where = [];
        //搜索条件 多字段相同条件的OR
        if(!empty($params['keyword'])){
            $keyword = $params['keyword'];
            $where['order_sn'] = ['like', "%$keyword%"];
        }
        if(!empty($params['status'])){
            if($params['status'] > 0){
                $where['status'] = $params['status'];
            }
        }
        $listRow = 6;
        if(!empty($params['pagesize'])){
            $listRow = $params['pagesize'];
        }
        $list = \app\common\model\Order::with('daddress')->where($where)->order('create_time','desc')->paginate($listRow);
        foreach($list as $v ){
            $v['status_text']  = getOrderStatus($v->status);
            $v['delivery_text']  = getDeliveryType($v->delivery_type);
        }
        //返回数据
        $this->ok($list);
    }


    public function getDetail(){
        $order = \app\common\model\Order::with('daddress,baddress')->find(input('id'));
        $orderGoods = \app\common\model\OrderGoods::where(['order_id'=>$order->id])->select();
        foreach ($orderGoods as $v){
            $v['img']  =  $this->get_wls_img('goods_sku',$v->sku_id);
        }
        $order['orderGoods'] = $orderGoods;
        $this->ok($order);
    }


    public function read($id)
    {
        $order = \app\common\model\Order::with('daddress,baddress')->find($id);
        $order['status_text'] =getOrderStatus($order->status);
        $order['not_pay'] =$order->status == 10?true:false;
        $this->ok($order);
    }


    public function update(Request $request, $id)
    {
        $params = input();
        $params['update_by'] = $this->getAdmin()->username;
        $data = \app\common\model\Order::update($params, ['id' => $id], true);
        $this->ok($data);
    }

    public function cancelOrder(){
        // id找到 Order, Order Status 改变  orderGoods 释放库存
        $id = input('id');
        cancelOrder($id);
        $order = \app\common\model\Order::get($id);
        if($order->status == 60){
            $this->ok();
        }else{
            $this->fail('出现错误');
        }
    }

}