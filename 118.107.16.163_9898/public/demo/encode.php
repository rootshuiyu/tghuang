<?php
/**
 * 字符串加密函数
 * @param string $str 要加密的字符串
 * @return string 加密后的字符串
 */
function encryptString($str) {
    // 第一步：将字符串转换为base64
    $base64 = base64_encode($str);
    
    // 第二步：打乱base64字符串的顺序
    // 方法：将字符串分为两半，然后交叉合并
    $length = strlen($base64);
    $half = ceil($length / 2);
    $firstHalf = substr($base64, 0, $half);
    $secondHalf = substr($base64, $half);
    
    $encrypted = '';
    $maxLength = max(strlen($firstHalf), strlen($secondHalf));
    for ($i = 0; $i < $maxLength; $i++) {
        if (isset($firstHalf[$i])) {
            $encrypted .= $firstHalf[$i];
        }
        if (isset($secondHalf[$i])) {
            $encrypted .= $secondHalf[$i];
        }
    }
    
    return $encrypted;
}

/**
 * 字符串解密函数
 * @param string $str 要解密的字符串
 * @return string 解密后的字符串
 */
function decryptString($str) {
    // 第一步：还原被打乱的顺序
    $firstHalf = '';
    $secondHalf = '';
    $length = strlen($str);
    
    for ($i = 0; $i < $length; $i++) {
        if ($i % 2 === 0) {
            $firstHalf .= $str[$i];
        } else {
            $secondHalf .= $str[$i];
        }
    }
    
    // 第二步：合并并解码base64
    $base64 = $firstHalf . $secondHalf;
    return base64_decode($base64);
}

// 测试用例
echo "===== PHP加密解密测试 =====\n";
$original = "Hello, World! 这是一个测试字符串。";
echo "原始字符串: " . $original . "\n";

$encrypted = encryptString($original);
echo "加密后: " . $encrypted . "\n";

$decrypted = decryptString('eeyEJV5IYc0V9NoLRMHGdZyZSIUiEwzidYW299HkMZVSVI66MMmSdwmibbFXBNXnZIGjJoOicbzmR8hieLGCVJYkVYCXIR6hIIljFppbTXlSYw3iedjGllEtcZkSkIy6NMWT9cq1eOVDdIiwNNHzBEDyYM3nd0S');
echo "解密后: " . $decrypted . "\n";

// 验证解密是否成功
if ($original === $decrypted) {
    echo "测试通过：解密后字符串与原始字符串一致！\n";
} else {
    echo "测试失败：解密后字符串与原始字符串不一致！\n";
}
?>