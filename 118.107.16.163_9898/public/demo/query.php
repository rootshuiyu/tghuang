<?php

// 支付接口 URL
$url = 'http://103.207.69.252/api/pay/query';

// 商户信息
$appid  = '54062819';            // 商户 PID
$appkey = '3ef3d98a-0876-4852-8512-de772f39a719';            // 商户秘钥
$suporder = 'P1965763464505413633';  // 商户订单号（示例值，实际使用时请替换）

// 构建请求数据
$data = [
    'appid'     => $appid,
    'suporder'  => $suporder,
    'timestamp' => time(),  // 当前时间戳
];

// 生成签名
$data['sign'] = sign(
    $appid.
    $appkey.
    $data['suporder'].
    $data['timestamp']
);

// 发送 HTTP 请求并获取响应
$http_response = http_send($url, $data);

// 打印响应结果
print_r($http_response);

$json = json_encode($http_response,JSON_PRETTY_PRINT);
file_put_contents('response/query.json',$json);

// 签名方法
function sign($str) {
    return hash('sha256', $str);
}

// 提交方法
function http_send($url, $params) {
    if ($params) {
        $url = $url . '?' . http_build_query($params);
    }

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output,true);
    return $output;
}