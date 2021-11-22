<?php


define('WEB_NAME', 'https://devshopapi.wlsphoenix.de/');

define('SERVICE_EMAIL', 'jing2016001@gmail.com');
define('FIRMA_IBAN', 'DE6637710040726682430');

define('UPLOAD', 'uploads');
define('UPLOAD_PATH', ROOT_PATH . 'public' . DS . UPLOAD);


function img_rule()
{
    $rule = ['size' => 100 * 1024 * 1024, 'ext' => 'jpg,png,gif,jpeg'];
    return $rule;
}


function myTest($data)
{

    $data = (new \think\Collection($data))->toArray();
    dump($data);
    die;
}


function tagTypes()
{
    return [
        'swiper' => ['name' => 'swiper', 'label' => '主页轮播图', 'hasImg' => 1, 'imageW' => 750, 'imageH' => 250],
        'banner' => ['name' => 'banner', 'label' => '主页广告图', 'hasImg' => 1, 'imageW' => 750, 'imageH' => 200],
        'service' => ['name' => 'service', 'label' => '商品服务', 'hasImg' => 0, 'imageW' => 0, 'imageH' => 0],
        'tag' => ['name' => 'tag', 'label' => '商品标签', 'hasImg' => 0, 'imageW' => 0, 'imageH' => 0],
        'tab1' => ['name' => 'tab1', 'label' => '商品标签1', 'hasImg' => 1, 'imageW' => 50, 'imageH' => 50],
        'tab2' => ['name' => 'tab2', 'label' => '商品标签2', 'hasImg' => 0, 'imageW' => 0, 'imageH' => 0]
    ];
}

//'10'=> '待付款', '20'=> '待发货','30'=> '待收货','40'=> '待评价','50'=> '已完成','60'=> '已取消', '70'=> '已退货', '80'=> '已退款',
function allOrderStatus()
{
    return [
        10 => '待付款',
        20 => '待发货',
        30 => '待收货',
        40 => '待评价',
        50 => '已完成',
        60 => '已取消',
        70 => '已退货',
        80 => '已退款',
    ];
}


function getOrderStatus($value)
{
    $allOrderStatus = allOrderStatus();
    if (array_key_exists($value, $allOrderStatus)) {
        return $allOrderStatus[$value];
    }
    return 'no value';

}


//'1'=> '直邮中国', '2'=> '中国现货','3'=> '欧境快递','4'=> '大宗货运','5'=> '买家自取'
function allDeliveryTypes()
{
    return [
        1 => ['value' => 1, 'label' => '直邮中国', 'preis' => 0, 'sku_id' => 0, 'checked' => false],
        2 => ['value' => 2, 'label' => '中国现货', 'preis' => 0, 'sku_id' => 0, 'checked' => false],
        3 => ['value' => 3, 'label' => '欧境快递', 'preis' => 0, 'sku_id' => 0, 'checked' => false],
      //  4 => ['value' => 4, 'label' => '大宗货运', 'preis' => 0, 'sku_id' => 0, 'checked' => false],
        5 => ['value' => 5, 'label' => '买家自取', 'preis' => 0, 'sku_id' => 0, 'checked' => false],
    ];
}

function getDeliveryType($type)
{
    $allDeliveryTypes = allDeliveryTypes();
    if (array_key_exists($type, $allDeliveryTypes)) {
        return $allDeliveryTypes[$type]['label'];
    }
    return 'no Type';

}


if (!function_exists('getImgType')) {
    function getImgType($type)
    {
        $array = [
            'admin' => 'a01',
            'shop' => 's01',
            'client' => 'c01',
            'user' => 'u01',
            'brand' => 'b01',
            'category' => 'k01',
            'type' => 'ty01',
            'swiper' => 't01',
            'banner' => 't02',
            'tag' => 't03',
            'tab1' => 't04',
            'tab2' => 't05',
            'service' => 't06',
            'goods_logo' => 'g01',
            'goods_cover' => 'g02',
            'goods_lunbo' => 'g03',
            'goods_detail' => 'g04',
            'goods_sku' => 'g05',
        ];

        if (array_key_exists($type, $array)) {
            return $array[$type];
        }
        return 'no Type';
    }
}

// 应用公共文件
if (!function_exists('encrypt_password')) {
    //密码加密函数
    function encrypt_password($password)
    {
        $salt = 'jinaAndBella';
        return md5($salt . md5($password));
    }
}

if (!function_exists('encrypt_phone')) {
    //手机号加密  15312345678   =》  153****5678
    function encrypt_phone($phone)
    {
        return substr($phone, 0, 3) . '****' . substr($phone, 7);
    }
}


if (!function_exists('get_cate_list')) {
    //递归函数 实现无限级分类列表
    function get_cate_list($list, $pid = 0, $level = 0)
    {
        static $tree = array();
        foreach ($list as $row) {
            if ($row['pid'] == $pid) {
                $row['level'] = $level;
                $tree[] = $row;
                get_cate_list($list, $row['id'], $level + 1);
            }
        }
        return $tree;
    }
}

if (!function_exists('get_tree_list')) {
    //引用方式实现 父子级树状结构
    function get_tree_list($list)
    {
        //将每条数据中的id值作为其下标
        $temp = [];
        foreach ($list as $v) {
            $v['son'] = [];
            $temp[$v['id']] = $v;
        }
        //获取分类树
        foreach ($temp as $k => $v) {
            $temp[$v['pid']]['son'][] = &$temp[$v['id']];
        }
        return isset($temp[0]['son']) ? $temp[0]['son'] : [];
    }
}


if (!function_exists('curl_request')) {
    //使用curl函数库发送请求
    function curl_request($url, $post = true, $params = [], $https = true)
    {
        //初始化请求
        $ch = curl_init($url);
        //默认是get请求。如果是post请求 设置请求方式和请求参数
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        //如果是https协议，禁止从服务器验证本地证书
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        //发送请求，获取返回结果
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        /*if(!$res){
            $msg = curl_error($ch);
            dump($msg);die;
        }*/
        //关闭请求
        curl_close($ch);
        return $res;
    }

}
if (!function_exists('remove_xss')) {
    //使用htmlpurifier防范xss攻击
    function remove_xss($string)
    {
        //composer安装的，不需要此步骤。相对index.php入口文件，引入HTMLPurifier.auto.php核心文件
//         require_once './plugins/htmlpurifier/HTMLPurifier.auto.php';
        // 生成配置对象
        $cfg = HTMLPurifier_Config::createDefault();
        // 以下就是配置：
        $cfg->set('Core.Encoding', 'UTF-8');
        // 设置允许使用的HTML标签
        $cfg->set('HTML.Allowed', 'div,b,strong,i,em,a[href|title],ul,ol,li,br,p[style],span[style],img[width|height|alt|src]');
        // 设置允许出现的CSS样式属性
        $cfg->set('CSS.AllowedProperties', 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align');
        // 设置a标签上是否允许使用target="_blank"
        $cfg->set('HTML.TargetBlank', TRUE);
        // 使用配置生成过滤用的对象
        $obj = new HTMLPurifier($cfg);
        // 过滤字符串
        return $obj->purify($string);
    }
}


if (!function_exists('email_verify')) {
    function email_verify($user)
    {

        $regtime = time();

        $token = md5($user['username'] . $user['password'] . $regtime); //创建用于激活识别码
        \app\common\model\User::update(['token' => $token, 'regtime' => $regtime], ['id' => $user->id], true);

        $mail = new \MyMail\MyMail();
        $mail->AddAddress($user->email, $user->username);
        $mail->Subject = '用户帐号激活';
        $url = WEB_NAME . 'apiUserActive?verify=' . $token;

        $b1 = "感谢您在我站注册了新帐号。<br/>";
        $b2 = "请点击链接激活您的帐号。<br/>";
        $b3 = "<a href=" . $url . "  target='_blank'>" . $url . "</a><br/>";
        $b4 = "如果以上链接无法点击，请将它复制到你的浏览器地址栏中进入访问，该链接24小时内有效。";

        $emailbody = $b1 . $b2 . $b3 . $b4;


        $mail->setTemplate('welcome', [
            'name' => $user->username,
            'date' => date('Y-m-d'),
            'table' => $emailbody
        ], 'zh');
        $mail->Send();
    }

}

function sendOrderMail ($ids) {
    $orderList = \app\common\model\Order::with('user,daddress,baddress,orderGoods')->where('id', 'in', $ids)->select();

    $orderDetails = '';
    $user = null;
    foreach($orderList as $order){
       // printArray($order->daddress);
        if($user == null){
            $user = $order->user;
        }
        $orderDetails .= '<div>';
        $orderDetails .= '<b>订单号：'.$order['order_sn'].'</b> （咨询时请提交此号码）<br />';
        $orderDetails .='<b>配送方式：</b> '.getDeliveryType( $order->delivery_type). '<br />';
        $orderDetails .='<b>送货地址：</b> '.getAddress ($order->daddress). '<br />';
        $orderDetails .='<b>账单地址：</b> '.getAddress($order->baddress). '<br />';

        $oaHeader = [
            '产品名称',
            '单价',
            '购买数量',
            '税率',
            '应付税款',
            '总价(不含税)',
            '总价(含税)',

        ];
        $oaTable = [];
        foreach($order->orderGoods as $goods){
            $price_net = ($goods['goods_price'] / (1 + ($goods['vat'] / 100)))*$goods['amount'];
            $total = $goods['goods_price'] * $goods['amount'];

            $oaTable[] = [
                $goods['goods_name'].' '. $goods['specs'],
                round($goods['goods_price']).' Euro',
                $goods['amount'],
                $goods['vat'].'%',
                round($total-$price_net,2).' Euro',
                round($price_net,2).' Euro',
                round($total,2).' Euro',

            ];
        }
        $oaTable[] = 'hr';
      //  $oaTable[] = ['商品总价（不含税)', '', '', '', '', '',$order['order_net']];
      //  $oaTable[] = ['总税款', '', '', '', '', '',$order['order_vat']];
        $oaTable[] = 'hr';
        $oaTable[] = ['总数', '', '', '', '', '',$order['total_amount']];
        $oaTable[] = ['商品总价', '', '', '','', '', $order['total_price'].' Euro'];
        $oaTable[] = ['运费', '', '', '', '', '',$order['delivery_price'].' Euro'];
        $oaTable[] = 'hr';

        $oaTable[] = ['总价', '', '', '', '', '',($order['total_price'] +$order['delivery_price']).' Euro' ];
        $orderDetails .= \MyMail\MyMail::makeTable($oaTable, $oaHeader);
        $orderDetails .= '<br />';
        $orderDetails .= '<br />';
    }

    //email senden
    $toEmail = $user->email;
    $toUsername = $user->username;
    $mail = new \MyMail\MyMail();
    $mail->AddAddress($toEmail, $toUsername);
    $mail->Subject = 'Ihre Bestellungen bei ' . \think\Config::get('system.name_en');
    $mail->setTemplate('ordermail', [
        'username' => $toUsername,
        'webname' => \think\Config::get('system.name'),
        'orderDetails' => $orderDetails
    ], 'de');

   // echo $mail->Body;
    $mail->Send();
}



function cancelOrder($id){
    $order= \app\common\model\Order::with('user,orderGoods,orderGoods.sku')->find($id);
    //'10'=> '待付款', '20'=> '待发货','30'=> '待收货','40'=> '待评价','50'=> '已完成','60'=> '已取消', '70'=> '已退货', '80'=> '已退款',
    \app\common\model\Order::update(['status'=>60],['id'=>$id],true);
    foreach($order->orderGoods as $goods){
        ////'1'=> '直邮中国', '2'=> '中国现货','3'=> '欧境快递','4'=> '大宗货运','5'=> '买家自取'
        \app\common\model\OrderGoods::update(['status'=>3],['id'=>$id],true);
        if($order->delivery_type == 2){
            $store_cn = $goods->sku->store_cn+$goods->amount;
            \app\common\model\Skus::update(['store_cn'=>$store_cn],['id'=>$goods->sku_id],true);
        } else{
            $store_de = $goods->sku->store_de+$goods->amount;
            \app\common\model\Skus::update(['store_de'=>$store_de],['id'=>$goods->sku_id],true);
        }
    }

    $orderDetails = '';
    //email senden
    $user = $order->user;
    $toEmail = $user->email;
    $toUsername = $user->username;
    $mail = new \MyMail\MyMail();
    $mail->AddAddress($toEmail, $toUsername);
    $mail->Subject = '订单 ' .$order->order_sn.' 已经取消了';
    $mail->setTemplate('ordercancel', [
        'username' => $toUsername,
        'order_sn'=>$order->order_sn,
        'webname' => \think\Config::get('system.name'),
        'orderDetails' => $orderDetails
    ], 'de');

    // echo $mail->Body;
    $mail->Send();
}


function getAddress ($address) {
    $data =$address->consignee.' ' .$address->street.' '.$address->house_number.' '.$address->postcode.' '.$address->city.' '.$address->country;
    return $data;
}


function printArray($arr)
{
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}

/**
 * 写日志
 * @param $logType : 写入哪个日志
 * @param $data : 数据
 */
function writeLog($logType, $data = null){
    if(is_null($data) || is_null($logType)){
        $out_arr['code'] = '400004';
        return $out_arr;
    }

    $path = RUNTIME_PATH . 'log/' . $logType;

    if(!is_dir($path)){
        $mkdir_re = mkdir($path,0777,TRUE);
        if(!$mkdir_re){
            $this->logs($data, $logType);
        }
    }

    $filePath = $path . "/" . date("Y-m-d",time());

    $time = date("Y-m-d H:i:s",time());
    $re = file_put_contents($filePath, $time." ".var_export($data,TRUE)."\r\n\r\n" , FILE_APPEND);

    if(!$re){
        $this->logs($data, $logType);
    }else{
        $out_arr['code'] = '000000';
        return $out_arr;
    }

}




