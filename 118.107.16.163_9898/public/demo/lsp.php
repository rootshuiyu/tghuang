<?php
// lps.php - 代理转发脚本

// 设置错误报告级别
error_reporting(E_ALL);
ini_set('display_errors', 0); // 生产环境应该关闭错误显示
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/proxy_errors.log');

// 设置超时时间（秒）
define('PROXY_TIMEOUT', 30);

// 允许的HTTP方法
$allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS', 'PATCH'];

try {
    // 从REQUEST_URI中获取完整的url参数
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // 使用parse_url和parse_str来安全地提取url参数
    $query = [];
    $urlParts = parse_url($requestUri);
    
    // 检查是否有URL参数
    if (!isset($_GET['url']) || empty($_GET['url'])) {
        throw new Exception('Missing target URL parameter');
    }
    
    $start = strpos($requestUri, 'url=') + 4; // 跳过 "url="
    $extracted_url = substr($requestUri, $start);
   
    $targetUrl = $extracted_url;
    //print_r($targetUrl);die;
    
    // 验证URL格式
    if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid target URL');
    }

    // 防止开放重定向漏洞 - 只允许http/https
    if (!preg_match('/^https?:\/\//i', $targetUrl)) {
        throw new Exception('Only HTTP/HTTPS URLs are allowed');
    }

    // 获取请求方法
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    
    // 检查是否允许的HTTP方法
    if (!in_array($method, $allowedMethods)) {
        throw new Exception('HTTP method not allowed');
    }

    // 初始化cURL
    $ch = curl_init();
    if (!$ch) {
        throw new Exception('Failed to initialize cURL');
    }

    // 设置cURL选项
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // 不跟随重定向，由客户端处理
    curl_setopt($ch, CURLOPT_TIMEOUT, PROXY_TIMEOUT);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, PROXY_TIMEOUT);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 生产环境应该验证SSL证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    // 处理请求头
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) === 'HTTP_') {
            $headerKey = strtolower(substr($key, 5));
            $headerKey = str_replace('_', '-', $headerKey);
            // 跳过一些不应该转发的头
            if (in_array($headerKey, ['host', 'x-forwarded-for', 'x-forwarded-proto', 'x-forwarded-port'])) {
                continue;
            }
            $headers[$headerKey] = $value;
        }
    }

    // 添加自定义头
    //$headers['X-Proxy-By'] = 'PHP-Proxy/1.0';
    
    // 设置请求头
    $curlHeaders = [];
    foreach ($headers as $key => $value) {
        $curlHeaders[] = "$key: $value";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

    // 处理请求体（POST/PUT等）
    $requestBody = null;
    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $requestBody = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
    }

    // 执行请求
    $response = curl_exec($ch);
    
    // 检查错误
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch), curl_errno($ch));
    }

    // 获取响应信息
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $responseHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // 关闭cURL资源
    curl_close($ch);

    // 处理响应头
    $responseHeaders = preg_split('/\r\n|\n|\r/', $responseHeaders);
    $outputHeaders = [];
    foreach ($responseHeaders as $header) {
        // 跳过一些不应该转发的头
        if (preg_match('/^(Transfer-Encoding|Connection|Keep-Alive|Proxy-Authenticate|Proxy-Authorization|TE|Trailers|Upgrade):/i', $header)) {
            continue;
        }
        $outputHeaders[] = $header;
    }

    // 发送响应头
    foreach ($outputHeaders as $header) {
        if (!empty($header)) {
            header($header);
        }
    }

    // 设置HTTP状态码
    http_response_code($httpCode);

    // 输出响应体
    echo $responseBody;

} catch (Exception $e) {
    // 错误处理
    $errorCode = $e->getCode() ?: 500;
    http_response_code($errorCode);
    
    $errorResponse = [
        'error' => [
            'code' => $errorCode,
            'message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s'),
            'requestId' => uniqid(),
        ]
    ];
    
    // 如果是开发环境，可以显示更多信息
    if (ini_get('display_errors')) {
        $errorResponse['error']['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($errorResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    // 记录错误日志
    error_log(sprintf(
        "[%s] Proxy Error (%d): %s in %s on line %d\nRequest: %s %s",
        date('Y-m-d H:i:s'),
        $errorCode,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $_SERVER['REQUEST_METHOD'],
        $_SERVER['REQUEST_URI']
    ));
}