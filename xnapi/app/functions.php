<?php
/**
 * Here is your custom functions.
 */
 
 if(!function_exists('queryck')){
    function queryck($query,$key = null){
        if(!$query){
            return [];
        }
        $query = str_replace(';','&',$query);
        parse_str($query, $params);
        if($key){
            return isset($params[$key]) ? $params[$key] : '';
        }
        return $params;
        
    }
 }


function sconfig($key)
{
        $model = new \app\api\model\Config;
        $result = $model->where(['name' => $key])->find();
        return $result['value'];
}

if (!function_exists('http_post')) {
    /**
     * HTTP_POST请求
     */
    function http_post($params)
    {

        $params['type']   = isset($params['type']) ? $params['type'] : '';
        $params['body']   = isset($params['body']) ? $params['body'] : '';
       
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $params['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(isset($params['header'])){
            $params['header'] = explode("\n",$params['header']);
            curl_setopt($ch, CURLOPT_HTTPHEADER,$params['header']); //设置头信息的地方
        }
        
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);  //超时时间
        //curl_setopt($ch, CURLOPT_INTERFACE, get_rand_ip());
        
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params['body']);
        $output = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        
        if($params['type'] =='json'){
           $output = json_decode($output,true);
        }
        
        return $output;
        
    }
    
}

if (!function_exists('get_rand_ip')) {
function get_rand_ip() {
    $ip_long = array(
        array('607649792', '608174079'), // 36.56.0.0-36.63.255.255
        array('1038614528', '1039007743'), // 61.232.0.0-61.237.255.255
        array('1783627776', '1784676351'), // 106.80.0.0-106.95.255.255
        array('2035023872', '2035154943'), // 121.76.0.0-121.77.255.255
        array('2078801920', '2079064063'), // 123.232.0.0-123.235.255.255
        array('-1950089216', '-1948778497'), // 139.196.0.0-139.215.255.255
        array('-1425539072', '-1425014785'), // 171.8.0.0-171.15.255.255
        array('-1236271104', '-1235419137'), // 182.80.0.0-182.92.255.255
        array('-770113536', '-768606209'), // 210.25.0.0-210.47.255.255
        array('-569376768', '-564133889'), // 222.16.0.0-222.95.255.255
        );
    $rand_key = mt_rand(0, 9);
    $ip = long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
    print_r($ip);
    return $ip;
}
}

if (!function_exists('http_get')) {
    /**
     * HTTP_GET请求
     */
    function http_get($params)
    {
        
        $params['body'] = $params['body'] ?? '';
        if (is_array($params['body'])) {
            $params['url'] = $params['url'] . '?' . http_build_query($params['body']);
        }
        $params['type']   = $params['type'] ?? '';
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $params['url']);
        
        if(isset($params['header'])){
            $params['header'] = $params['header'] ?? '';
            $params['header'] = explode("\n",$params['header']);
            curl_setopt($ch, CURLOPT_HTTPHEADER,$params['header']); //设置头信息的地方
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);  //超时时间
        // post的变量
       
        $output = curl_exec($ch);
        
        curl_close($ch);
        //打印获得的数据
        if($params['type'] =='json'){
           $output =  json_decode($output,true);
        }
        
        return $output;
        
    }
    
}

if (!function_exists('http_get_d')) {
    /**
     * HTTP_GET请求
     */
    function http_get_d($params,$lsp_id = '')
    {
        $params['method'] = 'GET';
        $params['lsp_id'] = $lsp_id;

        $http = http_post([
                'url'    => 'http://192.140.161.107:8787/lsphttp/request?key=ab874467a7d1ff5fc71a4ade87dc0e098b458aae',
                'body'   => $params,
            ]);
        return $http;
        
    }
    
}

if (!function_exists('http_post_d')) {
    /**
     * HTTP_GET请求
     */
    function http_post_d($params,$lsp_id = '')
    {
        $params['method'] = 'POST';
        $params['lsp_id'] = $lsp_id;
        if(isset($params['body']) && is_array($params['body'])){
            $params['body'] = json_encode($params['body'],256);
        }
        $http = http_post([
                'url'    => 'http://192.140.161.107:8787/lsphttp/request?key=ab874467a7d1ff5fc71a4ade87dc0e098b458aae',
                'body'   => $params,
            ]);
        return $http;
        
    }
    
}

if (!function_exists('rendMsgtpl')) {
    function rendMsgtpl($tpl,$query){
        foreach ($query as $k => $v){
            $tpl = str_replace('{'.$k.'}',$v,$tpl);
        }
        
        return $tpl;
        
    }
}

if (!function_exists('generateOrderId')) {
function generateOrderId() {
    $base = uniqid(); // 生成一个基于微秒的唯一ID，默认为13位
    $randomPart = mt_rand(1000, 9999); // 生成一个4位的随机数
    return $base . $randomPart; // 拼接，总共17位
}
}

if(!function_exists('queryStr')){
    function queryStr($input, $name = null) {
        // 判断输入是否为纯查询字符串（含?）或完整URL
        $query = (strpos($input, '=') !== false && strpos($input, '?') === false) 
            ? $input 
            : parse_url($input, PHP_URL_QUERY);
     
        // 处理空值情况
        $query = $query ?? '';
        parse_str($query, $params);
     
        return $name !== null 
            ? ($params[$name] ?? null) 
            : $params;
    }
}
 
 
function toArray($input): array
{
    // 第一步：类型检查（仅处理字符串类型）
    if (!is_string($input)) {
        return [];
    }
 
    // 第二步：空值检测（包含纯空白字符串）
    $trimmed = trim($input);
    if ($trimmed === '') {
        return [];
    }
 
    // 第三步：JSON解析（启用关联数组模式）
    $result = json_decode($trimmed, true);
 
    // 第四步：错误检测（包含JSON语法错误和深度限制）
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }
 
    // 第五步：类型验证（确保返回的是数组类型）
    return is_array($result) ? $result : [];
}

/**
 * 获取最终重定向地址（最多跟踪4次）
 * @param string $url 请求地址
 * @param string $headers 请求头（每行一个header）
 * @param string|null $body 请求体（可选）
 * @return string 最终重定向地址
 */
function getLocation($url, $headers = [], $body = null) {
    $ch = curl_init();
    $max_redirects = 4;
    
    // 配置基础选项
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,    // 获取响应头
        CURLOPT_NOBODY         => true,    // 不获取响应体
        CURLOPT_FOLLOWLOCATION => false,   // 禁用自动重定向
        CURLOPT_MAXREDIRS      => $max_redirects,
        CURLOPT_TIMEOUT        => 10,
    ]);

    // 处理请求头
    $headerArray = array_filter(array_map('trim', explode("\n", $headers)));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);

    // 处理GET请求带body的特殊情况
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    }

    $redirect_count = 0;
    $current_url = $url;

    do {
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // 如果是重定向响应
        if ($http_code >= 300 && $http_code < 400) {
            // 从响应头获取Location
            preg_match('/Location:(.*?)\n/i', $response, $matches);
            if (!empty($matches[1])) {
                $new_url = trim($matches[1]);
                
                // 处理相对路径
                if (!preg_match('/^https?:\/\//i', $new_url)) {
                    $url_parts = parse_url($current_url);
                    $new_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $new_url;
                }
                
                $current_url = $new_url;
                curl_setopt($ch, CURLOPT_URL, $current_url);
                $redirect_count++;
            }
        }
    } while ($redirect_count < $max_redirects && $http_code >= 300 && $http_code < 400);

    curl_close($ch);
    return $current_url;
}

function qrcodeValue($str = null) {

    $result = strstr($str, 'value=');
     
    if ($result !== false) {
        return substr($result, strlen('value=')); // 输出：需要提取的内容
    }
    
    return null;
    
}

function getMetaContent($html) {
    // 创建 DOM 对象
    $dom = new DOMDocument();
    
    // 抑制可能出现的警告（如 HTML 不完整）
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    
    // 获取所有 meta 标签
    $metas = $dom->getElementsByTagName('meta');
    
    // 遍历查找特定 meta 标签
    foreach ($metas as $meta) {
        // 检查 name 属性是否匹配（不区分大小写）
        if (strtolower($meta->getAttribute('name')) === 'unio_origin_url') {
            return $meta->getAttribute('content');//trim(); // 返回 content 值
        }
    }
    
    return ''; // 未找到时返回空字符串
}

function detectDevice($agent) {
    
    $ua = strtolower($agent);
    
    // 移动设备优先匹配
    if (preg_match('/iphone|ipod/', $ua)) {
        return 'iPhone';
    } elseif (preg_match('/ipad/', $ua)) {
        return 'iPad';
    } elseif (preg_match('/android/', $ua)) {
        // 安卓设备需要区分手机和平板
        if (preg_match('/mobile/', $ua)) {
            return 'Android';
        } else {
            return 'Android Pad';
        }
    }

    // Windows系统匹配
    if (preg_match('/windows nt (\d+\.\d+)/', $ua, $matches)) {
        $version = $matches[1];
        // 版本映射表
        $winVersions = [
            '10.0' => 'Windows 10',
            '6.3' => 'Windows 8.1',
            '6.2' => 'Windows 8',
            '6.1' => 'Windows 7',
            '6.0' => 'Windows Vista',
            '5.1' => 'Windows XP',
            '5.0' => 'Windows 2000'
        ];
        return isset($winVersions[$version]) ? $winVersions[$version] : 'Windows';
    }
    
    // Mac系统匹配
    if (preg_match('/mac os x/', $ua)) {
        return 'Mac';
    }

    // 默认返回未知设备
    return 'Unknown';
}

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

function userAgent() {
    $chromeVersions = [
        '116.0.5845.97', '117.0.5938.62', '118.0.5993.70', '119.0.6045.105',
        '120.0.6099.62', '121.0.6167.85', '122.0.6261.57', '123.0.6312.86',
        '124.0.6367.60', '125.0.6422.60', '126.0.6478.57', '127.0.6533.72',
        '128.0.6613.84', '129.0.6668.58', '130.0.6723.91', '131.0.6778.86',
        '132.0.6834.83'
    ];

    $systemVersions = [
        'Windows NT 10.0; Win64; x64',
        'Windows NT 10.0; Win64; x64',
        'Windows NT 10.0; Win64; x64',
        'Windows NT 10.0; Win64; x64',
        'Windows NT 11.0; Win64; x64',
        'Windows NT 11.0; Win64; x64',
        'Windows NT 6.1; Win64; x64',
    ];

    $browsers = [
        'Chrome' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36'
        ],
        'Edge' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36 Edg/%s',
            'use_chrome_version' => true
        ],
        'Sogou' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36 SE 2.X MetaSr %s',
            'features' => ['1.0', '1.1', '1.2', '2.0', '3.0', '1.0 Beta']
        ],
        '360' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36 QIHU %s',
            'variants' => ['360SE', '360EE']
        ],
        'QQBrowser' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36 QQBrowser/%s',
            'versions' => ['10.0.0', '11.0.0', '12.0.0', '13.0.0']
        ],
        'Liebao' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36 LBBROWSER'
        ],
        'UCBrowser' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36 UCBrowser/%s',
            'versions' => ['7.0.185', '7.2.195', '7.3.200', '8.0.225']
        ],
        'Baidu' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36 BIDUBrowser/%s',
            'versions' => ['8.8', '9.0', '9.5', '10.0']
        ],
        '2345Browser' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36 2345Explorer/%s',
            'versions' => ['9.0.0', '9.5.0', '10.0.0', '10.5.0']
        ],
        'CentBrowser' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36 CentBrowser/%s',
            'versions' => ['4.0', '4.1', '4.2', '4.3']
        ],
        'Opera' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36 OPR/%s',
            'versions' => ['105.0.0', '106.0.0', '107.0.0', '108.0.0']
        ],
        'Brave' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36 Brave/%s',
            'versions' => ['1.0.0', '1.5.0', '1.10.0', '1.20.0']
        ],
        'Vivaldi' => [
            'template' => 'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36 Vivaldi/%s',
            'versions' => ['5.0.0', '5.5.0', '6.0.0', '6.5.0']
        ],
        'Firefox' => [
            'template' => 'Mozilla/5.0 (%s; rv:%s) Gecko/20100101 Firefox/%s',
            'versions' => ['116.0', '117.0', '118.0', '119.0', '120.0', '121.0', '122.0', '123.0', '124.0', '125.0'],
            'is_firefox' => true
        ]
    ];

    // 随机选择
    $browserNames = array_keys($browsers);
    $browserName = $browserNames[array_rand($browserNames)];
    $browser = $browsers[$browserName];
    $system = $systemVersions[array_rand($systemVersions)];

    // 构建User-Agent
    if (isset($browser['is_firefox'])) {
        $version = $browser['versions'][array_rand($browser['versions'])];
        $userAgent = sprintf($browser['template'], $system, $version, $version);
        $displayVersion = (int)explode('.', $version)[0];
    } else {
        $chromeVersion = $chromeVersions[array_rand($chromeVersions)];
        $displayVersion = (int)explode('.', $chromeVersion)[0];
        $args = [$system, $chromeVersion];

        if (isset($browser['features'])) {
            $args[] = $browser['features'][array_rand($browser['features'])];
            $userAgent = sprintf($browser['template'], ...$args);
        } elseif (isset($browser['variants'])) {
            $variant = $browser['variants'][array_rand($browser['variants'])];
            $args[] = $variant;
            $userAgent = sprintf($browser['template'], ...$args);
        } elseif (isset($browser['versions'])) {
            $args[] = $browser['versions'][array_rand($browser['versions'])];
            $userAgent = sprintf($browser['template'], ...$args);
        } elseif (isset($browser['use_chrome_version'])) {
            $args[] = $chromeVersion;
            $userAgent = sprintf($browser['template'], ...$args);
        } else {
            $userAgent = sprintf($browser['template'], ...$args);
        }
    }

    return [
        'browser'   => $browserName,
        'system'    => $system,
        'v'         => $displayVersion,
        'u'         => $userAgent
    ];
}

