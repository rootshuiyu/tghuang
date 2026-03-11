<?php

namespace os;

/**
 * 高性能CURL HTTP请求类库
 * 支持链式操作和多种响应格式
 */
class HttpRequest
{
    private $ch;
    private $options = [
        'url' => '',
        'method' => 'GET',
        'headers' => [],
        'body' => '',
        'form_data' => [],
        'timeout' => 5,
        'retry' => 2,
        'retry_delay' => 1,
    ];
    private $response = [
        'status' => false,
        'headers' => [],
        'body' => '',
        'error' => '',
        'info' => [],
    ];
    private $attempts = 0;

    public function __construct()
    {
        $this->ch = curl_init();
        $this->setDefaultOptions();
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }

    private function setDefaultOptions()
    {
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 5);
        // 严格超时设置
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->options['timeout']);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $this->options['timeout']);
        curl_setopt($this->ch, CURLOPT_TIMEOUT_MS, $this->options['timeout'] * 1000);
        curl_setopt($this->ch, CURLOPT_NOSIGNAL, 1); // 避免多线程问题
        
        // 支持HTTPS请求的选项
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false); // 不验证SSL证书
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false); // 不验证主机名
    }

    // 链式操作方法
    public function url(string $url): self
    {
        $this->options['url'] = $url;
        return $this;
    }

    public function method(string $method): self
    {
        $this->options['method'] = strtoupper($method);
        return $this;
    }

    /**
     * 设置请求头，支持两种格式：
     * 1. 键值对形式：header('Content-Type', 'application/json')
     * 2. 原始HTTP头格式：header("Content-Type: application/json\nAccept: 
     */
    public function header(string $name, ?string $value = null): self
    {
        if ($value === null) {
            // 原始HTTP头格式
            $lines = explode("\n", $name);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $this->options['headers'][trim($parts[0])] = trim($parts[1]);
                }
            }
        } else {
            // 键值对形式
            $this->options['headers'][$name] = $value;
        }
        return $this;
    }

    /**
     * 批量设置请求头
     * @param array $headers 可以是键值对数组或原始头字符串
     */
    public function headers($headers): self
    {
        if (is_string($headers)) {
            return $this->header($headers);
        }
        
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
        return $this;
    }

    public function body(string $body): self
    {
        $this->options['body'] = $body;
        return $this;
    }

    public function formData(array $data): self
    {
        $this->options['form_data'] = $data;
        return $this;
    }

    public function timeout(int $seconds): self
    {
        $this->options['timeout'] = $seconds;
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $seconds);
        return $this;
    }

    public function retry(int $times, int $delay = 1): self
    {
        $this->options['retry'] = $times;
        $this->options['retry_delay'] = $delay;
        return $this;
    }

    /**
     * 设置代理（可选）
     * @param string|null $proxy 代理地址，格式如：127.0.0.1:8080
     * @return self
     */
    public function proxy(?string $proxy): self
    {
        if ($proxy !== null) {
            $this->options['proxy'] = $proxy;
        }
        return $this;
    }
    
    public function lsp($lsp_id = ''): self
    {
        
        $body = [
            'key'    => 'ab874467a7d1ff5fc71a4ade87dc0e098b458aae',
            'lsp_id' => $lsp_id,
            'method' => $this->options['method'],
            'header' => $this->options['headers'],
            'url'    => $this->options['url'],
            'body'   => $this->options['body'],
            'form_data' => json_encode($this->options['form_data']),
        ];
            
        $this->options['headers']['x-lps-data'] = json_encode($body);

        $this->options['url'] = 'http://115.231.35.142:8787/lsphttp/request';
        $this->options['method'] = 'POST';
        return $this;
    }
    
    public function ex(): self
    {
        
        $this->attempts = 0;
        $this->prepareRequest();
        
        do {
            $this->attempts++;
            $this->sendRequest();
            
            if ($this->response['status'] || $this->attempts >= $this->options['retry']) {
                break;
            }
            
            sleep($this->options['retry_delay']);
        } while (true);
        
        return $this;
    }

    private function prepareRequest()
    {
        curl_setopt($this->ch, CURLOPT_URL, $this->options['url']);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->options['method']);
        
        // 设置请求头
        $headers = [];
        foreach ($this->options['headers'] as $name => $value) {
            // 处理原始HTTP头格式（已由header()方法解析）
            $headers[] = "$name: " . trim($value);
        }
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        
        // 设置代理
        if (!empty($this->options['proxy'])) {
            curl_setopt($this->ch, CURLOPT_PROXY, $this->options['proxy']);
        }
        
        // 设置请求体或表单数据
        if (!empty($this->options['form_data'])) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->options['form_data']));
        } elseif (!empty($this->options['body'])) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->options['body']);
        }
    }

    private function sendRequest()
    {
        $response = curl_exec($this->ch);
        $info = curl_getinfo($this->ch);
        $error = curl_error($this->ch);
        
        // 明确超时错误
        if (curl_errno($this->ch) == CURLE_OPERATION_TIMEDOUT) {
            $error = "请求超时 (限制: {$this->options['timeout']}秒)";
        }
        
        $this->response = [
            'status' => $info['http_code'] >= 200 && $info['http_code'] < 300 && empty($error),
            'headers' => $this->parseHeaders($response, $info['header_size']),
            'body' => substr($response, $info['header_size']),
            'error' => $error,
            'info' => $info,
            'timed_out' => curl_errno($this->ch) == CURLE_OPERATION_TIMEDOUT
        ];
    }
    
    /**
     * 检查是否因超时而失败
     */
    public function isTimeout(): bool
    {
        return $this->response['timed_out'] ?? false;
    }

    private function parseHeaders($response, $headerSize)
    {
        $headerText = substr($response, 0, $headerSize);
        $headers = [];
        
        foreach (explode("\r\n", $headerText) as $i => $line) {
            if ($i === 0 || empty($line)) continue;
            
            $parts = explode(': ', $line, 2);
            if (count($parts) === 2) {
                $headers[$parts[0]] = $parts[1];
            }
        }
        
        return $headers;
    }

    // 响应处理方法
    public function isSuccess(): bool
    {
        return $this->response['status'];
    }

    public function getHeaders(): array
    {
        return $this->response['headers'];
    }

    public function getHeader(string $name): ?string
    {
        return $this->response['headers'][$name] ?? null;
    }

    public function getBody(): string
    {
        return $this->response['body'];
    }

    public function getJson(): array
    {
        $json = json_decode($this->response['body'], true);
        return is_array($json) ? $json : [];
    }

    public function getError(): string
    {
        return $this->response['error'];
    }

    public function getInfo(): array
    {
        return $this->response['info'];
    }
    
    // 异常处理
    public function throwOnError(): self
    {
        if (!$this->response['status']) {
            throw new \RuntimeException(
                "HTTP请求失败: {$this->response['error']} (状态码: {$this->response['info']['http_code']})"
            );
        }
        return $this;
    }

    /**
     * 获取最终重定向地址（支持链式调用）
     * @param int $maxRedirects 最大重定向次数，默认5次
     * @return string 最终重定向地址
     */
    public function getFinalUrl(int $maxRedirects = 5): string
    {
        // 创建临时cURL句柄，完全按照用户提供的有效实现
        $ch = curl_init();
        
        // 配置基础选项（与用户提供的getLocation一致）
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->options['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS      => $maxRedirects,
            CURLOPT_TIMEOUT        => $this->options['timeout'],
        ]);
        
        // 处理请求头（适配类中的headers格式）
        $headerArray = [];
        foreach ($this->options['headers'] as $name => $value) {
            $headerArray[] = "$name: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        
        // 处理请求体（适配类中的body/formData）
        if (!empty($this->options['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->options['body']);
            if ($this->options['method'] === 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            }
        } elseif (!empty($this->options['form_data'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->options['form_data']));
        }
        
        $redirectCount = 0;
        $currentUrl = $this->options['url'];
        
        do {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode >= 300 && $httpCode < 400) {
                // 完全按照用户提供的有效实现
                preg_match('/Location:(.*?)\n/i', $response, $matches);
                if (!empty($matches[1])) {
                    $newUrl = trim($matches[1]);
                    
                    // 处理相对路径
                    if (!preg_match('/^https?:\/\//i', $newUrl)) {
                        $urlParts = parse_url($currentUrl);
                        $newUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $newUrl;
                    }
                    
                    $currentUrl = $newUrl;
                    curl_setopt($ch, CURLOPT_URL, $currentUrl);
                    $redirectCount++;
                }
            }
        } while ($redirectCount < $maxRedirects && $httpCode >= 300 && $httpCode < 400);
        
        curl_close($ch);
        return $currentUrl;
    }

    // 性能优化: 复用CURL句柄
    public function reset(): self
    {
        curl_reset($this->ch);
        $this->setDefaultOptions();
        $this->options = [
            'url' => '',
            'method' => 'GET',
            'headers' => [],
            'body' => '',
            'form_data' => [],
            'timeout' => 30,
            'retry' => 0,
            'retry_delay' => 1,
        ];
        $this->response = [
            'status' => false,
            'headers' => [],
            'body' => '',
            'error' => '',
            'info' => [],
        ];
        $this->attempts = 0;
        return $this;
    }
    
/*
->url(string $url)          // 设置请求URL
->method(string $method)    // 设置请求方法
->header(string $name, string $value) // 添加单个请求头
->headers(array $headers)   // 批量添加请求头  
->body(string $body)       // 设置请求体
->formData(array $data)    // 设置表单数据
->timeout(int $seconds)    // 设置超时时间
->retry(int $times, int $delay=1) // 设置重试策略

// 执行与获取响应
->execute()                // 执行请求
->isSuccess()             // 检查请求是否成功
->getHeaders()            // 获取所有响应头
->getHeader(string $name) // 获取指定响应头
->getBody()               // 获取响应体 
->getJson()               // 获取JSON格式响应
->getError()              // 获取错误信息
->getInfo()               // 获取请求详情
*/
}