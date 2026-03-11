<?php

namespace app\common\exception;

use Webman\Http\Request;
use Webman\Http\Response;

class MyBusinessException
{
    public function render(Request $request): ?Response
    {
       
        return json(['code' => 2]);
        // 非json请求则返回一个页面
        return new Response(200, [], $this->getMessage());
    }
}