<?php

use app\common\model\Category;
use fast\Form;
use fast\Tree;
use think\Db;
use think\Loader;

if (!function_exists('build_select')) {

    /**
     * 生成下拉列表
     * @param string $name
     * @param mixed  $options
     * @param mixed  $selected
     * @param mixed  $attr
     * @return string
     */
    function build_select($name, $options, $selected = [], $attr = [])
    {
        $options = is_array($options) ? $options : explode(',', $options ?? '');
        $selected = is_array($selected) ? $selected : explode(',', $selected ?? '');
        return Form::select($name, $options, $selected, $attr);
    }
}

if (!function_exists('build_radios')) {

    /**
     * 生成单选按钮组
     * @param string $name
     * @param array  $list
     * @param mixed  $selected
     * @return string
     */
    function build_radios($name, $list = [], $selected = null)
    {
        $html = [];
        $selected = is_null($selected) ? key($list) : $selected;
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        foreach ($list as $k => $v) {
            $html[] = sprintf(Form::label("{$name}-{$k}", "%s " . str_replace('%', '%%', $v)), Form::radio($name, $k, in_array($k, $selected), ['id' => "{$name}-{$k}"]));
        }
        return '<div class="radio">' . implode(' ', $html) . '</div>';
    }
}

if (!function_exists('build_checkboxs')) {

    /**
     * 生成复选按钮组
     * @param string $name
     * @param array  $list
     * @param mixed  $selected
     * @return string
     */
    function build_checkboxs($name, $list = [], $selected = null)
    {
        $html = [];
        $selected = is_null($selected) ? [] : $selected;
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        foreach ($list as $k => $v) {
            $html[] = sprintf(Form::label("{$name}-{$k}", "%s " . str_replace('%', '%%', $v)), Form::checkbox($name, $k, in_array($k, $selected), ['id' => "{$name}-{$k}"]));
        }
        return '<div class="checkbox">' . implode(' ', $html) . '</div>';
    }
}


if (!function_exists('build_category_select')) {

    /**
     * 生成分类下拉列表框
     * @param string $name
     * @param string $type
     * @param mixed  $selected
     * @param array  $attr
     * @param array  $header
     * @return string
     */
    function build_category_select($name, $type, $selected = null, $attr = [], $header = [])
    {
        $tree = Tree::instance();
        $tree->init(Category::getCategoryArray($type), 'pid');
        $categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $categorydata = $header ? $header : [];
        foreach ($categorylist as $k => $v) {
            $categorydata[$v['id']] = $v['name'];
        }
        $attr = array_merge(['id' => "c-{$name}", 'class' => 'form-control selectpicker'], $attr);
        return build_select($name, $categorydata, $selected, $attr);
    }
}

if (!function_exists('build_toolbar')) {

    /**
     * 生成表格操作按钮栏
     * @param array $btns 按钮组
     * @param array $attr 按钮属性值
     * @return string
     */
    function build_toolbar($btns = null, $attr = [])
    {
        $auth = \app\admin\library\Auth::instance();
        $controller = str_replace('.', '/', Loader::parseName(request()->controller()));
        $btns = $btns ? $btns : ['refresh', 'add', 'edit', 'del', 'import'];
        $btns = is_array($btns) ? $btns : explode(',', $btns);
        $index = array_search('delete', $btns);
        if ($index !== false) {
            $btns[$index] = 'del';
        }
        $btnAttr = [
            'refresh' => ['javascript:;', 'yui-btn btn-refresh', 'fa fa-refresh', '', __('Refresh')],
            'add'     => ['javascript:;', 'yui-btn btn-add', 'fa fa-plus', __('Add'), __('Add')],
            'edit'    => ['javascript:;', 'yui-btn btn-edit btn-disabled disabled', 'fa fa-pencil', __('Edit'), __('Edit')],
            'del'     => ['javascript:;', 'yui-btn y-blue btn-del btn-disabled disabled', 'fa fa-trash', __('Delete'), __('Delete')],
            'import'  => ['javascript:;', 'yui-btn btn-import', 'fa fa-upload', __('Import'), __('Import')],
        ];
        $btnAttr = array_merge($btnAttr, $attr);
        $html = [];
        foreach ($btns as $k => $v) {
            //如果未定义或没有权限
            if (!isset($btnAttr[$v]) || ($v !== 'refresh' && !$auth->check("{$controller}/{$v}", $auth->id))) {
                continue;
            }
            list($href, $class, $icon, $text, $title) = $btnAttr[$v];
            //$extend = $v == 'import' ? 'id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"' : '';
            //$html[] = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '" ' . $extend . '><i class="' . $icon . '"></i> ' . $text . '</a>';
            if ($v == 'import') {
                $template = str_replace('/', '_', $controller);
                $download = '';
                if (file_exists("./template/{$template}.xlsx")) {
                    $download .= "<li><a href=\"/template/{$template}.xlsx\" target=\"_blank\">XLSX模版</a></li>";
                }
                if (file_exists("./template/{$template}.xls")) {
                    $download .= "<li><a href=\"/template/{$template}.xls\" target=\"_blank\">XLS模版</a></li>";
                }
                if (file_exists("./template/{$template}.csv")) {
                    $download .= empty($download) ? '' : "<li class=\"divider\"></li>";
                    $download .= "<li><a href=\"/template/{$template}.csv\" target=\"_blank\">CSV模版</a></li>";
                }
                $download .= empty($download) ? '' : "\n                            ";
                if (!empty($download)) {
                    $html[] = <<<EOT
                        <div class="btn-group">
                            <button type="button" href="{$href}" class="btn btn-info btn-import" title="{$title}" id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"><i class="{$icon}"></i> {$text}</button>
                            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" title="下载批量导入模版">
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu" role="menu">{$download}</ul>
                        </div>
EOT;
                } else {
                    $html[] = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '" id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"><i class="' . $icon . '"></i> ' . $text . '</a>';
                }
            } else {
                $html[] = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '"><i class="' . $icon . '"></i> ' . $text . '</a>';
            }
        }
        return implode(' ', $html);
    }
}

if (!function_exists('build_heading')) {

    /**
     * 生成页面Heading
     *
     * @param string $path 指定的path
     * @return string
     */
    function build_heading($path = null, $container = true)
    {
        $title = $content = '';
        if (is_null($path)) {
            $action = request()->action();
            $controller = str_replace('.', '/', Loader::parseName(request()->controller()));
            $path = strtolower($controller . ($action && $action != 'index' ? '/' . $action : ''));
        }
        // 根据当前的URI自动匹配父节点的标题和备注
        $data = Db::name('auth_rule')->where('name', $path)->field('title,remark')->find();
        if ($data) {
            $title = __($data['title']);
            $content = __($data['remark']);
        }
        if (!$content) {
            return '';
        }
        $result = '<div class="panel-lead"><em>' . $title . '</em>' . $content . '</div>';
        if ($container) {
            $result = '<div class="panel-heading">' . $result . '</div>';
        }
        return $result;
    }
}

 if(!function_exists('queryck')){
    function queryck($query){
        if(!$query){
            return [];
        }
        $query = str_replace(';','&',$query);
        parse_str($query, $params);
        return $params;
        
    }
 }
 
if (!function_exists('t')) {
 function t($field = null){
    
    $body = [
        'y' => date('Y'),
        'm' => date('m'),
        'd' => date('d'),
        'h' => date('h'),
        'i' => date('i'),
        's' => date('s'),
        'f' => date('f'),
    ];
    if(date('s') > 30){
      $body['f'] = 60 - date('s');  
    }
    
    if($field){
        return $body[$field];
    }
    return $body;
    
}
}

if (!function_exists('getFirstSixCombinedDigits')) {
function getFirstSixCombinedDigits($string) {
    // 使用正则表达式匹配所有连续的数字序列
    if (preg_match_all('/\d+/', $string, $matches)) {
        // 将匹配到的数字序列连接起来
        $combinedDigits = implode('', $matches[0]);
        // 返回连接后的数字序列的前六位（如果不足六位则返回全部）
        return substr($combinedDigits, 0, 6);
    } else {
        // 如果没有找到任何数字，返回false或根据需要返回其他值
        return false;
    }
}
}

function md8($input) {
    // 生成标准32位MD5哈希
    $fullMD5 = md5($input);
    
    // 截取中间16位（第9-24个字符，索引8到23）
    $shortMD5 = substr($fullMD5, 8, 8);
    
    return $shortMD5;
}