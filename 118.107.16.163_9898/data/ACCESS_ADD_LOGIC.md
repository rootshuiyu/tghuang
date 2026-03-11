# 游戏通道（游戏渠道）添加逻辑说明

当前「游戏渠道列表」对应后台 **yunos/access**（Access 控制器），添加逻辑如下。

---

## 一、入口与权限

- **菜单**：游戏渠道列表 → `yunos/access/index`，列表页有「添加」按钮 → `yunos/access/add`。
- **权限**：走 Backend 鉴权，需拥有 `yunos/access` 及 `yunos/access/add` 节点；若开启数据限制（`dataLimit`），会按 `admin_id` 做数据归属（当前 Access 控制器未显式开启 `dataLimit`）。

---

## 二、添加流程（add）

1. **GET**  
   - 直接渲染 `application/admin/view/yunos/access/add.html`，展示表单。

2. **POST**  
   - 取 `post('row/a')` 作为一条记录参数。
   - **必填**：`row` 不能为空，否则报错 “Parameter can not be empty”。
   - **自动生成**：`code` 在控制器里强制生成，不取自表单：
     - `$params['code'] = strtoupper(\fast\Random::alnum());`  
     即每条通道一个随机字母数字大写 **code**，用于与 xnapi 等对接时标识通道。
   - **数据限制**：若 Backend 开启 `dataLimit` 且 `dataLimitFieldAutoFill = true`，会自动把当前管理员 id 写入 `$params[$this->dataLimitField]`（当前 Access 未开，故无此步）。
   - **保存**：`preExcludeFields` 过滤后，`$this->model->allowField(true)->save($params)` 写入 **access** 表（表前缀以 database 配置为准，如 `hub_access`）。
   - **事务**：整段在 `Db::startTrans()` / `commit()` 中，异常则 `rollback()` 并 `$this->error($e->getMessage())`。
   - **模型验证**：若 `$this->modelValidate` 为 true，会走 Access 模型的 validate（当前未配置 yunos Access 的 validate，故一般不校验）。

---

## 三、添加表单字段（add.html）

| 表单 name | 含义 | 说明 |
|-----------|------|------|
| row[module] | 应用 | 对应 xnapi 里该通道使用的 module（如某渠道模块名） |
| row[name] | 通道名称 | 展示用名称 |
| row[alias] | 通道别名 | 可选别名 |
| row[image] | 图片 | 上传/选择图片地址 |
| row[timeout] | 订单超时时间(秒) | 数字 |
| row[rate] | 通道费率% | 数字，如 2 表示 2% |
| row[pay_tpl] | 支付模板 | 如默认跳转、自动跳转、pendent 等（见控制器 seachlist） |
| row[config_tpl] | 配置模板 | 动态表：配置名称(中文) + 配置标识(英文)，用于该通道下账号的配置项结构 |
| row[pay_type] | 支付方式 | 多选：QQ支付、微信支付、支付宝支付 |
| row[switch] | 上线/下线 | 单选：下线(0)、上线(1) |

**不来自表单、由逻辑写入**：

- **code**：控制器中 `strtoupper(Random::alnum())` 生成，唯一标识该通道。

---

## 四、与其它模块的关系

- **Account（游戏账号）**：账号表通过 `access_id` 关联通道；通道 **下线**（switch=0）时，Access 模型 `beforeUpdate` 会把该通道下所有账号的 `switch` 置为 0。
- **Order**：订单表 `access_id` 指向通道；下单/回调会按通道的 module、pay_tpl、config 等走对应逻辑。
- **收银台（Cashier）**：收银台页按「当前用户可见的 access」列出通道及支付链接；非 boss 只看到 `switch=1` 的通道。
- **xnapi**：支付/下单接口根据 **code** 或 **access_id** 定位通道，再按 module 调对应渠道逻辑。

---

## 五、编辑与列表

- **编辑**：`edit($ids)` 对已有记录按 id 编辑；会做数据权限校验（若有 dataLimit）；编辑时 `pay_type` 为多选，提交前被 `implode(',', $pay_type_arr)` 成字符串存库。
- **列表**：`index` 支持搜索、排序、分页；非 boss 时 `$wheres = ['switch' => 1]`，只显示已上线的通道。

---

## 六、小结（便于与咪咕对齐）

- 添加时**必填**：至少填 row 里若干项（无独立 validate 时由前端/业务约束）。
- **code** 一定由后端随机生成，不可表单填写，保证唯一。
- 通道**上线/下线**影响：下线后该通道下账号统一被置为下线，收银台与列表（非 boss）只展示上线通道。
- 若要与咪咕后台「游戏通道添加」完全一致，需对照咪咕的：字段列表、是否必填、code 生成规则、上线/下线语义及与账号的联动，再按需改 Access 控制器、模型与 add/edit 视图。
