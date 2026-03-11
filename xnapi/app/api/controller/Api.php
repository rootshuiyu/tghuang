<?php

namespace app\api\controller;

use Webman\Http\Response;
use Webman\Http\Request;
use Jnewer\ExceptionHandler\Exception\HttpException;

/**
 * API 基类
 * 统一 JSON 响应格式：{ code, msg, time, success, data }
 * - code: 1 成功，0 或其它为业务错误
 * - success: 与 code 一致，code===1 时为 true
 * - 新接口请使用 success() / error()，避免直接 return json()
 */
class Api
{
    protected $user_id = '';

    public function __construct(Request $request = null)
    {
        $this->init();
    }

    public function init()
    {
    }

    protected function Core($value = '')
    {
        if (!$value) {
            return;
        }
        $model = new \app\common\model\v2\Core;
        return $model->app($value);
    }

    /** 成功：code=1, success=true */
    protected function success($msg = 'SUCCESS', $data = null, $code = 1): Response
    {
        return $this->result($msg, $data, $code);
    }

    /** 失败：code=0, success=false */
    protected function error($msg = 'ERROR', $data = null, $code = 0): Response
    {
        return $this->result($msg, $data, $code);
    }

    /** 统一出参格式 */
    protected function result($message = 'SUCCESS', $data = null, $code = 0): Response
    {
        $result = [
            'code'    => $code,
            'msg'     => $message,
            'time'    => time(),
            'success' => (int)$code === 1,
            'data'    => $data,
        ];

        throw new HttpException(200, json_encode($result, JSON_UNESCAPED_UNICODE));
    }
}