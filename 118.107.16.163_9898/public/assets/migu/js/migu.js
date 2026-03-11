/**
 * 咪咕系统 - 公共 JS 工具库
 * 零依赖模块系统，直接通过 <script> 加载
 * 依赖: jQuery 3.x, Bootstrap 4.x
 */
var Migu = (function($) {
    'use strict';

    var _toastContainer = null;

    function _ensureToastContainer() {
        if (!_toastContainer || !_toastContainer.length) {
            _toastContainer = $('<div class="migu-toast-container"></div>').appendTo('body');
        }
        return _toastContainer;
    }

    var api = {
        /**
         * AJAX 请求封装
         */
        request: function(url, data, callback, method) {
            method = method || 'POST';
            return $.ajax({
                url: url,
                type: method,
                data: data,
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res) {
                    if (typeof callback === 'function') callback(res);
                },
                error: function(xhr) {
                    api.toast('网络错误: ' + xhr.status, 'error');
                }
            });
        },

        get: function(url, data, callback) {
            return api.request(url, data, callback, 'GET');
        },

        post: function(url, data, callback) {
            return api.request(url, data, callback, 'POST');
        },

        /**
         * Toast 通知
         */
        toast: function(msg, type, duration) {
            type = type || 'info';
            duration = duration || 3000;
            var icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-circle', info: 'fa-info-circle' };
            var $el = $('<div class="migu-toast migu-toast-' + type + '"><i class="fa ' + (icons[type] || icons.info) + '"></i> ' + msg + '</div>');
            _ensureToastContainer().append($el);
            setTimeout(function() { $el.fadeOut(300, function() { $el.remove(); }); }, duration);
        },

        /**
         * 确认弹窗
         */
        confirm: function(msg, callback) {
            if (window.confirm(msg)) {
                if (typeof callback === 'function') callback();
            }
        },

        /**
         * Loading 遮罩
         */
        showLoading: function(container) {
            var $c = $(container || 'body');
            if ($c.find('.migu-loading-mask').length) return;
            $c.css('position', 'relative');
            $c.append('<div class="migu-loading-mask"><div class="migu-spinner"></div></div>');
        },

        hideLoading: function(container) {
            $(container || 'body').find('.migu-loading-mask').remove();
        },

        /**
         * 格式化时间戳
         */
        formatTime: function(ts) {
            if (!ts) return '-';
            var d = typeof ts === 'number' ? new Date(ts * 1000) : new Date(ts);
            if (isNaN(d.getTime())) return ts;
            var pad = function(n) { return n < 10 ? '0' + n : n; };
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
        },

        /**
         * 复制文本到剪贴板
         */
        copy: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() { api.toast('已复制', 'success'); });
            } else {
                var $tmp = $('<textarea>').val(text).appendTo('body').select();
                document.execCommand('copy');
                $tmp.remove();
                api.toast('已复制', 'success');
            }
        }
    };

    /**
     * 数据表格组件
     * 兼容 FastAdmin 后端 buildparams() 接口
     *
     * 用法: Migu.table('#my-table', { url: '...', columns: [...] })
     */
    api.table = function(selector, opts) {
        var defaults = {
            url: '',
            columns: [],        // [{field:'id', title:'ID', width:'80px', formatter: fn}, ...]
            pageSize: 20,
            search: true,
            searchFields: '',
            toolbar: null,      // 额外工具栏 HTML
            onLoad: null        // 数据加载后回调
        };
        opts = $.extend({}, defaults, opts);

        var $wrap = $(selector);
        var currentPage = 1;
        var totalRows = 0;
        var searchKeyword = '';
        var sortField = 'id';
        var sortOrder = 'desc';

        // 构建 DOM
        var html = '<div class="migu-table-toolbar">' +
            '<div class="d-flex align-items-center gap-2">' +
            (opts.toolbar || '') +
            '</div>' +
            (opts.search ? '<div class="search-box"><i class="fa fa-search"></i><input type="text" class="form-control form-control-sm" placeholder="搜索..."></div>' : '') +
            '</div>' +
            '<div class="table-responsive"><table class="table migu-table"><thead><tr></tr></thead><tbody></tbody></table></div>' +
            '<div class="migu-pagination"><span class="page-info"></span><div class="page-btns"></div></div>';
        $wrap.html(html);

        var $thead = $wrap.find('thead tr');
        var $tbody = $wrap.find('tbody');
        var $pageInfo = $wrap.find('.page-info');
        var $pageBtns = $wrap.find('.page-btns');
        var $search = $wrap.find('.search-box input');

        // 渲染表头
        $.each(opts.columns, function(i, col) {
            var style = col.width ? ' style="width:' + col.width + '"' : '';
            var sortable = col.sortable !== false ? ' class="sortable" data-field="' + col.field + '"' : '';
            $thead.append('<th' + style + sortable + '>' + col.title + '</th>');
        });

        // 搜索
        var searchTimer = null;
        $search.on('input', function() {
            clearTimeout(searchTimer);
            var val = $(this).val();
            searchTimer = setTimeout(function() {
                searchKeyword = val;
                currentPage = 1;
                loadData();
            }, 400);
        });

        // 排序
        $thead.on('click', '.sortable', function() {
            var field = $(this).data('field');
            if (sortField === field) {
                sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                sortField = field;
                sortOrder = 'desc';
            }
            loadData();
        });

        function loadData() {
            var offset = (currentPage - 1) * opts.pageSize;
            var params = {
                offset: offset,
                limit: opts.pageSize,
                sort: sortField,
                order: sortOrder,
                search: searchKeyword,
                _: Date.now()
            };
            api.showLoading($wrap);
            $.ajax({
                url: opts.url,
                type: 'GET',
                data: params,
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res) {
                    api.hideLoading($wrap);
                    totalRows = res.total || 0;
                    renderRows(res.rows || []);
                    renderPagination();
                    if (typeof opts.onLoad === 'function') opts.onLoad(res);
                },
                error: function() {
                    api.hideLoading($wrap);
                    $tbody.html('<tr><td colspan="' + opts.columns.length + '" class="text-center text-muted py-4">加载失败</td></tr>');
                }
            });
        }

        function renderRows(rows) {
            if (!rows.length) {
                $tbody.html('<tr><td colspan="' + opts.columns.length + '" class="migu-empty"><i class="fa fa-inbox"></i>暂无数据</td></tr>');
                return;
            }
            var html = '';
            $.each(rows, function(i, row) {
                html += '<tr data-id="' + (row.id || '') + '">';
                $.each(opts.columns, function(j, col) {
                    var val = row[col.field];
                    if (val === undefined || val === null) val = '';
                    if (typeof col.formatter === 'function') val = col.formatter(val, row, i);
                    html += '<td>' + val + '</td>';
                });
                html += '</tr>';
            });
            $tbody.html(html);
        }

        function renderPagination() {
            var totalPages = Math.ceil(totalRows / opts.pageSize) || 1;
            $pageInfo.text('共 ' + totalRows + ' 条，第 ' + currentPage + '/' + totalPages + ' 页');
            var btns = '';
            btns += '<button class="pg-prev"' + (currentPage <= 1 ? ' disabled' : '') + '>&laquo;</button>';
            var start = Math.max(1, currentPage - 2);
            var end = Math.min(totalPages, start + 4);
            start = Math.max(1, end - 4);
            for (var p = start; p <= end; p++) {
                btns += '<button class="pg-num' + (p === currentPage ? ' active' : '') + '" data-page="' + p + '">' + p + '</button>';
            }
            btns += '<button class="pg-next"' + (currentPage >= totalPages ? ' disabled' : '') + '>&raquo;</button>';
            $pageBtns.html(btns);
        }

        $pageBtns.on('click', '.pg-prev', function() { if (currentPage > 1) { currentPage--; loadData(); } });
        $pageBtns.on('click', '.pg-next', function() { var tp = Math.ceil(totalRows / opts.pageSize); if (currentPage < tp) { currentPage++; loadData(); } });
        $pageBtns.on('click', '.pg-num', function() { currentPage = parseInt($(this).data('page')); loadData(); });

        // 公开方法
        var instance = {
            reload: function() { loadData(); },
            setPage: function(p) { currentPage = p; loadData(); }
        };

        loadData();
        return instance;
    };

    return api;
})(jQuery);
