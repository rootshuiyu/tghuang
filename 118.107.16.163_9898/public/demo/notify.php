<?php

// 商户信息
$appid  = '42783196'; // 商户PID
$appkey = '1ca74260-81b8-4cee-a91e-00c384052389'; // 商户秘钥

// 获取POST请求的数据
$data = $_POST;

// 生成签名
$generatedSign = sign(
    $appid  . 
    $appkey . 
    (isset($data['suporder'])  ? $data['suporder'] : '') . 
    (isset($data['timestamp']) ? $data['timestamp'] : '')
);

// 签名验证
if ($generatedSign === $data['sign']) {
    $json = json_encode($_POST,JSON_PRETTY_PRINT);
    file_put_contents('response/notify.json',$json);
    // 返回成功响应
    echo 'ok'; // 推荐返回ok，返回其它也视为成功
    exit;
    
} else {
    // 签名验证失败，可以执行一些错误处理逻辑
}

// 签名方法
function sign($str) {
    return hash('sha256', $str);
}