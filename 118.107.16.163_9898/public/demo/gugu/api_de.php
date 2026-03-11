<?php
header('Content-Type: application/json; charset=utf-8');

// 获取 POST 数据
$data = isset($_POST['data']) ? $_POST['data'] : '';

if (empty($data)) {
    echo json_encode(['error' => '没有提供数据'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo decrypt_aes_cbc($data);

function decrypt_aes_cbc($encrypted_data) {
        // 配置参数
        $key = 'jLPxjtrryxNMF3fP';
        $iv = 'aI0noh23QBCVYk0T';
        
        // 步骤1: Base64解码
        $encrypted_bytes = base64_decode($encrypted_data);
    
        // 步骤2: AES/CBC解密
        $decrypted_padded = openssl_decrypt($encrypted_bytes, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        
        if ($decrypted_padded === false) {
            return 'AES解密失败';
        }
        
        //return $decrypted_padded;
        
        // 步骤3: 去除PKCS7填充
        //$pad = ord($decrypted_padded[strlen($decrypted_padded) - 1]);
        //$decrypted_data = substr($decrypted_padded, 0, -$pad);
        
        // 步骤4: Base64解码
        $base64_decoded = base64_decode($decrypted_padded);
     
        // 步骤5: Gzip解压缩
        $decompressed_data = gzdecode($base64_decoded);

        if ($decompressed_data === false) {
            return 'Gzip解压缩失败';
        }
        
        return $decompressed_data;
    }

?>