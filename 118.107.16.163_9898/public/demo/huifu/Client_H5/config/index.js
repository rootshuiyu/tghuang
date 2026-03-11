// 说明：以下配置文件中xx只做演示，使用时请替换
var configObj = {
  mobile: {
    // 支付剩余时间倒计时,单位min
    payTime: 5,
    // 支付完成返回的页面地址，需要加协议，http/https
    backHomeUrl: '',
    // 微信支付相关配置
    wxConfig: {
      // 微信appid
      appid: '',
    },
  },
  pc: {
    // 支付剩余时间倒计时,单位min
    payTime: 5,
    // 支付完成返回的页面地址，需要加协议，http/https
    backHomeUrl: '',
  },
  // 汇付商户ID,作为获取奖励必传的字段
  huifuId: 'xxx',
  // 您自己的产品ID，作为获取奖励必传的字段
  productId: 'xxx',
  // 渠道商id,作为获取奖励必传的字段
  sysId: 'xxx',
  // 测试环境h5是否开启调试模式
  isDebug: true,
  // 接口名称集合
  api: {
    // 下单
    createPay: 'xx',
    // 查询下单
    queryPay: 'xx',
    // 获取微信openId,
    wxAuth: 'xx',
    // 获取银联userId,
    unionpayAuth: 'xx',
  },
  // 测试环境域名
  testEnvHost: 'xx.xx.xx',
  // 测试API前缀：
  testBaseURL: 'https://xx.xx/xx',
  // 生产环境域名
  productionEnvHost: 'xx.xx.xxx',
  // 生产API前缀
  prodBaseURL: 'https://xx.xx.com/xx',
  // 下单成功标识生产API，必须调用此接口上传数据成功，才可获得对应的奖励
  prodUploadPayAPI: 'https://api.huifu.com/v2/trade/casherinsert',
};
