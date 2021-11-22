<?php


namespace app\adminapi\controller;


use think\Request;

class Address extends BaseApi
{
 //1. 只能有一个默认地址
// 2.生成新地址时， 如果没有默认地址， 当前地址自动设为默认


    public function index(){
        //接收参数  keyword  page
        $params = input();
        $where = [];
        $where['user_id'] = $params['cur_user_id'];
        //搜索条件 多字段相同条件的OR
        if(!empty($params['keyword'])){
            $keyword = $params['keyword'];
            $where['consignee|email|country|city|postcode|street'] = ['like', "%$keyword%"];
        }
        $listRow = 6;
        if(!empty($params['pagesize'])){
            $listRow = $params['pagesize'];
        }
        $list = \app\common\model\Address::where($where)->order(['is_default_delivery'=>'desc','is_default_bill'=>'desc'])->paginate($listRow);
        $this->ok($list);
    }

    public function save(Request $request)
    {
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
        $params['user_id'] =  $params['cur_user_id'];

        $admin =  $this->getAdmin();
        $params['create_by'] = $admin->username;
        $params['update_by'] = $admin->username;
        $address = \app\common\model\Address::create($params,true);
        $this->setDefault($address);
        $this->ok($address);

    }

    public function read($id)
    {
        $this->checkAddress($id);
        $data = \app\common\model\Address::find($id);
        $this->ok($data);
    }

    public function update(Request $request, $id)
    {
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
        $admin =  $this->getAdmin();
        $data['update_by'] = $admin->username;
        \app\common\model\Address::update($params, ['id'=>$id], true);
        $this->ok();
    }

    public function addAddress()
    {
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
        $params['user_id'] =  $params['cur_user_id'];

        $admin =  $this->getAdmin();
        $params['create_by'] = $admin->username;
        $params['update_by'] = $admin->username;
        $address = \app\common\model\Address::create($params,true);
        $this->setDefault($address);
        $this->ok($address);

    }


    private function setDefault($address){
        $count = \app\common\model\Address::where(['user_id'=>$address->user_id])->count();
        if($count == 1){
            \app\common\model\Address::update(['is_default_delivery'=>1,'is_default_bill'=>1],['id'=>$address->id],true);
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

    public function addressList(){
        $params = input();
        $where = [];
        $where['user_id'] = $params['api_user_id'];
        $list = \app\common\model\Address::where($where)->order(['is_default_delivery'=>'desc','is_default_bill'=>'desc'])->select();
        $this->ok($list);
    }



    public function delete($id)
    {
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