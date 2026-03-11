<?php
/**
 * 与 9898 后台 / 外部服务相关的配置，避免硬编码 IP 与路径
 * 部署时请按实际环境修改本文件
 */
return [
    // 9898 后台站点根 URL（用于 qrcode/build、回调等），末尾不要带 /
    'admin_base_url' => 'http://103.207.69.252:9898',
    // 对外 encode 等 API 的根 URL（若与 admin 不同）
    'api_base_url'   => 'http://103.207.69.252',
    // yunos 业务日志目录；空则使用 xnapi 自身 runtime/yunos_logs
    'yunos_log_dir'  => '',
    // 内部回调密钥（sync_callback、http_query 等），须与 9898 后台配置一致
    'sync_callback_key' => 'f29be29fa30cb5a1d0b89e2c290825ac8bec567c',
];