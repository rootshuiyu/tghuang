<?php
/**
 * PHP代理服务器 - 支持修改源站响应体
 * 作者: PHP顶级程序员
 * 版本: 1.0
 * 日期: " . date('Y-m-d') . "
 */

// 确保PHP错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 设置脚本运行时间无限制
set_time_limit(0);

/**
 * 代理服务器类
 */
class ProxyServer {
    /**
     * 运行代理服务器
     */
    public function run() {
        // 获取客户端请求的URL
        $targetUrl = $this->getTargetUrl();
        
        if (!$targetUrl) {
            $this->sendErrorResponse(400, '缺少目标URL参数');
            return;
        }
        
        // 获取客户端请求的方法
        $method = $_SERVER['REQUEST_METHOD'];
        
        // 获取客户端请求头
        $headers = $this->getClientHeaders();
        
        // 获取客户端请求体
        $body = $this->getRequestBody();
        
        // 发送请求到目标服务器
        $response = $this->sendRequest($targetUrl, $method, $headers, $body);
        
        // 处理响应
        $this->processResponse($response);
    }
    
    /**
     * 获取目标URL
     * @return string|null 目标URL
     */
    private function getTargetUrl() {
        // 从GET参数中获取URL，或者从请求头中获取
        if (isset($_GET['url'])) {
            return $_GET['url'];
        }
        
        return null;
    }
    
    /**
     * 获取客户端请求头
     * @return array 请求头数组
     */
    private function getClientHeaders() {
        $headers = [];
        
        // 获取所有请求头
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                // 将HTTP_KEY转换为Key格式
                $headerKey = str_replace('_', '-', substr($key, 5));
                $headers[$headerKey] = $value;
            }
        }
        
        // 添加或修改一些必要的头信息
        $headers['Host'] = parse_url($this->getTargetUrl(), PHP_URL_HOST);
        $headers['X-Forwarded-For'] = $_SERVER['REMOTE_ADDR'];
        
        return $headers;
    }
    
    /**
     * 获取请求体
     * @return string 请求体内容
     */
    private function getRequestBody() {
        return file_get_contents('php://input');
    }
    
    /**
     * 发送请求到目标服务器
     * @param string $url 目标URL
     * @param string $method 请求方法
     * @param array $headers 请求头
     * @param string $body 请求体
     * @return array 响应数组
     */
    private function sendRequest($url, $method, $headers, $body) {
        $curl = curl_init();
        
        // 设置curl选项
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HEADER, true); // 包含响应头
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 不验证SSL证书
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        
        // 设置请求方法
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        
        // 设置请求头
        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = "$key: $value";
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curlHeaders);
        
        // 设置请求体
        if (!empty($body)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }
        
        // 执行请求
        $response = curl_exec($curl);
        
        // 获取响应状态码
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        // 获取响应头大小
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        
        // 关闭curl
        curl_close($curl);
        
        // 分离响应头和响应体
        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);
        
        return [
            'statusCode' => $statusCode,
            'headers' => $responseHeaders,
            'body' => $responseBody
        ];
    }
    
    /**
     * 处理响应
     * @param array $response 响应数组
     */
    private function processResponse($response) {
        // 解析响应头
        $headerLines = explode("\r\n", $response['headers']);
        
        // 发送状态码
        http_response_code($response['statusCode']);
        
        // 发送响应头（跳过第一行状态行）
        for ($i = 1; $i < count($headerLines); $i++) {
            $headerLine = trim($headerLines[$i]);
            if (!empty($headerLine)) {
                header($headerLine, false);
            }
        }
        
        // 修改响应体
        $modifiedBody = $this->modifyResponseBody($response['body']);
        
        // 发送修改后的响应体
        echo $modifiedBody;
    }
    
    /**
     * 修改响应体
     * @param string $body 原始响应体
     * @return string 修改后的响应体
     */
    private function modifyResponseBody($body) {
        // 这里是修改响应体的逻辑，可以根据需要自定义
        // 示例：在HTML内容中添加一个注释
        if (strpos($body, '<html') !== false || strpos($body, '<HTML') !== false) {
            $body = str_replace('</body>', '<!-- 代理服务器修改 -->\n</body>', $body);
        }
        
        // 示例：替换特定文本
        // $body = str_replace('原始文本', '替换后的文本', $body);
        
        return $body;
    }
    
    /**
     * 发送错误响应
     * @param int $statusCode 状态码
     * @param string $message 错误消息
     */
    private function sendErrorResponse($statusCode, $message) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => $message,
            'status' => $statusCode
        ]);
    }
}

// 运行代理服务器
$proxyServer = new ProxyServer();
$proxyServer->run();