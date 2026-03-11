<?php
namespace app\common\middleware;

use ReflectionClass;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

use app\common\service\ApiAuth;

class ApiPermissions implements MiddlewareInterface
{
    public function process(Request $request, callable $handler) : Response
    {
        $origin = $request->header('origin');
        $headers = [
            'Access-Control-Allow-Origin' => $origin ?: '*',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type,Authorization,X-Requested-With,x-token,x-Client-Type',
            'Content-Type' => 'application/json; charset=utf-8',
            'Server' => 'YOYO',
        ];

        if($request->method() == 'OPTIONS'){
            return response('ok', 200, $headers);
        }

        $response = $handler($request);

        // 给响应添加跨域相关的http头
        if ($origin) {
            $response = $response->withHeaders($headers);
        }

        return $response;
    }
}