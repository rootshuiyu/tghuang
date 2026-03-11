<?php

// 支付接口 URL
$url = 'http://cashier.help/api/pay/bulid';

// 商户 appid
$appid = $_GET['appid'];

// 商户秘钥
$appkey = $_GET['appkey'];



// 订单数据数组
$data = [
    'appid'      => $appid,            // 商户 appid
    'suporder'   => 'OR' . time().rand(1000000,9999999),   // 商户订单号（时间戳 + 随机数）
    'fee'        => $_GET['fee'],      // 订单金额（单位为元）
    'timestamp'  => time() . rand(100000,999999),          // 时间戳
    'pay_type'   => $_GET['pay_type'],               // 支付方式，1:QQ支付，2微信支付，3支付宝 
    'code'       => $_GET['code'],              // 通道编码（空值，根据实际情况填写）
    'mid'        => $_GET['mid'],        // 账号MID
    'return_url' => 'https://id1.cloud.huawei.com/AMW/portal/home.html',           // 同步跳转地址
    'notify_url' => '',  // 异步通知地址
];

// 生成签名
$data['sign'] = sign(
    $appid .              // 商户 appid
    $appkey .             // 商户秘钥
    $data['suporder'] .   // 商户订单号
    $data['fee'] .        // 订单金额
    $data['timestamp']    // 时间戳
);



// 发送 HTTP 请求
$http = http_send($url, $data);

$json = json_encode($http,JSON_PRETTY_PRINT);
file_put_contents('response/build.json',$json);

// 打印响应结果
//print_r($http);

if($http['code'] == 1){
    print_r($http['data']['url']);
    header("Location: ".$http['data']['url']);
}

// 签名方法
// 订单金额如果为整数，使用 1 而不是 1.00
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
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);  // 设置超时时间为10秒
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output,true);
    return $output;
}