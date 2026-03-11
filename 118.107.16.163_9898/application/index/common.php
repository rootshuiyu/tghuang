<?php
function getOs(){
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    
        if(preg_match('/windows/i',$agent)){
            $os = 'Windows';
        }else if(preg_match('/iphone/i',$agent)) {
            $os = 'IOS';
        }else if(preg_match('/ipad/i',$agent)) {
            $os = 'ipad';
        }else if(preg_match('/android/i',$agent)) {
            $os = 'Android';
        }else{
            $os = '未知';
        }
        
    return $os;  
}

function t($field = null){
    
    $body = [
        'y' => date('Y'),
        'm' => date('m'),
        'd' => date('d'),
        'h' => date('h'),
        'i' => date('i'),
        's' => date('s'),
        'f' => date('f'),
    ];
    if(date('s') > 30){
      $body['f'] = 60 - date('s');  
    }
    
    if($field){
        return $body[$field];
    }
    return $body;
    
}

function getFirstSixCombinedDigits($string) {
    // 使用正则表达式匹配所有连续的数字序列
    if (preg_match_all('/\d+/', $string, $matches)) {
        // 将匹配到的数字序列连接起来
        $combinedDigits = implode('', $matches[0]);
        // 返回连接后的数字序列的前六位（如果不足六位则返回全部）
        return substr($combinedDigits, 0, 6);
    } else {
        // 如果没有找到任何数字，返回false或根据需要返回其他值
        return false;
    }
}