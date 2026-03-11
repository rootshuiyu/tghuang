# xnapi 接口与配置规范

## 一、JSON 响应格式（推荐统一）

继承 `app\api\controller\Api` 的控制器请使用 `$this->success()` / `$this->error()`，保证出参一致：

```json
{
  "code": 1,
  "msg": "SUCCESS",
  "time": 1734567890,
  "success": true,
  "data": { ... }
}
```

- **code**：`1` 表示成功，`0` 或其它表示业务失败。
- **success**：与 code 一致，`code === 1` 时为 `true`。
- **msg**：提示信息；**data**：业务数据，可为 `null`。

错误示例：`code: 0`, `success: false`, `msg`: 错误说明。

## 二、配置集中（避免硬编码）

- **config/backend.php**
  - `admin_base_url`：9898 后台根 URL（qrcode、回调等）
  - `api_base_url`：对外 encode 等 API 根 URL
  - `yunos_log_dir`：业务日志目录，空则用 `runtime/yunos_logs`
  - `sync_callback_key`：内部回调密钥，须与 9898 后台配置一致

部署/换环境时只改 **config/backend.php**，不要改业务代码。

## 三、老接口迁移建议

仍直接 `return json([...])` 的接口，建议逐步改为 `$this->success($msg, $data)` 或 `$this->error($msg)`，以便前端统一解析。
