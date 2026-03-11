<?php

namespace Tencent;

if ( version_compare( PHP_VERSION, '5.1.2' ) < 0 ) {
    trigger_error( 'need php 5.1.2 or newer', E_USER_ERROR );
}

class Trtc {
    
    private $sdkappid = '1600027105';
    private $key = '1ef327175875991485b68d811e8a1d892b442923cc113108fef69e4baab88dfe';
    private $nkey = '7815696ecbf1c96e6894b779456d330e';
    
    private $Trtcurl = 'https://trtc.ap-chengdu.tencentcloudapi.com/';
    
    public function __construct( $sdkappid = '', $key = '') {
        if($sdkappid){
            $this->sdkappid = $sdkappid;
            $this->key = $key;
        }
        
    }
    
    
    
    public function RemoveUser($mid = '',$userid = ''){
        
        $secret_id = getenv('TENCENT_SECRET_ID') ?: '';
        $secret_key = getenv('TENCENT_SECRET_KEY') ?: '';
        $token = "";
        
        $service = "trtc";
        $host = "trtc.tencentcloudapi.com";
        $req_region = "ap-beijing";
        $version = "2019-07-22";
        $action = "RemoveUserByStrRoomId";
        //$payload = "{\"SdkAppId\":1600027105,\"RoomId\":\"7632232\",\"UserIds\":[\"3582374\"]}";
        
        if(!is_array($userid)){
            $item = ["$userid"];
        }else{
            foreach ($userid as $v){
                $item[] = "$v";
            }
        }
        
        $params = [
            'SdkAppId' => 1600027105,
            'RoomId' => "$mid",
            'UserIds' => $item
        ];
        $payload = json_encode($params);
        
        //$params = json_decode($payload);
        $endpoint = "https://trtc.tencentcloudapi.com";
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
        $secret_date = $this->trtcsign("TC3".$secret_key, $date);
        $secret_service = $this->trtcsign($secret_date, $service);
        $secret_signing = $this->trtcsign($secret_service, "tc3_request");
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
            $response = json_decode($response,true);
            
            if($response['Response']['Error']){
                
                return false;
            }
            return true;
        } catch (Exception $err) {
            return false;
        }
        
    }
    
    private function trtcsign($key, $msg) {
        return hash_hmac("sha256", $msg, $key, true);
    }
    
    public function hmac_sign($body,$sign) {
        $hash = $this->hmac_sha256($body);
        if($hash != $sign){
            return false;
        }
		return json_decode($body,true);
    }
    
    private function hmac_sha256($body) {
        $hash = hash_hmac( 'sha256', $body, $this->nkey, true );
		return base64_encode($hash);
    }

    
    //腾讯trtc业务
    public function genUserSig( $userid, $expire = 86400*180 ) {
        
        return $this->__genSig( $userid, $expire, '', false );
    }

    public function genPrivateMapKey( $userid, $expire, $roomid, $privilegeMap ) {
        $userbuf = $this->__genUserBuf( $userid, $roomid, $expire, $privilegeMap, 0, '' );
        return $this->__genSig( $userid, $expire, $userbuf, true );
    }

    public function genPrivateMapKeyWithStringRoomID( $userid, $expire, $roomstr, $privilegeMap ) {
        $userbuf = $this->__genUserBuf( $userid, 0, $expire, $privilegeMap, 0, $roomstr );
        return $this->__genSig( $userid, $expire, $userbuf, true );
    }

    private function base64_url_encode( $string ) {
        static $replace = Array( '+' => '*', '/' => '-', '=' => '_' );
        $base64 = base64_encode( $string );
        if ( $base64 === false ) {
            throw new \Exception( 'base64_encode error' );
        }
        return str_replace( array_keys( $replace ), array_values( $replace ), $base64 );
    }
    private function base64_url_decode( $base64 ) {
        static $replace = Array( '+' => '*', '/' => '-', '=' => '_' );
        $string = str_replace( array_values( $replace ), array_keys( $replace ), $base64 );
        $result = base64_decode( $string );
        if ( $result == false ) {
            throw new \Exception( 'base64_url_decode error' );
        }
        return $result;
    }

    private function __genUserBuf( $account, $dwAuthID, $dwExpTime, $dwPrivilegeMap, $dwAccountType,$roomStr ) {
     
        //cVer  unsigned char/1 版本号，填0
        if($roomStr == '')
            $userbuf = pack( 'C1', '0' );
        else
            $userbuf = pack( 'C1', '1' );
        
        $userbuf .= pack( 'n', strlen( $account ) );
        //wAccountLen   unsigned short /2   第三方自己的帐号长度
        $userbuf .= pack( 'a'.strlen( $account ), $account );
        //buffAccount   wAccountLen 第三方自己的帐号字符
        $userbuf .= pack( 'N', $this->sdkappid );
        //dwSdkAppid    unsigned int/4  sdkappid
        $userbuf .= pack( 'N', $dwAuthID );
        //dwAuthId  unsigned int/4  群组号码/音视频房间号
        $expire = $dwExpTime + time();
        $userbuf .= pack( 'N', $expire );
        //dwExpTime unsigned int/4  过期时间 （当前时间 + 有效期（单位：秒，建议300秒））
        $userbuf .= pack( 'N', $dwPrivilegeMap );
        //dwPrivilegeMap unsigned int/4  权限位
        $userbuf .= pack( 'N', $dwAccountType );
        //dwAccountType  unsigned int/4
        if($roomStr != '')
        {
            $userbuf .= pack( 'n', strlen( $roomStr ) );
            //roomStrLen   unsigned short /2   字符串房间号长度
            $userbuf .= pack( 'a'.strlen( $roomStr ), $roomStr );
            //roomStr   roomStrLen 字符串房间号
        }
        return $userbuf;
    }
    private function hmacsha256( $identifier, $curr_time, $expire, $base64_userbuf, $userbuf_enabled ) {
        $content_to_be_signed = 'TLS.identifier:' . $identifier . "\n"
        . 'TLS.sdkappid:' . $this->sdkappid . "\n"
        . 'TLS.time:' . $curr_time . "\n"
        . 'TLS.expire:' . $expire . "\n";
        if ( true == $userbuf_enabled ) {
            $content_to_be_signed .= 'TLS.userbuf:' . $base64_userbuf . "\n";
        }
        return base64_encode( hash_hmac( 'sha256', $content_to_be_signed, $this->key, true ) );
    }

    private function __genSig( $identifier, $expire, $userbuf, $userbuf_enabled ) {
        $curr_time = time();
        $sig_array = Array(
            'TLS.ver' => '2.0',
            'TLS.identifier' => strval( $identifier ),
            'TLS.sdkappid' => intval( $this->sdkappid ),
            'TLS.expire' => intval( $expire ),
            'TLS.time' => intval( $curr_time )
        );

        $base64_userbuf = '';
        if ( true == $userbuf_enabled ) {
            $base64_userbuf = base64_encode( $userbuf );
            $sig_array['TLS.userbuf'] = strval( $base64_userbuf );
        }

        $sig_array['TLS.sig'] = $this->hmacsha256( $identifier, $curr_time, $expire, $base64_userbuf, $userbuf_enabled );
        if ( $sig_array['TLS.sig'] === false ) {
            throw new \Exception( 'base64_encode error' );
        }
        $json_str_sig = json_encode( $sig_array );
        if ( $json_str_sig === false ) {
            throw new \Exception( 'json_encode error' );
        }
        $compressed = gzcompress( $json_str_sig );
        if ( $compressed === false ) {
            throw new \Exception( 'gzcompress error' );
        }
        return $this->base64_url_encode( $compressed );
    }
    private function __verifySig( $sig, $identifier, &$init_time, &$expire_time, &$userbuf, &$error_msg ) {
        try {
            $error_msg = '';
            $compressed_sig = $this->base64_url_decode( $sig );
            $pre_level = error_reporting( E_ERROR );
            $uncompressed_sig = gzuncompress( $compressed_sig );
            error_reporting( $pre_level );
            if ( $uncompressed_sig === false ) {
                throw new \Exception( 'gzuncompress error' );
            }
            $sig_doc = json_decode( $uncompressed_sig );
            if ( $sig_doc == false ) {
                throw new \Exception( 'json_decode error' );
            }
            $sig_doc = ( array )$sig_doc;
            if ( $sig_doc['TLS.identifier'] !== $identifier ) {
                throw new \Exception( "identifier dosen't match" );
            }
            if ( $sig_doc['TLS.sdkappid'] != $this->sdkappid ) {
                throw new \Exception( "sdkappid dosen't match" );
            }
            $sig = $sig_doc['TLS.sig'];
            if ( $sig == false ) {
                throw new \Exception( 'sig field is missing' );
            }

            $init_time = $sig_doc['TLS.time'];
            $expire_time = $sig_doc['TLS.expire'];

            $curr_time = time();
            if ( $curr_time > $init_time+$expire_time ) {
                throw new \Exception( 'sig expired' );
            }

            $userbuf_enabled = false;
            $base64_userbuf = '';
            if ( isset( $sig_doc['TLS.userbuf'] ) ) {
                $base64_userbuf = $sig_doc['TLS.userbuf'];
                $userbuf = base64_decode( $base64_userbuf );
                $userbuf_enabled = true;
            }
            $sigCalculated = $this->hmacsha256( $identifier, $init_time, $expire_time, $base64_userbuf, $userbuf_enabled );

            if ( $sig != $sigCalculated ) {
                throw new \Exception( 'verify failed' );
            }

            return true;
        } catch ( \Exception $ex ) {
            $error_msg = $ex->getMessage();
            return false;
        }
    }

    public function verifySig( $sig, $identifier, &$init_time, &$expire_time, &$error_msg ) {
        $userbuf = '';
        return $this->__verifySig( $sig, $identifier, $init_time, $expire_time, $userbuf, $error_msg );
    }

    public function verifySigWithUserBuf( $sig, $identifier, &$init_time, &$expire_time, &$userbuf, &$error_msg ) {
        return $this->__verifySig( $sig, $identifier, $init_time, $expire_time, $userbuf, $error_msg );
    }
    
    
}