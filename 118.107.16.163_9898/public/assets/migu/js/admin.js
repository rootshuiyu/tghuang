/**
 * 咪咕系统 - SPA 管理后台业务逻辑
 * 依赖: jQuery 3.x, Bootstrap 4.x, AdminLTE 3.2, DataTables, SweetAlert2
 */
(function($) {
'use strict';

var BASE = window.MIGU_BASE || '';
var IS_BOSS = window.MIGU_IS_BOSS || 0;

function api(path) { return BASE + '/' + path; }

function fmtTime(ts) {
    if (!ts) return '-';
    var d = typeof ts === 'number' ? new Date(ts * 1000) : new Date(ts);
    if (isNaN(d.getTime())) return ts;
    var p = function(n) { return n < 10 ? '0' + n : n; };
    return d.getFullYear() + '-' + p(d.getMonth()+1) + '-' + p(d.getDate()) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
}

function copyText(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            Swal.fire({icon:'success',title:'已复制',timer:1000,showConfirmButton:false});
        });
    } else {
        var $t = $('<textarea>').val(text).appendTo('body').select();
        document.execCommand('copy');
        $t.remove();
        Swal.fire({icon:'success',title:'已复制',timer:1000,showConfirmButton:false});
    }
}

function escHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

var DT_LANG = {
    processing: '加载中...',
    lengthMenu: '每页 _MENU_ 条',
    zeroRecords: '暂无数据',
    info: '共 _TOTAL_ 条，第 _PAGE_/_PAGES_ 页',
    infoEmpty: '暂无数据',
    infoFiltered: '(从 _MAX_ 条中筛选)',
    search: '搜索:',
    paginate: { first:'首页', previous:'上一页', next:'下一页', last:'末页' },
    emptyTable: '<div class="migu-empty"><i class="fas fa-inbox"></i>暂无数据</div>'
};

function miguTable(selector, url, columns, extraOpts) {
    extraOpts = extraOpts || {};
    var onLoad = extraOpts.onLoad; delete extraOpts.onLoad;
    return $(selector).DataTable($.extend({
        processing: true,
        serverSide: true,
        ajax: function(data, callback) {
            var col = data.columns[data.order[0] ? data.order[0].column : 0];
            $.ajax({
                url: api(url),
                type: 'GET',
                data: {
                    offset: data.start,
                    limit: data.length,
                    sort: col ? col.data : 'id',
                    order: data.order[0] ? data.order[0].dir : 'desc',
                    search: data.search.value,
                    _: Date.now()
                },
                headers: {'X-Requested-With':'XMLHttpRequest'},
                success: function(res) {
                    callback({ draw: data.draw, recordsTotal: res.total||0, recordsFiltered: res.total||0, data: res.rows||[] });
                    if (typeof onLoad === 'function') onLoad(res);
                },
                error: function() {
                    callback({ draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
                }
            });
        },
        columns: columns,
        order: [[0, 'desc']],
        pageLength: 20,
        language: DT_LANG,
        dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rt<"row"<"col-sm-5"i><"col-sm-7"p>>',
        destroy: true
    }, extraOpts));
}

var ORDER_STATUS = {0:'正在下单',1:'下单失败',2:'等待支付',3:'订单超时',10:'支付成功'};
var ORDER_BADGE  = {0:'badge-info',1:'badge-danger',2:'badge-warning',3:'badge-secondary',10:'badge-success'};
var PAY_TYPE_MAP = {'qqpay':'QQ支付','wxpay':'微信支付','alipay':'支付宝支付'};

/* ============================
   页面路由
   ============================ */
window.loadPage = function(page) {
    $('.nav-sidebar .nav-link').removeClass('active');
    var sel = 'a[onclick="loadPage(\'' + page + '\')"]';
    $(sel).addClass('active');

    if ($(window).width() <= 991) {
        $('body').removeClass('sidebar-open');
    }

    var titles = {
        'dashboard':'仪表盘','game-channels':'游戏渠道列表','game-accounts':'添加游戏账号',
        'game-accounts-list':'游戏账号列表','payment-orders':'支付订单数据','order-search':'订单数据查询',
        'commission-records':'抽佣点位记录','cashier-urls':'收银台地址','admin-management':'分后台管理',
        'system-config':'后台配置管理'
    };
    var t = titles[page] || page;
    $('#pageTitle').text(t);
    $('#pageBreadcrumb').text(t);

    switch(page) {
        case 'dashboard': loadDashboard(); break;
        case 'game-channels': loadGameChannels(); break;
        case 'game-accounts': loadGameAccounts(); break;
        case 'game-accounts-list': loadGameAccountsList(); break;
        case 'payment-orders': loadPaymentOrders(); break;
        case 'order-search': loadOrderSearch(); break;
        case 'commission-records': loadCommissionRecords(); break;
        case 'cashier-urls': loadCashierUrls(); break;
        case 'admin-management': loadAdminManagement(); break;
        case 'system-config': loadSystemConfig(); break;
        default: $('#main-content').html('<div class="migu-empty"><i class="fas fa-exclamation-triangle"></i>页面不存在</div>');
    }
};

/* ============================
   仪表盘
   ============================ */
function loadDashboard() {
    var h = '<div class="row">' +
        '<div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3 id="s-day-in">-</h3><p>今日收入</p></div><div class="icon"><i class="fas fa-dollar-sign"></i></div></div></div>' +
        '<div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3 id="s-yesterday">-</h3><p>昨日收入</p></div><div class="icon"><i class="fas fa-chart-line"></i></div></div></div>' +
        '<div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3 id="s-day-rate">-</h3><p>今日成功率</p></div><div class="icon"><i class="fas fa-chart-pie"></i></div></div></div>' +
        '<div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3 id="s-hour-rate">-</h3><p>近1小时成功率</p></div><div class="icon"><i class="fas fa-clock"></i></div></div></div>' +
        '</div>' +
        '<div class="row">' +
        '<div class="col-md-6"><div class="card"><div class="card-header"><h3 class="card-title">总体概况</h3></div>' +
        '<div class="card-body p-0"><table class="table table-striped"><tbody>' +
        '<tr><td>总成功率</td><td class="text-right font-weight-bold" id="s-total-rate">-</td></tr>' +
        '<tr><td>今日收入</td><td class="text-right font-weight-bold" id="s-day-in2">-</td></tr>' +
        '<tr><td>昨日收入</td><td class="text-right font-weight-bold" id="s-yesterday2">-</td></tr>' +
        '<tr><td>近1小时收入</td><td class="text-right font-weight-bold" id="s-hour-in">-</td></tr>' +
        '</tbody></table></div></div></div>' +
        '<div class="col-md-6"><div class="card"><div class="card-header"><h3 class="card-title">快捷操作</h3></div>' +
        '<div class="card-body">' +
        '<button class="btn btn-primary btn-sm mr-2 mb-2" onclick="loadPage(\'game-channels\')"><i class="fas fa-th mr-1"></i>渠道管理</button>' +
        '<button class="btn btn-success btn-sm mr-2 mb-2" onclick="loadPage(\'game-accounts\')"><i class="fas fa-plus mr-1"></i>添加账号</button>' +
        '<button class="btn btn-info btn-sm mr-2 mb-2" onclick="loadPage(\'payment-orders\')"><i class="fas fa-credit-card mr-1"></i>订单管理</button>' +
        '<button class="btn btn-outline-secondary btn-sm mr-2 mb-2" onclick="loadPage(\'cashier-urls\')"><i class="fas fa-link mr-1"></i>收银台</button>' +
        '<button class="btn btn-outline-secondary btn-sm mr-2 mb-2" onclick="loadPage(\'commission-records\')"><i class="fas fa-percentage mr-1"></i>抽佣记录</button>' +
        '</div></div></div></div>';
    $('#main-content').html(h);
    $.ajax({
        url: api('yunos/order/custom'), type:'GET', dataType:'json',
        headers:{'X-Requested-With':'XMLHttpRequest'},
        success: function(r) {
            $('#s-day-in').text(r.day_in||'0元'); $('#s-day-in2').text(r.day_in||'0元');
            $('#s-yesterday').text(r.yesterday_in||'0元'); $('#s-yesterday2').text(r.yesterday_in||'0元');
            $('#s-day-rate').text(r.day_success||'0%');
            $('#s-hour-rate').text(r.hour_success||'0%');
            $('#s-total-rate').text(r.success||'0%');
            $('#s-hour-in').text(r.hour_in||'0元');
        }
    });
}

/* ============================
   游戏渠道列表（卡片布局）
   ============================ */
function loadGameChannels() {
    var h = '<div class="card"><div class="card-header"><h3 class="card-title">游戏渠道列表</h3>' +
        '<div class="card-tools"><div class="input-group input-group-sm" style="width:250px;"><input type="text" id="searchGameChannel" class="form-control" placeholder="搜索渠道..."><div class="input-group-append"><button class="btn btn-default" id="btnSearchChannel"><i class="fas fa-search"></i></button></div></div></div>' +
        '</div><div class="card-body"><div class="row" id="channelCards"><div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div></div></div>';
    $('#main-content').html(h);

    function renderChannels(keyword) {
        $.ajax({
            url: api('yunos/access/index'), type:'GET', dataType:'json',
            data: { offset:0, limit:100, search:keyword||'', sort:'id', order:'desc' },
            headers:{'X-Requested-With':'XMLHttpRequest'},
            success: function(res) {
                var rows = res.rows||[], cards='';
                if (!rows.length) {
                    cards = '<div class="col-12 migu-empty"><i class="fas fa-inbox"></i>暂无渠道</div>';
                } else {
                    var colors = ['primary','success','info','warning','danger','secondary'];
                    $.each(rows, function(i, r) {
                        var c = colors[i % colors.length];
                        var pt = PAY_TYPE_MAP[r.pay_type] || r.pay_type || '-';
                        var sw = r.switch == 1;
                        var img = r.image ? '<img src="' + escHtml(r.image) + '" class="game-channel-icon mb-2" alt="">' : '<i class="fas fa-gamepad fa-3x mb-2 text-'+c+'"></i>';
                        cards += '<div class="col-lg-3 col-md-4 col-sm-6 mb-3 game-channel-card">' +
                            '<div class="card card-outline card-'+c+'">' +
                            '<div class="card-body text-center">' + img +
                            '<h5 class="card-title">' + escHtml(r.name) + '</h5>' +
                            '<p class="mb-1"><span class="badge badge-'+c+'">' + escHtml(pt) + '</span></p>' +
                            '<p class="mb-2"><span class="badge badge-'+(sw?'success':'secondary')+'">' + (sw?'上线':'下线') + '</span></p>' +
                            '<small class="text-muted">编码: ' + escHtml(r.code) + '</small>' +
                            '</div></div></div>';
                    });
                }
                $('#channelCards').html(cards);
            }
        });
    }
    renderChannels('');
    $('#btnSearchChannel').on('click', function(){ renderChannels($('#searchGameChannel').val()); });
    $('#searchGameChannel').on('keyup', function(e){ if(e.keyCode===13) renderChannels($(this).val()); });
}

/* ============================
   添加游戏账号
   ============================ */
function loadGameAccounts() {
    var h = '<div class="card"><div class="card-header"><h3 class="card-title">添加游戏账号</h3></div>' +
        '<div class="card-body"><p class="text-muted mb-3">请选择要添加账号的游戏渠道：</p>' +
        '<div class="row" id="addAccountChannels"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div></div></div>';
    $('#main-content').html(h);
    $.ajax({
        url: api('yunos/access/index'), type:'GET', dataType:'json',
        data: { offset:0, limit:100, sort:'id', order:'asc' },
        headers:{'X-Requested-With':'XMLHttpRequest'},
        success: function(res) {
            var rows = res.rows||[], cards='';
            if (!rows.length) {
                cards = '<div class="col-12 migu-empty"><i class="fas fa-inbox"></i>暂无可用渠道</div>';
            } else {
                $.each(rows, function(i,r) {
                    if (r.switch != 1 && !IS_BOSS) return;
                    var img = r.image ? '<img src="'+escHtml(r.image)+'" class="game-channel-icon mb-2" alt="">' : '<i class="fas fa-gamepad fa-3x mb-2 text-primary"></i>';
                    cards += '<div class="col-lg-3 col-md-4 col-sm-6 mb-3">' +
                        '<div class="card card-outline card-primary" style="cursor:pointer;" onclick="openAddAccount('+r.id+',\''+escHtml(r.name)+'\')">' +
                        '<div class="card-body text-center">' + img +
                        '<h6 class="card-title">' + escHtml(r.name) + '</h6>' +
                        '<button class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>添加账号</button>' +
                        '</div></div></div>';
                });
            }
            $('#addAccountChannels').html(cards || '<div class="col-12 migu-empty"><i class="fas fa-inbox"></i>暂无可用渠道</div>');
        }
    });
}

window.openAddAccount = function(accessId, accessName) {
    Swal.fire({
        title: '添加账号 - ' + accessName,
        html: '<form id="swalAddAccountForm">' +
              '<div class="form-group text-left"><label>账号备注</label><textarea name="row[remarks]" class="form-control" rows="2" placeholder="备注信息"></textarea></div>' +
              '<div class="form-group text-left"><label>日限额 (元，0=不限)</label><input type="number" name="row[limit_fee]" class="form-control" value="0"></div>' +
              '<div class="form-group text-left"><label>日限单 (0=不限)</label><input type="number" name="row[limit_day_in_number]" class="form-control" value="0"></div>' +
              '<input type="hidden" name="row[access_id]" value="'+accessId+'">' +
              '</form>',
        showCancelButton: true, confirmButtonText:'提交', cancelButtonText:'取消',
        preConfirm: function() {
            var data = $('#swalAddAccountForm').serialize();
            return $.ajax({ url:api('yunos/account/add'), type:'POST', data:data, dataType:'json', headers:{'X-Requested-With':'XMLHttpRequest'} })
                .then(function(res){ if(res.code!==1) throw new Error(res.msg||'添加失败'); return res; })
                .catch(function(e){ Swal.showValidationMessage(e.message||e.responseJSON&&e.responseJSON.msg||'网络错误'); });
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({icon:'success',title:'添加成功',timer:1500,showConfirmButton:false});
        }
    });
};

/* ============================
   游戏账号列表
   ============================ */
function loadGameAccountsList() {
    var h = '<div class="card"><div class="card-header"><h3 class="card-title">游戏账号列表</h3></div>' +
        '<div class="card-body"><table id="accountsTable" class="table table-bordered table-hover" width="100%"></table></div></div>';
    $('#main-content').html(h);
    miguTable('#accountsTable', 'yunos/account/index', [
        { data:'id', title:'ID', width:'60px' },
        { data:'mid', title:'账号编码', render: function(d){ return '<span class="text-monospace">'+escHtml(d)+'</span>'; } },
        { data:'access', title:'所属渠道', render: function(d){ return d ? escHtml(d.name||d.code||'-') : '-'; } },
        { data:'remarks', title:'备注', render: function(d){ return escHtml(d||'-'); } },
        { data:'in_fee', title:'累计入账', render: function(d){ return '<span class="font-weight-bold">'+(d||0)+'</span>'; } },
        { data:'switch', title:'状态', render: function(d,t,row){
            var on = d==1;
            return '<span class="badge badge-'+(on?'success':'secondary')+'">'+(on?'上线':'下线')+'</span>';
        }},
        { data:'match_number', title:'匹配次数' },
        { data:'createtime', title:'创建时间', render: function(d){ return fmtTime(d); } },
        { data:'id', title:'操作', orderable:false, render: function(d,t,row) {
            var on = row.switch==1;
            return '<div class="btn-group btn-group-sm">' +
                '<button class="btn btn-outline-'+(on?'warning':'success')+'" onclick="toggleAccountSwitch('+d+','+(on?0:1)+')" title="'+(on?'下线':'上线')+'"><i class="fas fa-'+(on?'pause':'play')+'"></i></button>' +
                '<button class="btn btn-outline-danger" onclick="deleteAccount('+d+')" title="删除"><i class="fas fa-trash"></i></button>' +
                '</div>';
        }}
    ]);
}

window.toggleAccountSwitch = function(id, val) {
    $.ajax({
        url: api('yunos/account/multi'), type:'POST', dataType:'json',
        data: { ids:id, params:'switch='+val },
        headers:{'X-Requested-With':'XMLHttpRequest'},
        success: function(res) {
            if (res.code===1) {
                Swal.fire({icon:'success',title:'操作成功',timer:1000,showConfirmButton:false});
                $('#accountsTable').DataTable().ajax.reload(null,false);
            } else { Swal.fire({icon:'error',title:res.msg||'操作失败'}); }
        }
    });
};

window.deleteAccount = function(id) {
    Swal.fire({
        title:'确定删除?', text:'删除后不可恢复', icon:'warning',
        showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'删除', cancelButtonText:'取消'
    }).then(function(r){
        if (!r.isConfirmed) return;
        $.ajax({
            url:api('yunos/account/del'), type:'POST', data:{ids:id}, dataType:'json',
            headers:{'X-Requested-With':'XMLHttpRequest'},
            success: function(res) {
                if (res.code===1) {
                    Swal.fire({icon:'success',title:'已删除',timer:1000,showConfirmButton:false});
                    $('#accountsTable').DataTable().ajax.reload(null,false);
                } else { Swal.fire({icon:'error',title:res.msg||'删除失败'}); }
            }
        });
    });
};

/* ============================
   支付订单数据
   ============================ */
function loadPaymentOrders() {
    var h = '<div class="card"><div class="card-header"><h3 class="card-title">支付订单数据</h3>' +
        '<div class="card-tools"><span class="badge badge-success" id="orderSuccessFee">成功金额: -</span></div></div>' +
        '<div class="card-body"><table id="ordersTable" class="table table-bordered table-hover table-sm" width="100%"></table></div></div>';
    $('#main-content').html(h);
    miguTable('#ordersTable', 'yunos/order/index', [
        { data:'id', title:'ID', width:'60px' },
        { data:'orderid', title:'订单号', render: function(d){ return '<span class="text-monospace" style="font-size:12px;">'+escHtml(d)+'</span>'; } },
        { data:'fee', title:'金额', render: function(d){ return '<span class="font-weight-bold text-primary">'+(d||0)+'</span>'; } },
        { data:'status', title:'状态', render: function(d){
            return '<span class="badge '+(ORDER_BADGE[d]||'badge-secondary')+'">'+(ORDER_STATUS[d]||'未知')+'</span>';
        }},
        { data:'suporder', title:'上游单号', render: function(d){ return d ? '<span class="text-monospace" style="font-size:11px;">'+escHtml(d)+'</span>' : '-'; } },
        { data:'paytime', title:'支付时间', render: function(d){ return d ? fmtTime(d) : '-'; } },
        { data:'createtime', title:'创建时间', render: function(d){ return fmtTime(d); } },
        { data:'id', title:'操作', orderable:false, render: function(d,t,row){
            var btns = '';
            if (row.status == 2 || row.status == 10) {
                btns += '<button class="btn btn-outline-info btn-xs mr-1" onclick="orderCallback(\''+escHtml(row.orderid)+'\')"><i class="fas fa-sync-alt"></i> 回调</button>';
            }
            btns += '<button class="btn btn-outline-primary btn-xs" onclick="orderQuery(\''+escHtml(row.orderid)+'\')"><i class="fas fa-search"></i> 查询</button>';
            return btns;
        }}
    ], {
        onLoad: function(res) {
            var fee = res.success_fee || 0;
            $('#orderSuccessFee').text('成功金额: ' + fee + '元');
        }
    });
}

window.orderCallback = function(orderid) {
    Swal.fire({title:'执行回调中...',allowOutsideClick:false,didOpen:function(){Swal.showLoading();}});
    $.ajax({
        url:api('yunos/order/callback'), type:'POST', data:{orderid:orderid}, dataType:'json',
        headers:{'X-Requested-With':'XMLHttpRequest'},
        success: function(res) { Swal.fire({icon:'info',title:'回调结果',text:res.msg||JSON.stringify(res)}); },
        error: function() { Swal.fire({icon:'error',title:'网络错误'}); }
    });
};

window.orderQuery = function(orderid) {
    Swal.fire({title:'查询中...',allowOutsideClick:false,didOpen:function(){Swal.showLoading();}});
    $.ajax({
        url:api('yunos/order/http_query'), type:'POST', data:{orderid:orderid}, dataType:'json',
        headers:{'X-Requested-With':'XMLHttpRequest'},
        success: function(res) { Swal.fire({icon:'info',title:'查询结果',text:res.msg||JSON.stringify(res)}); },
        error: function() { Swal.fire({icon:'error',title:'网络错误'}); }
    });
};

/* ============================
   订单数据查询
   ============================ */
function loadOrderSearch() {
    var h = '<div class="card"><div class="card-header"><h3 class="card-title">订单数据查询</h3></div>' +
        '<div class="card-body"><table id="fahubTable" class="table table-bordered table-hover table-sm" width="100%"></table></div></div>';
    $('#main-content').html(h);
    miguTable('#fahubTable', 'yunos/fahuborder/index', [
        { data:'id', title:'ID', width:'60px' },
        { data:'orderid', title:'订单号', render: function(d){ return '<span class="text-monospace">'+escHtml(d||'')+'</span>'; } },
        { data:'fee', title:'金额', render: function(d){ return '<span class="font-weight-bold">'+(d||0)+'</span>'; } },
        { data:'status', title:'状态', render: function(d){
            var map = {0:'badge-warning',1:'badge-success',2:'badge-info'};
            var txt = {0:'待处理',1:'已完成',2:'处理中'};
            return '<span class="badge '+(map[d]||'badge-secondary')+'">'+(txt[d]||d)+'</span>';
        }},
        { data:'createtime', title:'创建时间', render: function(d){ return fmtTime(d); } }
    ]);
}

/* ============================
   抽佣点位记录
   ============================ */
function loadCommissionRecords() {
    var h = '<div class="card"><div class="card-header"><h3 class="card-title">抽佣点位记录</h3></div>' +
        '<div class="card-body"><table id="commissionTable" class="table table-bordered table-hover table-sm" width="100%"></table></div></div>';
    $('#main-content').html(h);
    miguTable('#commissionTable', 'yunos/commission/index', [
        { data:'id', title:'ID', width:'60px' },
        { data:'user', title:'用户', render: function(d){ return d ? escHtml(d.nickname||d.username||'-') : '-'; } },
        { data:'fee', title:'金额', render: function(d){ return '<span class="font-weight-bold text-danger">'+(d||0)+'</span>'; } },
        { data:'memo', title:'说明', render: function(d){ return escHtml(d||'-'); } },
        { data:'createtime', title:'时间', render: function(d){ return fmtTime(d); } }
    ]);
}

/* ============================
   收银台地址
   ============================ */
function loadCashierUrls() {
    var h = '<div class="card"><div class="card-header"><h3 class="card-title">收银台地址</h3></div>' +
        '<div class="card-body"><div id="cashierList"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div></div></div>';
    $('#main-content').html(h);
    $.ajax({
        url: api('yunos/cashier/index'), type:'GET', dataType:'json',
        headers:{'X-Requested-With':'XMLHttpRequest'},
        success: function(res) {
            var rows = res.rows||[], cards='';
            if (!rows.length) {
                cards = '<div class="migu-empty"><i class="fas fa-inbox"></i>暂无收银台</div>';
            } else {
                $.each(rows, function(i,r) {
                    var sw = r.switch==1;
                    cards += '<div class="col-lg-6 mb-3"><div class="card cashier-url-card">' +
                        '<div class="card-body">' +
                        '<div class="d-flex justify-content-between align-items-center mb-2">' +
                        '<h5 class="mb-0">' + escHtml(r.name) + '</h5>' +
                        '<span class="badge badge-'+(sw?'success':'secondary')+'">'+(sw?'上线':'下线')+'</span>' +
                        '</div>' +
                        '<small class="text-muted">' + escHtml(r.pay_type_text||r.pay_type||'') + '</small>' +
                        '<div class="input-group mt-2">' +
                        '<input type="text" class="form-control cashier-url-input" value="' + escHtml(r.pay_link||'') + '" readonly>' +
                        '<div class="input-group-append">' +
                        '<button class="btn btn-outline-primary copy-button" onclick="copyText(\''+escHtml(r.pay_link||'')+'\')"><i class="fas fa-copy"></i> 复制</button>' +
                        '</div></div></div></div></div>';
                });
                cards = '<div class="row">' + cards + '</div>';
            }
            $('#cashierList').html(cards);
        }
    });
}

/* ============================
   分后台管理（API信息）
   ============================ */
function loadAdminManagement() {
    var h = '<div class="card"><div class="card-header"><h3 class="card-title">API 接入信息</h3></div>' +
        '<div class="card-body" id="supInfo"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div></div>';
    $('#main-content').html(h);
    $.ajax({
        url: api('yunos/sup/index'), type:'GET', dataType:'json',
        headers:{'X-Requested-With':'XMLHttpRequest'},
        success: function(res) {
            var d = res.data || res;
            var html = '<table class="table table-bordered">' +
                '<tr><th style="width:150px;">APPID</th><td><code>' + escHtml(d.appid||'-') + '</code> <button class="btn btn-xs btn-outline-primary ml-2" onclick="copyText(\''+escHtml(d.appid||'')+'\')">' +
                '<i class="fas fa-copy"></i></button></td></tr>' +
                '<tr><th>APPKEY</th><td><code>' + escHtml(d.appkey||'-') + '</code> <button class="btn btn-xs btn-outline-primary ml-2" onclick="copyText(\''+escHtml(d.appkey||'')+'\')">' +
                '<i class="fas fa-copy"></i></button></td></tr>' +
                '<tr><th>费率</th><td>' + escHtml(d.fee||'-') + '</td></tr>' +
                '<tr><th>API 地址</th><td><code>' + escHtml(d.api_url||'-') + '</code> <button class="btn btn-xs btn-outline-primary ml-2" onclick="copyText(\''+escHtml(d.api_url||'')+'\')">' +
                '<i class="fas fa-copy"></i></button></td></tr>' +
                '</table>';
            $('#supInfo').html(html);
        },
        error: function() {
            $('#supInfo').html('<div class="alert alert-danger">加载失败</div>');
        }
    });
}

/* ============================
   后台配置管理
   ============================ */
function loadSystemConfig() {
    var h = '<div class="card"><div class="card-header"><h3 class="card-title">后台配置管理</h3></div>' +
        '<div class="card-body" id="configContent"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div></div>';
    $('#main-content').html(h);
    $.ajax({
        url: api('general/config/index'), type:'GET', dataType:'json',
        headers:{'X-Requested-With':'XMLHttpRequest'},
        success: function(res) {
            var groups = res.data || [];
            if (!groups.length) { $('#configContent').html('<div class="migu-empty"><i class="fas fa-cog"></i>暂无配置</div>'); return; }
            var tabs='<ul class="nav nav-tabs" id="configTabs">', panels='<div class="tab-content mt-3" id="configPanels">';
            $.each(groups, function(i,g) {
                if (!g.list || !g.list.length) return;
                var active = i===0 ? ' active' : '';
                tabs += '<li class="nav-item"><a class="nav-link'+active+'" data-toggle="tab" href="#cfg-'+g.name+'">'+escHtml(g.title)+'</a></li>';
                panels += '<div class="tab-pane fade'+(i===0?' show active':'')+'" id="cfg-'+g.name+'">';
                $.each(g.list, function(j,item) {
                    panels += renderConfigField(item);
                });
                panels += '</div>';
            });
            tabs += '</ul>'; panels += '</div>';
            var formHtml = '<form id="configForm">' + tabs + panels +
                '<div class="mt-3"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>保存配置</button></div></form>';
            $('#configContent').html(formHtml);
            $('#configForm').on('submit', function(e) {
                e.preventDefault();
                var btn = $(this).find('[type=submit]').prop('disabled',true).html('<i class="fas fa-spinner fa-spin mr-1"></i>保存中...');
                $.ajax({
                    url: api('general/config/edit'), type:'POST', data: $(this).serialize(), dataType:'json',
                    headers:{'X-Requested-With':'XMLHttpRequest'},
                    success: function(res) {
                        btn.prop('disabled',false).html('<i class="fas fa-save mr-1"></i>保存配置');
                        if (res.code===1) { Swal.fire({icon:'success',title:'保存成功',timer:1500,showConfirmButton:false}); }
                        else { Swal.fire({icon:'error',title:res.msg||'保存失败'}); }
                    },
                    error: function() {
                        btn.prop('disabled',false).html('<i class="fas fa-save mr-1"></i>保存配置');
                        Swal.fire({icon:'error',title:'网络错误'});
                    }
                });
            });
        },
        error: function() { $('#configContent').html('<div class="alert alert-danger">加载配置失败</div>'); }
    });
}

function renderConfigField(item) {
    var n = 'row['+item.name+']';
    var val = Array.isArray(item.value) ? item.value.join(',') : (item.value||'');
    var tip = item.tip ? '<small class="form-text text-muted">'+escHtml(item.tip)+'</small>' : '';
    var field = '';
    switch(item.type) {
        case 'string':
            field = '<input type="text" class="form-control" name="'+n+'" value="'+escHtml(val)+'">'; break;
        case 'password':
            field = '<input type="password" class="form-control" name="'+n+'" value="'+escHtml(val)+'" autocomplete="new-password">'; break;
        case 'text':
            field = '<textarea class="form-control" name="'+n+'" rows="3">'+escHtml(val)+'</textarea>'; break;
        case 'number':
            field = '<input type="number" class="form-control" name="'+n+'" value="'+escHtml(val)+'">'; break;
        case 'switch':
            var ck = val=='1' ? ' checked' : '';
            field = '<input type="hidden" name="'+n+'" value="0"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="cfg_'+item.name+'" name="'+n+'" value="1"'+ck+'><label class="custom-control-label" for="cfg_'+item.name+'">启用</label></div>'; break;
        case 'select':
            field = '<select class="form-control" name="'+n+'">';
            if (item.content) { $.each(item.content, function(k,v){ var sel = (String(val)===String(k) || (Array.isArray(item.value)&&item.value.indexOf(k)>=0)) ? ' selected' : ''; field += '<option value="'+escHtml(k)+'"'+sel+'>'+escHtml(v)+'</option>'; }); }
            field += '</select>'; break;
        default:
            field = '<input type="text" class="form-control" name="'+n+'" value="'+escHtml(val)+'">'; break;
    }
    return '<div class="form-group row"><label class="col-sm-3 col-form-label">'+escHtml(item.title)+'</label><div class="col-sm-9">'+field+tip+'</div></div>';
}

/* ============================
   通知轮询
   ============================ */
function pollNotifications() {
    $.ajax({
        url: api('yunos/order/recent_count'), type:'GET', dataType:'json',
        headers:{'X-Requested-With':'XMLHttpRequest'},
        success: function(res) {
            if (res && res.code===1 && res.count > 0) {
                $('#notificationBadge').text(res.count).show();
            } else {
                $('#notificationBadge').hide();
            }
        }
    });
}

/* ============================
   侧栏折叠持久化
   ============================ */
function initSidebarState() {
    if ($(window).width() > 991) {
        var collapsed = localStorage.getItem('sidebarCollapsed');
        if (collapsed === 'true') {
            setTimeout(function(){
                $('body').addClass('sidebar-collapse').removeClass('sidebar-open');
            }, 100);
        }
        $(document).on('collapsed.lte.pushmenu', function(){ localStorage.setItem('sidebarCollapsed','true'); });
        $(document).on('shown.lte.pushmenu', function(){ localStorage.setItem('sidebarCollapsed','false'); });
    }
}

/* ============================
   初始化
   ============================ */
$(function() {
    setTimeout(function(){ $('.preloader').addClass('fade-out'); setTimeout(function(){ $('.preloader').remove(); }, 300); }, 500);
    initSidebarState();
    loadPage('dashboard');
    pollNotifications();
    setInterval(pollNotifications, 60000);
});

})(jQuery);
