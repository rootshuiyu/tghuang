<?php

namespace app\admin\controller\yunos;

use app\common\controller\Backend;
use app\admin\model\yunos\Access;

/**
 * 收银台地址（咪咕对标）
 * 集中展示当前管理员可用的支付链接
 */
class Cashier extends Backend
{
    protected $noNeedRight = ['index'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 收银台地址列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $where = [];
            if (!$this->is_boss) {
                $where['switch'] = 1;
            }
            $list = Access::where($where)->order('id', 'desc')->select();
            $payUrl = config('site.pay_url');
            foreach ($list as &$row) {
                $row['pay_link'] = $payUrl . (new Access)->url($this->auth->id, $row['id']);
                $row['pay_type_text'] = isset([
                    'qqpay' => 'QQ支付',
                    'wxpay' => '微信支付',
                    'alipay' => '支付宝支付',
                ][$row['pay_type']]) ? [
                    'qqpay' => 'QQ支付',
                    'wxpay' => '微信支付',
                    'alipay' => '支付宝支付',
                ][$row['pay_type']] : $row['pay_type'];
            }
            $result = ['total' => count($list), 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
}
