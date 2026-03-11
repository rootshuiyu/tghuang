-- 咪咕系统 - 菜单与站点名一键初始化
-- 执行前请备份 fa_admin_rule、fa_config。若表前缀不是 fa_，请全局替换为实际前缀（如 hub_）。

-- 0. 站点名称改为「咪咕系统」（后台顶部标题来源）
UPDATE fa_config SET value = '咪咕系统' WHERE name = 'name';

-- 1. 现有 yunos 菜单改为咪咕文案
UPDATE fa_admin_rule SET title = '游戏渠道列表' WHERE name = 'yunos/access' AND ismenu = 1;
UPDATE fa_admin_rule SET title = '游戏账号列表' WHERE name = 'yunos/account' AND ismenu = 1;
UPDATE fa_admin_rule SET title = '支付订单数据' WHERE name = 'yunos/order' AND ismenu = 1;
UPDATE fa_admin_rule SET title = '分后台管理' WHERE name = 'yunos/sup' AND ismenu = 1;
UPDATE fa_admin_rule SET title = '仪表盘' WHERE name = 'dashboard' AND ismenu = 1;

-- 2. 新增「收银台地址」菜单（与 yunos/access 同级）
INSERT INTO fa_admin_rule (pid, name, title, icon, `condition`, remark, ismenu, createtime, updatetime, weigh, status)
SELECT r.pid, 'yunos/cashier', '收银台地址', 'fa fa-credit-card', '', '', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM fa_admin_rule r WHERE r.name = 'yunos/access' LIMIT 1
AND NOT EXISTS (SELECT 1 FROM fa_admin_rule x WHERE x.name = 'yunos/cashier');

-- 3. 新增「抽佣点位记录」菜单
INSERT INTO fa_admin_rule (pid, name, title, icon, `condition`, remark, ismenu, createtime, updatetime, weigh, status)
SELECT r.pid, 'yunos/commission', '抽佣点位记录', 'fa fa-list-alt', '', '', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM fa_admin_rule r WHERE r.name = 'yunos/access' LIMIT 1
AND NOT EXISTS (SELECT 1 FROM fa_admin_rule x WHERE x.name = 'yunos/commission');

-- 4. 其它 yunos 菜单咪咕化文案（若存在则更新，不存在则无影响）
UPDATE fa_admin_rule SET title = '资金流水' WHERE name = 'yunos/fund' AND ismenu = 1;
UPDATE fa_admin_rule SET title = '卡密管理' WHERE name = 'yunos/card' AND ismenu = 1;
UPDATE fa_admin_rule SET title = '店铺管理' WHERE name = 'yunos/shop' AND ismenu = 1;
UPDATE fa_admin_rule SET title = '安全令牌' WHERE name = 'yunos/safetoken' AND ismenu = 1;
UPDATE fa_admin_rule SET title = '反馈记录' WHERE name = 'yunos/fafeedback' AND ismenu = 1;
UPDATE fa_admin_rule SET title = '汇总订单' WHERE name = 'yunos/fahuborder' AND ismenu = 1;
UPDATE fa_admin_rule SET title = '邀请码' WHERE name = 'yunos/fcode' AND ismenu = 1;
UPDATE fa_admin_rule SET title = '订单查询' WHERE name = 'yunos/yorder' AND ismenu = 1;

-- 执行后请清空 runtime/cache 或后台点击「清空缓存」，再刷新后台。
