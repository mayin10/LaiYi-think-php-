<?php
/**
 * Created by PhpStorm.
 * User: benyingz
 * Date: 04.08.2021
 * Time: 16:24
 */

namespace app\adminapi\controller;

use think\Request;
use YabandPay\YabandPay;


class Zo extends BaseApi
{
    public function mailtest () {
        $mail = new \MyMail\MyMail();
        $mail->AddAddress('zoubenying@hotmail.com', 'Benying');
        $mail->Subject = 'test mail';
        //$mail->Body = '！！！！</p>';

        //$mail->addAttachment('');

        $table = $mail->makeTable([
            ['Jina', 'F', '2011-10-15'],
            ['Sissi', 'F', '2012-10-31'],
            ['Calven', 'M', '2011-08-14']
        ], ['Name', 'Geschlecht', 'Geburtsdatum']);

        $mail->setTemplate('welcome', [
            'name' => 'Benying',
            'date' => date('Y-m-d'),
            'table' => $table
        ], 'de');

        $mail->Send();

        $this->ok(1);
    }

    public function paypaltest () {
        return $this->fetch();
    }

    public function afterpay () {
        \Think\Log::record("afterpay");
//        $params = input();
//        $strParams = print_r($params, 1);
//        printArray($params);
//        \Think\Log::record($strParams);
    }

    public function sendOrderMail () {
        $orders = $orderList = \app\common\model\Order::with('user,daddress,baddress,orderGoods,orderGoods.sku')
            ->where('id', 'in', [1,2,3])->select();


        $oods = [];
        if ($orders) {
            foreach ($orders as $o) {
                $oas = [];
                foreach ($o->order_goods as $oa) {
                    $oas[] = [
//                        'name' => $oa->goods_name.' '.$oa->sku->name,
                        'name' => $oa->goods_name,

                        'goods_price' => $oa->goods_price,
                        'vat' => $oa->vat,
                        'amount' => $oa->amount,
                        'delivery_price' => $oa->delivery_price,
                        'store_cn' => $oa->sku->store_cn,
                        'store_de' => $oa->sku->store_de,
                        'lang' => $oa->sku->lang,
                        'width' => $oa->sku->width,
                        'height' => $oa->sku->height,
                        'weight' => $oa->sku->weight,
                        'status' => $oa->sku->status,
                    ];
                }

                $oods[$o->user_id][] = [
                    'id' => $o->id,

                    'currency' => 'EUR',

                    'user_id' => $o->user_id,

//                    'username' => $o->user->name ? $o->user->name : $o->user->username,
                    'username' => $o->user->username,

                    'email' => $o->user->email,
                    'language' => $o->user->language,
                    'user_note' => $o->user_note,

                    'order_net' => $o->order_net,
                    'order_vat' => $o->order_vat,
                    'total_price' => $o->total_price,

                    'order_sn' => $o->order_sn,

                    'pay_code' => $o->pay_code,
                    'pay_name' => $o->pay_name,
                    'pay_time' => $o->pay_time,
                    'shipping_name' => $o->shipping_name,
                    'shipping_sn' => $o->shipping_sn,
                    'shipping_time' => $o->shipping_time,

                    'status' => $o->status,
                    'status_name' => getOrderStatus($o->status),
                    'delivery_type' => $o->delivery_type,
                    'delivery_type_name' => getDeliveryType($o->delivery_type),
                    'delivery_price' => $o->delivery_price,
                    'invoice_id' => $o->invoice_id,
                    'invoice_title' => $o->invoice_title,

                    'client_discount' => $o->client_discount,
                    'coupon_price' => $o->coupon_price,
                    'confirm_time' => $o->confirm_time,

                    'deylivery_address' => [
                        'consignee' => $o->daddress->consignee,
                        'street' => $o->daddress->street,
                        'house_number' => $o->daddress->house_number,
                        'postcode' => $o->daddress->postcode,
                        'city' => $o->daddress->city,
                        'country' => $o->daddress->country,
                        'tel' => $o->daddress->tel,
                        'email' => $o->daddress->email
                    ],
                    'bill_address' => [
                        'consignee' => $o->baddress->consignee,
                        'street' => $o->baddress->street,
                        'house_number' => $o->baddress->house_number,
                        'postcode' => $o->baddress->postcode,
                        'city' => $o->baddress->city,
                        'country' => $o->baddress->country,
                        'tel' => $o->baddress->tel,
                        'email' => $o->baddress->email
                    ],

                    'order_goods' => $oas
                ];
            }
        }
        unset($orders);




//        printArray($oods);
//        die();

        if ($oods) {
            foreach ($oods as $os) {
                $toEmail = $os[0]['email'];
                if ($toEmail) {
                    $orderDetails = '';
                    foreach ($os as $o) {
                        $orderDetails .= '<p>';

                        $orderDetails .= '订单号：<b>'.$o['order_sn'].'</b> （咨询时请提交此号码）<br />';
                        $orderDetails .= '配送方式：'.$o['delivery_type_name'].'<br />';
                        $orderDetails .= '配送地址：'.$o['deylivery_address']['consignee'].', '.$this->_buildAddress($o['deylivery_address']).'<br />';
                        $orderDetails .= '账单地址：'.$o['bill_address']['consignee'].', '.$this->_buildAddress($o['bill_address']).'<br />';

                        $orderDetails .= '</p>';

                        $oaHeader = [
                            'article',
                            'vat',
                            'unit price',
                            'amount',
                            'sum'
                        ];

                        $oaTable = [];
                        foreach ($o['order_goods'] as $oa) {
                            $price_net = $oa['goods_price'] / (1 + ($oa['vat'] / 100));
                            $price_net_line = $price_net * $oa['amount'];

                            $oaTable[] = [
                                $oa['name'],
                                $oa['vat'].'%',
                                $price_net,
                                $oa['amount'],
                                $price_net_line
                            ];
                        }
                        $oaTable[] = 'hr';
                        $oaTable[] = ['sum net', '', '', '', $o['order_net']];
                        $oaTable[] = ['vat', '', '', '', $o['order_vat']];
                        $oaTable[] = 'hr';
                        $oaTable[] = ['sum order', '', '', '', $o['total_price']];
                        $oaTable[] = ['delivery', '', '', '', $o['delivery_price']];
                        $oaTable[] = 'hr';
                        $oaTable[] = ['Total', '', '', '', $o['total_price']];

                        $orderDetails .= \MyMail\MyMail::makeTable($oaTable, $oaHeader);
                        $orderDetails .= '<br />';
                    }




                    $mail = new \MyMail\MyMail();
                    $mail->AddAddress($toEmail, $os[0]['username']);
                    $mail->Subject = 'Ihre Bestellungen bei ' . \think\Config::get('system.name_en');
                    $mail->setTemplate('ordermail', [
                        'username' => $os[0]['username'],
                        'webname' => \think\Config::get('system.name'),
                        'orderDetails' => $orderDetails
                    ], 'de');

//                    echo $mail->Body;
                    $mail->Send();
                }
            }
        }
    }

    protected function _buildAddress ($addData) {
        if ($addData == 'zh') {
            $str = '中国'.$addData['city'].$addData['street'].$addData['house_number'].'，邮编：'.$addData['postcode'];
        } else {
            $str = $addData['street'].' '.$addData['house_number'].', '.$addData['postcode'].' '.$addData['city'].', '.$addData['country'];
        }
    }

    public function testYabandPay ($id, $method="yabandPay") {



        $order = \app\common\model\Order::find($id);
        $user = \app\common\model\User::find($order->user_id);
        $redirectUrl = 'https://devshopapi.wlsphoenix.de/adminapi/zo/finish';
        $notifyUrl = 'https://devshopapi.wlsphoenix.de/adminapi/zo/notify';

        $result_data = \YabandPay\YabandPay::createPay($method, $order->total_price, $order->currency, $order->order_sn, 'per '.$method, $redirectUrl, $notifyUrl, $user->email);
        //$contents = file_get_contents($result_data->data->url);

        //printArray($result_data); die;




        if(@$result_data->status == 'true'){   //判断是否成功
            $data_url = $result_data->data;

            $params = [
                'pay_code' => $data_url->trade_id,
                'pay_method' => $method
            ];
            \app\common\model\Order::update($params, ['id' => $id], true);

            $pay_url = $data_url->url;
            header('Location:'.$pay_url);
            exit();
        } else {
            $this->fail('支付过程发生错误，请重新尝试！');
        }
    }

    public function finish () {
        $data = urldecode(file_get_contents("php://input"));
        writeLog('YabandPay',  "finish RESULT: " . $data);
        if($data){
            $data_arr = json_decode($data, true);  //json转数组

            printArray($data_arr);

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
                $order = \app\common\model\Order::where(['pay_code'=> $data_arr['data']['trade_id']])->find();
                if ($order) {
                    $params = [
                        'paid_time' => date('Y-m-d H:i:s', intval($data_arr['data']['paid_time'])),
                        'status' => 20
                    ];
                    \app\common\model\Order::update($params, ['id' => $order->id], true);
                }
                writeLog('YabandPay',  "notify: pay success!");
            } else {
                writeLog('YabandPay',  "notify: Sign Error!");
            }
        } else {
            writeLog('YabandPay',  "notify: Nothing received!");
        }
    }

    public function refund ($id = 1) {
        $order = \app\common\model\Order::find($id);
        $notifyUrl = 'https://devshopapi.wlsphoenix.de/adminapi/zo/refundNotify';

        $res = \YabandPay\YabandPay::createRefund($order->pay_code, $order->total_price, $order->currency, "refund", $notifyUrl);
        printArray($res);
    }

    public function refundNotify ($id) {
        echo 'ok';  //必须返回ok,不然yabanpay会一直发异步消息,直到次数发送完,详细可参考文档
        $data = file_get_contents("php://input");  //直接返回json数据

        writeLog('YabandPay',  "refundNotify RESULT: " . $data);
        if($data){
            $data_arr = json_decode($data,true);  //json转数组
            //验证签名
            ////验证签名(注意如果是v1接口还是v3接口,v1接口方法是getV1sign,v3是getSign)
            $sign = $data_arr['sign'];  //yabandpay返回的签名
            $mysign = \YabandPay\YabandPay::getSign($data_arr['data']);   //自己用文档的签名方法生成的签名
            if($mysign == $sign){  //验证成功
                writeLog('YabandPay',  "refundNotify: refund success!");
                $order = \app\common\model\Order::where(['pay_code'] == $data_arr['data']['trade_id'])->find();
                if ($order) {
                    $params = [
                        'refund_time' => date('Y-m-d H:i:s'),
                        'status' => 70
                    ];
                    \app\common\model\Order::update($params, ['id' => $order->id], true);
                }
            } else {
                writeLog('YabandPay',  "refundNotify: Sign Error!");
            }
        } else {
            writeLog('YabandPay',  "refundNotify: Nothing received!");
        }
    }
}