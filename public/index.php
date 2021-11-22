<?php

//处理跨域Options预检请求
if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
    //允许的源域名
    header("Access-Control-Allow-Origin: *");
    //允许的请求头信息
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
    //允许的请求类型
    header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');
    exit;
}
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
