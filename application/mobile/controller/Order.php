<?php


namespace app\mobile\controller;
class Order extends MobileApi
{
    public function addOrder(){
        //开启事务
        \think\Db::startTrans();
        try {
            $params = input();
            $order_sn = time() . mt_rand(1000, 9999);
            $output_order_sn =[];
            $output_order_ids =[];
            foreach($params['list'] as $cart){
                //检测库存
                 foreach($cart['cartId'] as $cart_id){
                     $this->checkStore($cart_id);
                 }

                $order_data = [
                    'user_id' => $params['user_id'],
                    'order_sn' => $order_sn.'_'. $cart['type'],
                    'delivery_type' => $cart['type'],
                    'default_delivery_address' => $cart['default_delivery_address'],
                    'default_bill_address' =>  $cart['default_bill_address'],
                    'total_price' => $cart['total_price'],
                    'delivery_price' => $cart['delivery_price'],
                    'total_amount' => $cart['total_amount'],
                    'user_note' =>$cart['user_note'],
                ];

                $order = \app\common\model\Order::create($order_data,true);
                $output_order_sn[] = $order->order_sn;
                $output_order_ids[] =$order->id;
                $order_net = 0;
                $order_vat = 0;
                foreach($cart['cartId'] as $cart_id){
                    $orderGoods =  $this->addOrderGoods($order->id,$cart_id);

                   // $this->fail('test',403,$orderGoods);
                    $net =$orderGoods->goods_price/(1+$orderGoods->vat/100);
                    $vat = $orderGoods->goods_price - $net;
                    $order_vat = $order_vat + $vat*$orderGoods->amount;
                    $order_net = $order_net  + $net*$orderGoods->amount;
                    $this->updateStore($cart_id);
                    //删除购物车数据
                    \app\common\model\Cart::destroy($cart_id);
                }
                \app\common\model\Order::update(['order_net'=>$order_net, 'order_vat'=>$vat],['id'=>$order->id],true);

            }
            \think\Db::commit();
            //email
             sendOrderMail ($output_order_ids);
            $this->ok(implode(",", $output_order_sn));
        }catch (\Exception $e){
            //回滚事务
           \think\Db::rollback();;
           $this->fail('创建订单失败，请重试',403);
        }
    }


    private function  addOrderGoods($order_id, $cart_id){
        $cart = \app\common\model\Cart::with('sku,sku.goods,delivery')->find($cart_id);

        $data = [
            'status' => 0,
            'order_id' => $order_id,
            'sku_id' => $cart->sku_id,
            'sku_delivery_id' => $cart->sku_delivery_id,
            'goods_name' =>  $cart->sku->name,
            'specs' =>  $cart->sku->name_en,
            'sku_name' =>  $cart->sku->name,
            'sku_name_en' =>  $cart->sku->name_en,
            'goods_price' => $cart->sku->price_1,
            'vat' => $cart->sku['goods_vat'],
            'delivery_price' => $cart->delivery->preis,
            'amount' =>$cart->amount,
        ];

         $orderGoods =  \app\common\model\OrderGoods::create($data,true);

         return $orderGoods;
    }


    //判断库存
    //sku_delivery_id   '1'=> '直邮中国', '2'=> '中国现货','3'=> '欧境快递','4'=> '大宗货运','5'=> '买家自取'
    private function checkStore($cart_id){
        $cart = \app\common\model\Cart::with('sku,delivery')->find($cart_id);
        if($cart == null){
            $this->fail('购物车内没有相关商品', 500);
        }
        if($cart->delivery->delivery_type == 2){
            if($cart->amount > $cart->sku->store_cn){
                $this->fail('中国库存不足, 不能购买中国现货的商品', 500);
            }
        } else{
            if($cart->amount > $cart->sku->store_de){
                $this->fail('德国库存不足, 不能欧洲境内发货的商品', 500);
            }
        }

    }
    //更新库存
    //sku_delivery_id   '1'=> '直邮中国', '2'=> '中国现货','3'=> '欧境快递','4'=> '大宗货运','5'=> '买家自取'
    private function updateStore($cart_id){
        $cart = \app\common\model\Cart::with('sku,delivery')->find($cart_id);
        if($cart == null){
            $this->fail('购物车内没有相关商品', 500);
        }

        if($cart->delivery->delivery_type == 2){
            if($cart->amount < $cart->sku->store_cn){
                $newAmount =  $cart->sku->store_cn - $cart->amount;
                 \app\common\model\Skus::update(['store_cn' =>$newAmount],['id'=>$cart->sku->id]);
            }
        } else{
            if($cart->amount < $cart->sku->store_de){
                $newAmount =  $cart->sku->store_de - $cart->amount;
                \app\common\model\Skus::update(['store_de' =>$newAmount],['id'=>$cart->sku->id]);
            }
        }

    }

    public function getOrderList(){
        $params = input();
        $where['user_id'] = $params['user_id'];

        if($params['status'] != 0){
            $where['status'] = $params['status'];
        }

        $orderList = \app\common\model\Order::where($where)->order(['status'=>'asc', 'create_time'=>'desc'])->select();
        //'10'=> '待付款', '20'=> '待发货','30'=> '待收货','40'=> '待评价','50'=> '已完成','60'=> '已取消', '70'=> '已退货', '80'=> '已退款',
        $orderType = [
            10=> 'unpaid',
            30=> 'unreceived',
            40=> 'received',
            50=> 'completed',
            60=> 'cancelled',
            70=> 'refunds',
        ];
        foreach($orderList as $v){

            $orderGoodsList =\app\common\model\OrderGoods::where(['order_id'=>$v->id])->order(['goods_price' =>'desc'])->select();
            $sku_id = 0;
            foreach($orderGoodsList as $g){
                if($sku_id == 0){
                    $sku_id =   $g->sku_id;
                    break;
                }
            }
            $v['status_text']  = getOrderStatus($v->status);
            $v['img'] = $this->getImg($sku_id,'goods_sku');
            $v['type'] = $this->getOrderType($v->status);
            $v['delivery_type_label'] = getDeliveryType($v->delivery_type);
        }
        $this->ok($orderList);
    }

    public function getOrderType($status){
        $OrderType = [
            10=> ['type'=>'unpaid', 'text'=>'等待付款'],
            30=> ['type'=>'unreceived', 'text'=>'商家已发货'],
            40=> ['type'=>'received', 'text'=>'等待用户评价'],
            50=> ['type'=>'completed', 'text'=>'交易已完成'],
            60=> ['type'=>'cancelled', 'text'=>'订单已取消'],
            70=> ['type'=>'refunds', 'text'=>'商品退货处理中'],
        ];


        if (array_key_exists($status, $OrderType)) {
            return $OrderType[$status];
        }
        return 'no Type';
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


    public function getOrderByInput($nr){
        /*
       // $nr ="16324034688895_1_2_3_5";
        $pos = strpos($nr,'_');
        $sub1 = substr($nr, 0,$pos);
        $sub2 = substr($nr, $pos+1);
        $sub2 = str_replace('_',',',$sub2);

        $where['order_sn'] =['like',$sub1.'%'];
        $where['delivery_type' ] = ['in',$sub2];
        $list =  \app\common\model\Order::where($where)->select();*/

        $list =  \app\common\model\Order::where('order_sn','in',$nr)->select();
        return $list;
    }

    public function paynow () {
        $params = input();
        $sn =$params['order_sn'];
        $amount =$params['amount'];
        $email =$params['email'];

        $currency = "Euro";
        $method="yabandPay";

        $finishtUrl = WEB_NAME.'appPayFinish';
        $notifyUrl = WEB_NAME.'appPayNotify';

        $result_data = \YabandPay\YabandPay::createPay($method, $amount, $currency,$sn, 'per '.$method, $finishtUrl, $notifyUrl, $email);

        if(@$result_data->status == 'true'){   //判断是否成功
            $data_url = $result_data->data;
            $params = [
                'pay_code' => $data_url->trade_id,
                'pay_method' => $method
            ];
            foreach($this->getOrderByInput($sn) as $order){
                \app\common\model\Order::update($params, ['id' => $order->id], true);
            }
             $this->ok($data_url);
        } else {
            $this->fail('支付过程发生错误，请重新尝试！');
        }
    }

    public function finish () {
        $data = urldecode(file_get_contents("php://input"));
        writeLog('YabandPay',  "finish RESULT: " . $data);
        if($data){
            $data_arr = json_decode($data, true);  //json转数组

         //   printArray($data_arr);

            //验证签名
            ////验证签名(注意如果是v1接口还是v3接口,v1接口方法是getV1sign,v3是getSign)
            $sign = $data_arr['sign'];  //yabandpay返回的签名
            $mysign = \YabandPay\YabandPay::getSign($data_arr['data']);   //自己用文档的签名方法生成的签名

            if($mysign == $sign){  //验证成功
                if ($data_arr['data']['state'] == 'expired') {
                    echo '二维码已经失效，请重新购买！';
                } else {
                    echo '购买成功！';
                }
            }else{
                echo '签名错误！';
            }
        }else{
            echo '无法接受数据！';
        }
    }

    public function notify () {
        echo 'ok';  //必须返回ok,不然yabanpay会一直发异步消息,直到次数发送完,详细可参考文档
        $data = urldecode(file_get_contents("php://input"));  //直接返回json数据
        writeLog('YabandPay',  "notify RESULT: " . $data);
        if($data){
            $data_arr = json_decode($data,true);  //json转数组
            //验证签名
            ////验证签名(注意如果是v1接口还是v3接口,v1接口方法是getV1sign,v3是getSign)
            $sign = $data_arr['sign'];  //yabandpay返回的签名
            $mysign = \YabandPay\YabandPay::getSign($data_arr['data']);   //自己用文档的签名方法生成的签名
            if($mysign == $sign && $data_arr['data']['state'] == 'paid'){  //验证成功

                $orderList = \app\common\model\Order::where(['pay_code'=> $data_arr['data']['trade_id']])->select();
                if ($orderList) {
                    $params = [
                        'paid_time' => date('Y-m-d H:i:s', intval($data_arr['data']['paid_time'])),
                        'status' => 20
                    ];
                    foreach($orderList as $order){
                        \app\common\model\Order::update($params, ['id' => $order->id], true);
                    }
                }
                writeLog('YabandPay',  "notify: pay success!");
            } else {
                writeLog('YabandPay',  "notify: Sign Error!");
            }
        } else {
            writeLog('YabandPay',  "notify: Nothing received!");
        }
    }

    public function getOrder(){
        $params = input();
        //16324034688895_1_2_3_5
        $order_nr =$params['order_nr'];
        $this->ok($this->getOrderByInput($order_nr));
    }

    public function getOrderDetail(){
        $order_id = input('order_id');
        $order = \app\common\model\Order::with('daddress,baddress')->find($order_id);
        $orderGoods = \app\common\model\OrderGoods::where(['order_id'=>$order->id])->select();
        foreach ($orderGoods as $v){
            $v['img'] = $this->getImg($v->sku_id,'goods_sku');
        }
        $order['orderGoods'] = $orderGoods;
        $order['status_text']  = getOrderStatus($order->status);
        $order['delivery_type_label'] = getDeliveryType($order->delivery_type);
        $this->ok($order);
    }


}