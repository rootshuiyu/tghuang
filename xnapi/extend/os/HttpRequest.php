<?php

namespace os;
use think\facade\Cache;
/**
 * 高性能CURL HTTP请求类库
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
        'lsp_id' => '',
        'ip_version' => null, // null=自动, 4=IPv4, 6=IPv6
        'dns_server' => null, // 自定义DNS服务器
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
        curl_setopt($this->ch, CURLOPT_DNS_CACHE_TIMEOUT, 60); //DNS缓存
        
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
    public function proxy($proxy): self
    {
        if ($proxy !== null) {
            $this->options['proxy'] = $proxy;
        }
        return $this;
    }

    /**
     * 设置IP版本（4=IPv4, 6=IPv6, null=自动）
     * @param int|null $version 4, 6 或 null
     * @return self
     */
    public function ipVersion(?int $version): self
    {
        $this->options['ip_version'] = in_array($version, [4, 6]) ? $version : null;
        return $this;
    }

    /**
     * 强制使用IPv4
     * @return self
     */
    public function useIPv4(): self
    {
        return $this->ipVersion(4);
    }

    /**
     * 强制使用IPv6
     * @return self
     */
    public function useIPv6(): self
    {
        return $this->ipVersion(6);
    }
    
    public function getIPVersion(): string
    {
        $info = $this->getInfo();
        $ip = $info['primary_ip'] ?? '';
        
        if (empty($ip)) {
            return 'unknown';
        }
        
        // IPv6包含冒号
        if (strpos($ip, ':') !== false) {
            return 'ipv6';
        }
        
        // IPv4格式
        if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $ip)) {
            return 'ipv4';
        }
        
        return 'unknown';
    }
    
    public function getRequestDetails(): array
    {
        $info = $this->getInfo();
        
        return [
            'success' => $this->isSuccess(),
            'ip_version' => $this->getIPVersion(),
            'primary_ip' => $info['primary_ip'] ?? '',
            'local_ip' => $info['local_ip'] ?? '',
            'primary_port' => $info['primary_port'] ?? '',
            'local_port' => $info['local_port'] ?? '',
            'total_time' => $info['total_time'] ?? 0,
            'http_code' => $info['http_code'] ?? 0,
            'scheme' => $info['scheme'] ?? '',
        ];
    }

    /**
     * 设置自定义DNS服务器
     * @param string $dnsServer DNS服务器地址，如：8.8.8.8 或 2001:4860:4860::8888
     * @return self
     */
    public function dnsServer(string $dnsServer): self
    {
        $this->options['dns_server'] = $dnsServer;
        return $this;
    }
    
    public function lsp_tps(): self
    {
        
        /*if(env('Dl_agent') == false){
            return $this;
        }*/
        $proxyServer = 'http://t102.juliangip.cc:17183';
        $proxyUser   = '17733717375';//账号
        $proxyPass   = 'hgaC3eQW';//密码
        $info = [
            'ip_port' => $proxyServer,
            'user'    => $proxyUser,
            'pass'    => $proxyPass,
        ];
        $this->options['proxy'] = $info;
        return $this;
    }
    
    public function lsp_jl($ip = null): self
    {
        $geo     = '';
        $ip_city = '';
        if($ip){
            $geo = (new \os\iplib\Ip2Region)->btreeSearch($ip);
            
            if (strpos($geo, '中国') !== false && strpos($geo, '香港') == false  && strpos($geo, '台湾') == false && strpos($geo, '澳门') == false ) {
                $cityinfo = explode('|',$geo);
                
                $ip_city = $cityinfo[2] != 0 ? $cityinfo[2] : $cityinfo[3];
                if($ip_city == 0){
                    $ip_city = '深圳';
                }

            }else{
                $ip_city = '深圳';

            }
            
            
        }
        
        $body = [
            'key'    => 'ab874467a7d1ff5fc71a4ade87dc0e098b458aae',
            'lsp_id'     => $ip,
            'lsp_city'   => $ip_city,
            'lsp_name'   => 'jl',
            'ipver'   => $this->options['ip_version'],
            'method' => $this->options['method'],
            'header' => $this->options['headers'],
            'url'    => $this->options['url'],
            'body'   => $this->options['body'],
            'form_data' => json_encode($this->options['form_data']),
        ];

        $this->options['headers']['x-lps-data'] = json_encode($body);
        $this->options['url'] = 'http://110.42.106.165:8787/lsphttp/request';
        $this->options['method'] = 'POST';
        return $this;
        
        
    }
    
    public function lsp_qg($ip = null): self
    {
        $geo = '';
        if($ip){
            $geo = (new \os\iplib\Ip2Region)->btreeSearch($ip);
            //$cityinfo = explode('|',$geo);
            //$ip_city = $cityinfo[2] ? $cityinfo[2] : $cityinfo[1];
            
        }
        
        $body = [
            'key'    => 'ab874467a7d1ff5fc71a4ade87dc0e098b458aae',
            'lsp_id'     => $ip,
            'lsp_city'   => $geo,
            'lsp_name'   => 'qg',
            'method' => $this->options['method'],
            'header' => $this->options['headers'],
            'url'    => $this->options['url'],
            'body'   => $this->options['body'],
            'form_data' => json_encode($this->options['form_data']),
        ];
            
        $this->options['headers']['x-lps-data'] = json_encode($body);
        $this->options['url'] = 'http://59.153.166.65:8787/lsphttp/request';
        $this->options['method'] = 'POST';
        return $this;
        
        return $this;
        $ipx = file_get_contents('https://share.proxy.qg.net/get?key=UDRYKEJH&pwd=EE5C49EE5D1F&num=1&area=140000&isp=0&format=txt&seq=\r\n&distinct=false');
        if($ipx){
            $this->options['proxy'] = [
                'ip_port' => $ipx,
                'user'    => 'UDRYKEJH',
                'pass'    => 'EE5C49EE5D1F',
            ];
        }
        
        return $this;
        
        
    }
    
    public function lsp_init(): self
    {
        
        $info = [
            'ip_port' => '47.101.32.121:11813',
            'user'    => 'vsb',
            'pass'    => 'yxg',
        ];
        $this->options['proxy'] = $info;
        
    }
    
    public function lsp($ip = null): self
    {
        $this->options['lsp_id'] = $ip;
        
        /*$ipcache = Cache::get('lsp_id'.$ip);
        if($ipcache){
            $this->options['proxy'] = $ipcache;
            return $this;
        }*/
        
        $query = '';
        if($ip){
            $ip2region = new \Ip2Region();
            $geo = $ip2region->memorySearch($ip)['region'];

            if (strpos($geo, '中国') !== false && strpos($geo, '香港') == false  && strpos($geo, '台湾') == false && strpos($geo, '澳门') == false ) {
                $cityinfo = explode('|',$geo);
                $ip_city = $cityinfo[3] ? $cityinfo[3] : $cityinfo[2];

            }else{
                $ip_city = '北京';

            }
            
            $query = '&area='. $ip_city;
        }
        
        $SecretId = 'ovnu0wws3t1yxp6ryy5n'; // 快代理平台(私密代理)API ID
        $SecretKey = 'n601wuw4qxgcg08rpis60pn10ihwh3oj';
        $proxy_ip = 'https://dps.kdlapi.com/api/getdps/?secret_id='.$SecretId.'&signature='.$SecretKey.'&num=1&format=text&sep=1&dedup=1'.$query;
        $ipx = $this->curl_api($proxy_ip);
        if($ipx){
            $info = [
                'ip_port' => $ipx,
                'user'    => 'd4141568104',
                'pass'    => 'dns38s67',
            ];
            $this->options['proxy'] = $info;
            //Cache::set('lsp_id'.$ip,$info,60);
        }
        return $this;
    }
    
    public function lsp_bilibili($ip = null): self
    {
        $this->options['lsp_id'] = $ip;
        
        /*$ipcache = Cache::get('lsp_id'.$ip);
        if($ipcache){
            $this->options['proxy'] = $ipcache;
            return $this;
        }*/
        
        $query = '';
        if($ip){
            $ip2region = new \Ip2Region();
            $geo = $ip2region->memorySearch($ip)['region'];
 
            if (strpos($geo, '中国') !== false && strpos($geo, '香港') == false  && strpos($geo, '台湾') == false && strpos($geo, '澳门') == false ) {
                $cityinfo = explode('|',$geo);
                $ip_city = $cityinfo[3] ? $cityinfo[3] : $cityinfo[2];

            }else{
                $ip_city = '中国';

            }

            $query = '&area='. $ip_city;
        }
        
        $SecretId = 'o3qwvpc1sp27d0x1qd7l'; // 快代理平台(私密代理)API ID
        $SecretKey = 'gew7ptpk3p6oieof2h2nz55sohcu13st';
        $proxy_ip = 'https://dps.kdlapi.com/api/getdps/?secret_id='.$SecretId.'&signature='.$SecretKey.'&num=1&format=text&sep=1&dedup=1'.$query;
        $ipx = $this->curl_api($proxy_ip);
        if($ipx){
            $info = [
                'ip_port' => $ipx,
                'user'    => 'd4776611780',
                'pass'    => 'dns38s67',
            ];
            $this->options['proxy'] = $info;
            //Cache::set('lsp_id'.$ip,$info,60);
        }
        return $this;
    }
    
    private function curl_api($url,$header=array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //设置选项，包括URL
        if(!empty($header)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        // 是否抓取跳转后的页面
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        //打印获得的数据
        return $output;
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

        // 设置IP版本
        if ($this->options['ip_version'] === 6) {
            curl_setopt($this->ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6);
        } elseif ($this->options['ip_version'] === 4) {
            curl_setopt($this->ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        } else {
            curl_setopt($this->ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_WHATEVER);
        }

        // 设置自定义DNS服务器
        if (!empty($this->options['dns_server'])) {
            curl_setopt($this->ch, CURLOPT_DNS_SERVERS, $this->options['dns_server']);
        }

        // 设置请求头
        $headers = [];
        foreach ($this->options['headers'] as $name => $value) {

            // 处理原始HTTP头格式（已由header()方法解析）
            $normalizedName = strtolower(trim($name));

            // 处理 X-Forwarded-For：只保留最后一个IP或移除该头部
            if ($normalizedName === 'x-forwarded-for') {
                $ips = array_map('trim', explode(',', $value));
                if (count($ips) > 1) {
                    // 方案A：只保留最后一个IP
                    $value = end($ips);
                    // 方案B：直接移除该头部（取消下面注释启用）
                     continue;
                }
            }

            // 跳过不完整的 multipart/form-data Content-Type（让 CURL 自动生成正确的 boundary）
            if ($normalizedName === 'content-type' && stripos($value, 'multipart/form-data') !== false && stripos($value, 'boundary') === false) {
                continue;
            }

            // 处理原始HTTP头格式（已由header()方法解析）
            $headers[] = "$name: " . trim($value);
        }
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);

        // 设置代理
        if (!empty($this->options['proxy'])) {
            curl_setopt($this->ch, CURLOPT_PROXY, $this->options['proxy']['ip_port']);
            if(isset($this->options['proxy']['user'])){
                curl_setopt($this->ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $this->options['proxy']['user'].':'.$this->options['proxy']['pass']);
            }
            // 禁止代理自动添加 X-Forwarded-For 等头部
            //curl_setopt($this->ch, CURLOPT_PROXYHEADER, []);
        }

        // 设置请求体或表单数据
        if (!empty($this->options['form_data'])) {
            // 检查是否设置了 multipart/form-data
            $isMultipart = false;
            foreach ($headers as $header) {
                if (stripos($header, 'content-type') !== false && stripos($header, 'multipart/form-data') !== false) {
                    $isMultipart = true;
                    break;
                }
            }

            if ($isMultipart) {
                // 使用真正的 multipart/form-data（由 CURL 自动生成 boundary）
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->options['form_data']);
            } else {
                // 使用 application/x-www-form-urlencoded
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->options['form_data']));
            }
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
        if(!$this->response['body'] && !empty($this->options['lsp_id']) ){
            
            Cache::delete('lsp_id'.$this->options['lsp_id']);
  
        }
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
    

}