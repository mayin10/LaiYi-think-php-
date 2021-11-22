<?php
use think\Route;

//adminapi模块首页路由
Route::get('/', 'adminapi/index/index');
Route::get('active', 'adminapi/index/active');
Route::get('test', 'adminapi/index/test');
Route::get('testapi', 'adminapi/index/testapi');
//api******************************************************+api

Route::post('apiDelImg', 'adminapi/upload/delImg');;
Route::post('apiUpload', 'adminapi/Upload/logo');


Route::post('apilogin', 'adminapi/login/login');
Route::post('apilogout', 'adminapi/login/logout');

Route::get('apiMenupath', 'adminapi/menu/menupath');

Route::resource('apiMenu', 'adminapi/menu', [], ['id'=>'\d+']);
Route::get('apiParentMenu', 'adminapi/menu/parentMenu');
Route::post('apiMenuShow', 'adminapi/menu/menushow');
Route::post('apiMenuStatus', 'adminapi/menu/menuStatus');

Route::resource('apiAuth', 'adminapi/auth', [], ['id'=>'\d+']);
Route::post('updateAuth', 'adminapi/role/updateAuths');
Route::get('nav', 'adminapi/auth/nav');

Route::resource('apiRole', 'adminapi/role', [], ['id'=>'\d+']);
Route::get('apiRoleList', 'adminapi/role/getRoleList');

Route::resource('apiShops', 'adminapi/shop', [], ['id'=>'\d+']);
Route::get('apiMyShop', 'adminapi/shop/getShopByUserId');

Route::resource('apiUsers', 'adminapi/user', [], ['id'=>'\d+']);
Route::post('apiToActive', 'adminapi/user/toActive');
Route::get('apiUserActive', 'adminapi/user/active');

Route::resource('apiAddress', 'adminapi/address', [], ['id'=>'\d+']);
Route::post('apiAddAddress','adminapi/address/addAddress');
Route::post('apiChangeDefault','adminapi/address/changeDefalut');
Route::get('apiAddressList','adminapi/address/addressList');
Route::get('apiCountryList','adminapi/address/getCountries');

Route::resource('admins', 'adminapi/admin', [], ['id'=>'\d+']);
Route::post('adminStatus', 'adminapi/admin/status');

Route::resource('apiBrand', 'adminapi/brand', [], ['id'=>'\d+']);
Route::get('apiBrandList', 'adminapi/brand/datalist');

Route::resource('apiCategory', 'adminapi/category', [], ['id'=>'\d+']);
Route::get('apiCategoryList', 'adminapi/category/datalist');
Route::post('apiCateHot', 'adminapi/category/hotChange');

Route::resource('apiType', 'adminapi/type', [], ['id'=>'\d+']);
Route::get('apiTypeList', 'adminapi/type/datalist');
Route::post('apiTypeHot', 'adminapi/type/hotChange');



Route::resource('apiGroups', 'adminapi/group', [], ['id'=>'\d+']);
Route::get('parentList', 'adminapi/group/parentList');

Route::resource('apiAttr', 'adminapi/attr', [], ['id'=>'\d+']);
Route::get('apiAttrByGoods', 'adminapi/attr/getAttrByGoodsId');
Route::get('apiCheckAttr', 'adminapi/attr/checkAttr');

Route::resource('apiTag', 'adminapi/tag', [], ['id'=>'\d+']);
Route::get('apiTagTypes', 'adminapi/tag/getTagTypes');
Route::get('apiTagArray', 'adminapi/tag/getTagArray');
Route::get('apiTagCheckArray', 'adminapi/tag/getTagCheckArray');
Route::post('apiTagDefault', 'adminapi/tag/changeDefault');


Route::resource('apiGoods', 'adminapi/goods', [], ['id'=>'\d+']);

Route::resource('apiSkus', 'adminapi/skus', [], ['id'=>'\d+']);
Route::get('apiCheckSpecs', 'adminapi/skus/checkSpecs');
Route::get('apiDeliveryTypes', 'adminapi/skus/allDeliveryTypes');
Route::post('apiSkuStatus', 'adminapi/skus/statusChange');

Route::resource('apiOrder', 'adminapi/order', [], ['id'=>'\d+']);
Route::get('apiOrderDetail', 'adminapi/order/getDetail');
Route::post('apiCancelOrder', 'adminapi/order/cancelOrder');


//home**********************************************home
Route::get('home', 'home/index/index');








//mobile**********************************************mobile

Route::get('apptestapi', 'mobile/index/testapi');

Route::get('apptest','mobile/index/index');
//Home
Route::get('appHome','mobile/index/home');
Route::get('appSearch','mobile/index/search');
Route::get('appSearchList','mobile/index/searchList');

//Upload
Route::post('appUpload','mobile/index/upload');


//login logout
Route::post('appLogin','mobile/login/login');
Route::post('appLogout','mobile/login/logout');
Route::post('appRegister','mobile/login/register');
Route::post('appVerify','mobile/login/verify');
Route::post('appCodeVerify','mobile/login/codeVerify');
Route::post('appPswReset','mobile/login/passwordReset');
Route::post('appPswModify','mobile/login/passwordModify');
//goods
Route::get('appGoodsList','mobile/goods/goods');
Route::get('appGoodsdetail/:id','mobile/goods/detail');
Route::get('appCategory','mobile/goods/category');

//keep
Route::post('appIsKeep','mobile/goods/isKeep');
Route::post('appUpdateKeep','mobile/goods/updateKeep');
Route::get('appKeepGoods','mobile/goods/getKeepGoods');
//Cart
Route::get('cart','mobile/cart/getCart');
Route::post('addCart','mobile/cart/addCart');
Route::post('changeNum','mobile/cart/changeNum');
Route::post('delCart','mobile/cart/delCart');

//Address
Route::resource('appAddress', 'mobile/address', [], ['id'=>'\d+']);
Route::post('addAddress','mobile/address/addAddress');
Route::delete('appDeleteAddress','mobile/address/delete');
Route::get('getDefaultAddress','mobile/address/getDefaultAddress');
Route::get('appGetAddress','mobile/address/getEditAddress');
Route::post('appChangeDefault','mobile/address/changeDefalut');
Route::get('appCountryList','mobile/address/getCountries');

//Order
Route::post('addOrder','mobile/order/addOrder');
Route::post('appCancelOrder','mobile/order/cancelOrder');
Route::get('appOrderList','mobile/order/getOrderList');
Route::get('appPaynow','mobile/order/paynow');
Route::post('appPayFinish','mobile/order/finish');
Route::post('appPayNotify','mobile/order/notify');
Route::get('appGetOrder','mobile/order/getOrder');
Route::get('appGetOrderDetail','mobile/order/getOrderDetail');

