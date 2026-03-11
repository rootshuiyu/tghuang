<?php

namespace support\tencent;

class Teo
{
    private $secretId = '';   // 通过环境变量 TENCENT_TEO_SECRET_ID 配置
    private $secretKey = '';  // 通过环境变量 TENCENT_TEO_SECRET_KEY 配置
    private $host = 'teo.tencentcloudapi.com';
    private $parm_url = [
        'ACCOUNT_IMPORT_URL' => "v4/im_open_login_svc/account_import", //单个账号倒入
        'ACCOUNT_DELETE_URL' => "v4/im_open_login_svc/account_delete", //删除账号
    ];
    
    protected $user_id = '123456';
    
    public function __construct()
    {
        
        
        
    }
    
    public function vv2()
    {
        
        return $this->vv3();
        
    }
    
    public function vv3()
    {
        
        return $this->user_id;
        
    }

    /**
     * 【功能说明】进行IM 生成url
     * @param string admin_id 管理人员账号
     * @param string path 接口路径
     */
    public function http_post($payload = '{}')
    {
        $secret_id = $this->secretId;
        $secret_key = $this->secretKey;
        $token = "";
        $service = "teo";
        $host = "teo.tencentcloudapi.com";
        $req_region = "";
        $version = "2022-09-01";
        $action = "DescribeOriginGroup";
        //$params = json_decode($payload);
        $params = $payload;
        $endpoint = "https://teo.tencentcloudapi.com";
        $algorithm = "TC3-HMAC-SHA256";
        $timestamp = time();
        $date = gmdate("Y-m-d", $timestamp);
        
        // ************* 步骤 1：拼接规范请求串 *************
        $http_request_method = "POST";
        $canonical_uri = "/";
        $canonical_querystring = "";
        $ct = "application/json; charset=utf-8";
        $canonical_headers = "content-type:".$ct."\nhost:".$host."\nx-tc-action:".strtolower($action)."\n";
        $signed_headers = "content-type;host;x-tc-action";
        $hashed_request_payload = hash("sha256", $payload);
        $canonical_request = "$http_request_method\n$canonical_uri\n$canonical_querystring\n$canonical_headers\n$signed_headers\n$hashed_request_payload";
        
        // ************* 步骤 2：拼接待签名字符串 *************
        $credential_scope = "$date/$service/tc3_request";
        $hashed_canonical_request = hash("sha256", $canonical_request);
        $string_to_sign = "$algorithm\n$timestamp\n$credential_scope\n$hashed_canonical_request";
        
        // ************* 步骤 3：计算签名 *************
        $secret_date = $this->sign("TC3".$secret_key, $date);
        $secret_service = $this->sign($secret_date, $service);
        $secret_signing = $this->sign($secret_service, "tc3_request");
        $signature = hash_hmac("sha256", $string_to_sign, $secret_signing);
        
        // ************* 步骤 4：拼接 Authorization *************
        $authorization = "$algorithm Credential=$secret_id/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";
        
        // ************* 步骤 5：构造并发起请求 *************
        $headers = [
            "Authorization" => $authorization,
            "Content-Type" => "application/json; charset=utf-8",
            "Host" => $host,
            "X-TC-Action" => $action,
            "X-TC-Timestamp" => $timestamp,
            "X-TC-Version" => $version
        ];
        if ($req_region) {
            $headers["X-TC-Region"] = $req_region;
        }
        if ($token) {
            $headers["X-TC-Token"] = $token;
        }
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_map(function ($k, $v) { return "$k: $v"; }, array_keys($headers), $headers));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($response,true);
            return $output;
        } catch (Exception $err) {
            echo $err->getMessage();
        }
                
    }
    
    private function sign($key, $msg) {
        return hash_hmac("sha256", $msg, $key, true);
    }


}
