<?php
namespace app\api\controller;

use support\Request;

class StaticResource
{
    
    public function serve(Request $request)
    {
        // 从请求路径中获取，去掉前缀 /api/view-res/
        $uri = $request->path();
        $path = str_replace('/api/view-res/', '', $uri);
        
        // 安全检查：防止路径穿越攻击
        if (strpos($path, '..') !== false || strpos($path, '\\') !== false) {
            return response('Invalid path', 403);
        }
        
        // 自动在最后一级目录下添加 res
        // 例如：pay/1.css -> pay/res/1.css
        // 例如：pay/dysq/1.css -> pay/dysq/res/1.css
        $pathParts = explode('/', $path);
        $filename = array_pop($pathParts); // 取出文件名
        $pathParts[] = 'res'; // 添加 res 目录
        $pathParts[] = $filename; // 放回文件名
        $path = implode('/', $pathParts);
        
        // 拼接完整文件路径：app/api/view/pay/res/1.css
        $file = app_path("api/view/{$path}");
        
        // 检查文件是否存在
        if (!is_file($file)) {
            return 'File not found';
        }
        
        // 返回文件
        return response()->file($file);
    }
    
}
