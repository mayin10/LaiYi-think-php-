<?php

namespace app\adminapi\controller;

use think\Controller;

class BaseApi extends Controller
{
    //无需登录的请求数组
    protected $no_login = ['login/login','login/logout', 'zo/*', 'type/*','user/active','index/*'];
    protected $no_check = ['menu/menupath','login/logout'];


    protected function _initialize()
    {
        //实现父类的初始化方法
        parent::_initialize();
        //初始化代码
        //处理跨域请求
        //允许的源域名
        header("Access-Control-Allow-Origin: *");
        //允许的请求头信息
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
        //允许的请求类型
        header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');

        try{
            //登录检测
            //获取当前请求的控制器方法名称
            $path = strtolower($this->request->controller()) . '/' . $this->request->action();
            if(!in_array($path, $this->no_login) && !in_array(strtolower($this->request->controller()) . '/*', $this->no_login)){
                //需要做登录检测
                $user_id = \tools\jwt\Token::getUserId();
                if($user_id == 0){
                    $this->fail('token验证失败', 403);
                }

                //将得到的用户id 放到请求信息中去  方便后续使用
                $this->request->get(['user_id' => $user_id]);
                $this->request->post(['user_id' => $user_id]);



                //权限检测
                if(!in_array($path, $this->no_check)){
                    $this->authCheck();
                }
            }
        }catch (\Exception $e){
            $this->fail($e->getMessage(), $e->getCode());
        }
    }


    protected function authCheck(){
        $admin = $this->getAdmin();
        //2->一级  增删改查  2->二级  只可以查看数据，修改    2->三级 只可以查看数据
        $role_id = $admin->role->id;
        $action = $this->request->action();
        if($role_id == 4){
            $no_check_l1 = [strtolower('getShopByUserId'),'index', 'read'];
            if(!in_array($action, $no_check_l1)){
                $this->fail('三级用户只可以查看数据', 402);
            }
        } else if($role_id == 3){
            $check_l2 = ['delete'];
            if(in_array($action, $check_l2)){
                $this->fail('二级用户不可以删除数据', 402);
            }
        }
    }


    protected function get_wls_img($type, $type_id){
        $type =  getImgType($type);
        $img = \app\common\model\Images::where(['type_id'=>$type_id,'type'=>$type])->find();
        return $img ? WEB_NAME.$img->img_sma : "";
    }

    protected function  getImg($type, $type_id){
        $type =  getImgType($type);
        $img = \app\common\model\Images::where(['type_id'=>$type_id,'type'=>$type])->select();
        foreach($img as $v){
            $v['name'] = $v->id.'-'.'shop';
            $v['url'] = WEB_NAME .$v->img_sma;
        }
        return $img;
    }




    /**
     * 通用的响应
     * @param int $code 错误码
     * @param string $msg 错误信息
     * @param array $data 返回数据
     */
    protected function response($code=200, $msg='success', $data=[])
    {
        $res = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];
        //原生php写法
        echo json_encode($res, JSON_UNESCAPED_UNICODE);die;
        //框架写法
        //json($res)->send();

    }
    /**
     * 成功的响应
     * @param array $data 返回数据
     * @param int $code 错误码
     * @param string $msg 错误信息
     */
    protected function ok($data=[], $code=200, $msg='success')
    {
        $this->response($code, $msg, $data);
    }

    /**
     * 失败的响应
     * @param $msg 错误信息
     * @param int $code 错误码
     * @param array $data 返回数据
     */
    protected function fail($msg, $code=500, $data=[])
    {
        $this->response($code, $msg, $data);
    }

    protected function getAdmin(){
        $user_id =input('user_id');
        $admin =  \app\common\model\Admin::with('shop,role')->find($user_id);
        return $admin;
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


    protected function addImgList($data,$type_id,$type,$size_sw,$size_sh,$size_bw,$size_bh){

        foreach($data as $img){
            if(strpos($img,"temp")>-1) {
                $admin =  $this->getAdmin();
                //每个shop都有单独的文件夹
                if($admin->shop_name == null){$shop_name = 'wls_shop';}
                $dir1 = ROOT_PATH .'public'. DS. 'uploads'.DS.$admin->shop_name;
                if(!is_dir($dir1)) mkdir($dir1);

                $image = \think\Image::open('.' . $img);
                $dirname = str_replace("temp",$admin->shop_name,dirname($img));
                $dir2 = ROOT_PATH .'public'.$dirname;
                if(!is_dir($dir2)) mkdir($dir2);
                $img_new_s = $dirname . DS . $admin->shop_name.'_s_' . basename($img);
                $img_new_b = $dirname . DS . $admin->shop_name.'_b_' . basename($img);
                //调用thumb方法生成缩略图并保存（直接覆盖原始图片）
                $image->thumb($size_bw, $size_bh)->save('.' . $img_new_b);
                $image->thumb($size_sw, $size_sh)->save('.' . $img_new_s);

                //删除 temp里的文件
                $temp =  ROOT_PATH .'public'.$img;
                if(is_file($temp)){unlink($temp);}

                $img_data = [
                    'shop_id'=>$admin->shop_id,
                    'type_id'=>$type_id,
                    'type'=> getImgType($type),
                    'img_sma'=> $img_new_s,
                    'img_big'=> $img_new_b,
                    'create_by'=>$admin->username,
                    'update_by'=>$admin->username];
                \app\common\model\Images::create($img_data,true);
            }
        }
    }




    public function removeImgs($removeIds,  $type_id, $type){
        $imgs = null;
        if($removeIds != null){
            $imgs= \app\common\model\Images::where('id','in',$removeIds)->select();
        } else {
            $imgs = \app\common\model\Images::where([ 'type_id'=> $type_id, 'type'=>$type])->select();
        }
        if($imgs){
            foreach($imgs as $img){
                $alt_sma =  ROOT_PATH .'public'.$img->img_sma;
                if(is_file($alt_sma)){
                    unlink($alt_sma);
                }
                $alt_big =  ROOT_PATH .'public'.$img->img_big;
                if(is_file($alt_big)){
                    unlink($alt_big);
                }
                \app\common\model\Images::destroy($img->id);
            }
        }
    }

    protected function getImgList($type, $type_id)
    {
        $where =[
            'type_id'=> $type_id,
            'type'=>  getImgType($type)
        ];
        $imgs = \app\common\model\Images::where($where)->select();
        foreach($imgs as $v){
            $v['name'] = $v->id.'-'.'shop';
            if ($type=='goods_lunbo'|| $type=='goods_detail'){
                $v['url'] = WEB_NAME .$v->img_big;
            } else{
                $v['url'] = WEB_NAME .$v->img_sma;
            }

        }
        return $imgs;
    }



}
