<?php
namespace app\adminapi\controller;
use think\Log;

class Index extends BaseApi
{


    public function index()
    {
        $user_data =[
            'name'=>'admin',
            'username'=>'admin',
            'password'=>'123456',
            'email'=>'jina2016001@gmail.com',
            'create_by'=>'admin',
            'update_by'=>'admin'];
        $admin = \app\common\model\Admin::create($user_data, true);
    }

    //vorher $data[0]
    protected function addImg($img,$type_id,$type,$size_w,$size_h){





        if(!empty($img) && (strpos($img,"temp")>-1)){
            $admin =  $this->getAdmin();
            //每个shop都有单独的文件夹
            $shop_name = $admin->shop_name== null ? 'wls_shop':$admin->shop_name;
            $username = $admin->username;
            $dir1 = ROOT_PATH .'public'. DS. 'uploads'.DS.$shop_name;
            if(!is_dir($dir1)) mkdir($dir1);

            $image = \think\Image::open('.' .$img);
            if($image == null){
                $this->fail('图片上传错误！');
            }
            $dirname = str_replace("temp",$shop_name,dirname($img));
            $dir2 = ROOT_PATH .'public'.$dirname;
            if(!is_dir($dir2)) mkdir($dir2);
            $img_new = $dirname . DS . $shop_name.'_' . basename($img);
            //调用thumb方法生成缩略图并保存（直接覆盖原始图片）
            $image->thumb($size_w, $size_h)->save('.' . $img_new);

            //删除 temp里的文件
            $temp =  ROOT_PATH .'public'.$img;
            if(is_file($temp)){unlink($temp);}

            //生成新店铺时判断
            $shop_id = $admin->shop_id;
            if($type == 'shop'){
                $shop_id = $type_id;
            }
            $type =  getImgType($type);
            $data = \app\common\model\Images::where(['type_id'=>$type_id,'type'=>$type])->find();

            if(!$data){
                $img_data = [
                    'shop_id'=>$shop_id,
                    'type_id'=>$type_id,
                    'type'=>$type,
                    'img_sma'=> $img_new,
                    'create_by'=>$username,
                    'update_by'=>$username];
                \app\common\model\Images::create($img_data,true);
            } else{
                $alt =  ROOT_PATH .'public'.$data->img_sma;
                if(is_file($alt)){unlink($alt);}
                \app\common\model\Images::update(['img_sma' => $img_new, 'update_by'=>$username], ['type_id'=>$type_id, 'type'=>$type]);
            }
        }
    }

    public function testapi(){
        $nr ="16324034688895_1_2_3_5";

        $order_nr1 = strpos($nr,'_');
        $sub1 = substr($nr, 0,$order_nr1);
        $sub2 = substr($nr, $order_nr1+1);
        $sub2 = str_replace('_',',',$sub2);

        $where['order_sn'] =['like',$sub1.'%'];
        $where['delivery_type' ] = ['in',$sub2];
        $orderlist =  \app\common\model\Order::where($where)->select();
        $this->ok($orderlist);
    }


















    private function getGoodPreis($goods_id){
        $min = 0;
        $max = 0;
        $skuList = \app\common\model\Skus::where(['goods_id'=>$goods_id, 'status'=>1 ])->order(['price_1'=>'asc'])->select();
        $count = count($skuList);
        $index = 0;
        foreach($skuList as $sku){
            if($index == 0){
                $min =  $sku->price_1;
            }
            $index ++;
            if($index == $count -1){
                $max = $sku->price_1;
            }
        }
        if($max > 0){
            return strval($min).'€ - '.strval($max).'€';
        }else{
            return strval($min).'€';
        }
    }



    public function active(){

        $params = input();
        $verify = $params['verify'];
        $user = \app\common\model\User::where(['token'=>$verify])->find();

        if($user == null){
            $msg = '<h1>账户错误</h1>';
        }
        $nowtime = time();
        //60*60*24 = 86400
        if($nowtime -$user['regtime']  > 86400  ){
            $msg = '<h1>您的激活有效期已过，<br/>请登录您的帐号重新发送激活邮件.</h1>';
            echo $msg; die;
        }else{
            $data = [
                'token'=>null,
                'regtime'=>null,
                'active'=>1
            ];
            \app\common\model\User::update($data, ['id' => $user->id], true);
            $msg = '<h1>恭喜您，帐号激活成功！</h1><br/>请登录！';
        }
        echo $msg; die;
    }


    public function setDefault($address){
        $count = \app\common\model\Address::where(['user_id'=>$address->user_id])->count();
        if($count == 1){
            \app\common\model\Address::update(['is_default_delivery'=>1,'is_default_bill'=>1],['id'=>$address->id],true);
        }
    }

    public function test11 () {


        $user =  \app\common\model\User::get(3);

        $regtime = time();

        $token = md5($user['username'].$user['password'].$regtime); //创建用于激活识别码
        $token_exptime = time()+60*60*24;//过期时间为24小时后
        $data = [
            'token'=>$token,
            'regtime'=>$regtime
        ];
        $info = \app\common\model\User::update($data, ['id' => $user->id], true);

        $url = WEB_NAME.'active?verify='.$token;
        $b1 = "亲爱的".$user->username."：<br/>感谢您在我站注册了新帐号。<br/>";
        $b2 = "请点击链接激活您的帐号。<br/>";
        $b3  = "<a href=".$url."  target='_blank'>".$url."</a><br/>";
        $b4 = "如果以上链接无法点击，请将它复制到你的浏览器地址栏中进入访问，该链接24小时内有效。";

        $emailbody =  $b1. $b2. $b3. $b4;

        email_verify($user);
       echo $emailbody; die;
    }

}
