<?php

namespace safe;
use think\Exception;

class Aes
{
    private $secrect_key = '5bbaa89895798c7a5d519c89ae4c6af2';

    public function __construct($secrect_key = '')
    {
        $this->secrect_key = '5bbaa89895798c7a5d519c89ae4c6af2';
    }
    
    public function encrypt($data,$iv = '') {
        // 密钥和初始向量（IV）
        $key = $this->secrect_key;//openssl_random_pseudo_bytes(32); // AES-256位长度的密钥
        //$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        
        $encryptedData = openssl_encrypt(
            $data,
            'aes-256-ecb',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        return base64_encode($encryptedData);
    }
    
    public function decrypt($enData) {
        
        try {
            if($iv === ''){
                //throw new Exception("3 not provided.");
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        //$iv = base64_decode($iv);
        $key = $this->secrect_key;
    
        // Base64 解码加密的数据
        $enData = base64_decode($enData);
    
        // 使用openssl_decrypt函数解密数据
        $decryptedData = openssl_decrypt(
            $enData,
            'aes-256-ecb',
            $key,
            OPENSSL_RAW_DATA, // 使用相同的加密模式
            $iv
        );
        
        if($decryptedData === false){
            return false;
        }
    
        return $decryptedData;
    }


}