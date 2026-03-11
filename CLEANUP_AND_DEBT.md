# 冗余清理与技术债整理说明

本文档记录本次「清空删除冗余数据/债、整理思路」的范围与结果，以及后续可继续项。

---

## 一、本次已完成的清理与优化

### 1. xnapi：配置化，去除硬编码

| 项 | 原状 | 现状 |
|----|------|------|
| 9898 地址 / 端口 | 多处写死 `103.207.69.252`、`:9898` | 统一由 **config/backend.php** 提供 |
| 日志目录 | `extend/os/Logger.php` 写死 `/www/wwwroot/118.107.16.163_9898/runtime/yunos_logs/` | 使用 `config('backend.yunos_log_dir')`，空则用 xnapi 自身 **runtime/yunos_logs** |
| Pay::qrcode | 写死 9898 的 qrcode URL，且含死代码（先 return 再 return） | 按 **backend.admin_base_url** 拼 qrcode/build，请求参数透传，返回图片或 502 |
| Index::fun / Index::sss | 写死 `http://103.207.69.252/api/index/encode` | 使用 **backend.api_base_url** 拼接，未配置时返回错误提示 |

**新增文件**

- **xnapi/config/backend.php**  
  - `admin_base_url`：9898 后台根 URL（qrcode、回调等）  
  - `api_base_url`：对外 encode 等 API 根 URL  
  - `yunos_log_dir`：空则用 xnapi 的 runtime/yunos_logs  

**修改文件**

- **xnapi/extend/os/Logger.php**：构造函数中从配置读取日志目录，无配置则用 runtime/yunos_logs。  
- **xnapi/app/api/controller/Pay.php**：qrcode() 改为读配置、透传 query、去掉死代码。  
- **xnapi/app/api/controller/Index.php**：fun()、sss() 中 encode 的 URL 改为从配置读取。

部署时只需改 **xnapi/config/backend.php** 中两个 URL 和（可选）日志目录，无需再搜代码里的 IP/路径。

- **sync_callback 密钥配置化**  
  - xnapi **config/backend.php** 新增 `sync_callback_key`，**Pay::sync_callback** 改为校验 `config('backend.sync_callback_key')`。  
  - 9898 **application/extra/site.php** 新增 `sync_callback_key`，**Order** 中调用 sync_callback / http_query 时从 `config('site.sync_callback_key')` 取 key（缺省保留原值以兼容未写入 DB 的情况）。  
  - 更换密钥时须同时改 xnapi 与 9898 两处配置并保持一致。

### 2. 9898：删除可再生的冗余数据

| 项 | 说明 |
|----|------|
| **runtime/cache/\*** | ThinkPHP 模板/配置等缓存，已清空；运行时会自动重建。 |
| **runtime/temp/\*** | 编译临时文件，已清空；访问后台会重新生成。 |
| **public/demo/yun/\*** | 约 84 个无扩展名的临时文件（疑似测试/临时数据），已删除。 |

**保留**

- **public/demo/build.php、jmiao.html、response/** 等仍保留，作为对接 cashier 的示例/文档用；若确认不再需要可后续再删。

---

## 二、技术债与思路整理（供后续迭代）

### 1. 已缓解的债

- **硬编码**：xnapi 对 9898 的地址、日志路径已收口到 config/backend.php，换环境只需改配置。  
- **死代码**：Pay::qrcode 中无效 return 与写死 URL 已去掉，逻辑单一清晰。  
- **可再生缓存/临时文件**：9898 的 cache/temp 与 demo/yun 已清，避免无谓占盘与干扰。  
- **sync_callback 密钥**：已从代码中移除，改为 xnapi config/backend.php 与 9898 site 配置，两处保持一致即可。  
- **接口响应**：xnapi Api 基类统一 `code/msg/time/success/data`，`success` 与 `code===1` 一致；详见 **xnapi/API_CONVENTION.md**。

### 2. 仍存在、建议后续处理的债

| 类型 | 说明 | 建议 |
|------|------|------|
| **库/表前缀不统一** | 9898 多用 `hub_*`，xnapi 多用 `fa_*`，同库 xndata | 新表尽量统一前缀；老表可逐步在文档中标明归属，避免误用。 |
| **多渠道重复逻辑** | xnapi 各渠道（B站、完美、聚人、虎牙等）存在大量相似下单/回调/查询代码 | 抽公共层（策略/模板方法），新渠道只填配置与少量差异。 |
| **API 响应不统一** | 各处返回格式、错误码不统一 | 定一套 code/msg/data + 错误码规范，新接口遵守，老接口逐步迁。 |
| **敏感信息** | 数据库账号、密钥等仍在配置文件内 | 生产环境建议用环境变量或独立配置（不提交仓库）。 |

### 3. 重构方向（与之前讨论一致）

- **轻量**：在现有两套（9898 + xnapi）上继续配置化、抽公共逻辑、统一响应格式。  
- **抽离 API**：把对外 API 迁到单独服务（如统一用 xnapi 或新项目），9898 只做后台与内部能力。  
- **大重构**：新后端统一承接后台+API，逐步下线 ThinkPHP5/FastAdmin，需排期与风险控制。

---

## 三、使用与检查清单

- **xnapi**  
  - 修改 9898 的域名/IP 或端口时，只改 **config/backend.php** 的 `admin_base_url`、`api_base_url`。  
  - 若希望日志写在 9898 的 runtime，在 backend 中设置 `yunos_log_dir` 为 9898 的 runtime/yunos_logs 绝对路径；默认留空即用 xnapi 自身 runtime。  
  - 内部回调密钥：**config/backend.php** 的 `sync_callback_key` 须与 9898 的 `sync_callback_key` 一致。  
  - 接口规范见 **xnapi/API_CONVENTION.md**。  
- **9898**  
  - 清空 cache/temp 后首次访问后台可能略慢（重新编译模板），属正常。  
  - 若仍有视图或权限问题，可再执行一次后台「清空缓存」。  
  - **sync_callback_key** 在 **application/extra/site.php**（若站点配置来自数据库，需在后台或 DB 中同步该键）。  

---

## 四、业务逻辑向咪咕靠齐

- 所有业务逻辑尽量向咪咕靠齐的**完整清单**（已对齐项、对齐要点、后续可做）见：**118.107.16.163_9898/data/MIGU_ALIGNMENT.md**
- 菜单与站点名执行 **data/migu_menu.sql** 并清缓存即可；更多咪咕化文案见 **application/admin/lang/zh-cn/migu.php** 与 README_MIGU.md。
