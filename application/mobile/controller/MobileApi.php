<?php


namespace app\mobile\controller;

use think\Controller;

class MobileApi extends Controller{

    protected $no_login = ['index/*','login/*', 'goods/goods','goods/detail','goods/category','order/paynow','order/finisch','order/notify'];
    protected function _initialize(){

        parent::_initialize();
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
        header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');

        $path = strtolower($this->request->controller()) . '/' . $this->request->action();
        if(!in_array($path, $this->no_login) && !in_array(strtolower($this->request->controller()) . '/*', $this->no_login)){
            try{
                $user_id = \tools\jwt\Token::getUserId();
                if($user_id == 0){
                  //  $this->fail('token验证失败', 403);
                    $this->fail($path, 403);
                }
                $this->request->get(['user_id' => $user_id]);
                $this->request->post(['user_id' => $user_id]);
            }catch (\Exception $e){
                //token解析失败
                $this->fail('token解析失败', 406);
            }
        }
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

    public function getImg($type_id,$type){
        $type = getImgType($type);
        $img = \app\common\model\Images::where(['type_id'=> $type_id, 'type'=> $type])->find();
        if($img){
            return WEB_NAME.$img->img_sma;
        }else{
            return  WEB_NAME ."/uploads/no-image.png";
        }
    }

    public function getImgs($type_id,$type){
        $type = getImgType($type);
        $where =['type_id'=> $type_id, 'type'=> $type];
        $imgs = \app\common\model\Images::where($where)->select();
        foreach($imgs as $v){
            $v->img_big = WEB_NAME.$v->img_big;
        }
        unset($v);
        return $imgs;

    }

}