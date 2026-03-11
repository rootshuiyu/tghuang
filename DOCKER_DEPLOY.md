# Docker 部署指南

## 一、项目结构

```
wwwroot_d3rJ7/
├── docker-compose.yml          # 编排文件（MySQL + Redis + admin + xnapi）
├── .env.example                # 环境变量模板
├── .dockerignore
├── 118.107.16.163_9898/        # 咪咕后台
│   ├── Dockerfile
│   └── docker/
│       ├── nginx-admin.conf
│       └── supervisord-admin.conf
└── xnapi/                      # 支付/下单 API
    └── Dockerfile
```

## 二、本地启动

```bash
# 1. 复制并编辑环境变量
cp .env.example .env
# 修改 .env 中的 DB_PASSWORD、SYNC_CALLBACK_KEY 等

# 2. 构建并启动
docker-compose up -d --build

# 3. 首次：导入数据库（进入 mysql 容器）
docker-compose exec mysql mysql -u xndata -p xndata < /path/to/your-schema.sql
# 再执行咪咕菜单初始化
docker-compose exec mysql mysql -u xndata -p xndata < /path/to/migu_menu.sql

# 4. 访问
# 后台：http://localhost:9898/admin
# API：http://localhost:9588
```

## 三、GitHub 云部署

### 方式 A：GitHub Actions + 云服务器（推荐）

1. 将项目推送到 GitHub 仓库。
2. 在仓库 Settings → Secrets 中添加：
   - `SERVER_HOST`：服务器 IP
   - `SERVER_USER`：SSH 用户名
   - `SERVER_SSH_KEY`：SSH 私钥
   - `DB_PASSWORD`：数据库密码
3. 推送后 GitHub Actions 自动构建并部署到服务器。

### 方式 B：Docker Hub + 手动拉取

```bash
# 在服务器上
git clone https://github.com/你的用户/你的仓库.git
cd 你的仓库
cp .env.example .env
# 编辑 .env
docker-compose up -d --build
```

## 四、服务说明

| 服务 | 容器端口 | 宿主端口 | 说明 |
|------|----------|----------|------|
| admin | 9898 | ${ADMIN_PORT} | 咪咕后台（PHP-FPM + Nginx） |
| xnapi | 9588 | ${XNAPI_PORT} | 支付/下单 API（Webman 常驻） |
| mysql | 3306 | 3306 | MySQL 8.0 |
| redis | 6379 | 6379 | Redis 7 |

## 五、数据持久化

通过 Docker volumes 持久化：
- `mysql_data`：数据库文件
- `redis_data`：Redis 持久化
- `admin_runtime`：后台 runtime（缓存、日志等）
- `admin_uploads`：后台上传文件
- `xnapi_runtime`：API runtime

## 六、常用命令

```bash
# 查看日志
docker-compose logs -f admin
docker-compose logs -f xnapi

# 重启单个服务
docker-compose restart admin

# 进入容器
docker-compose exec admin sh
docker-compose exec xnapi sh

# 清空后台缓存
docker-compose exec admin rm -rf runtime/cache/* runtime/temp/*

# 停止并销毁（不删数据卷）
docker-compose down

# 停止并删除数据卷（慎用）
docker-compose down -v
```

## 七、注意事项

- `.env` 文件包含敏感信息，**不要提交到仓库**（已在 .gitignore 中排除）。
- 9898 的 `database.php` 通过 ThinkPHP 的 `Env::get()` 读取环境变量，Docker 中通过 `environment` 注入。
- xnapi 的 `thinkorm.php` 和 `redis.php` 通过 `getenv()` 读取环境变量。
- 首次部署需导入数据库 schema 和 `data/migu_menu.sql`。
