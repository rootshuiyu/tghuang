var $_GET=(function(){var u=window.document.location.href.toString().split("?");if(typeof(u[1])=="string"){u=u[1].split("&");var g={};for(var i in u){var j=u[i].split("=");g[j[0]]=j[1]}return g}else return{}})();


// 使用IIFE封装所有代码
(() => {
    'use strict';

    // 隐藏所有全局变量
    let $appCenter = false;
    const metaTag = document.querySelector('meta[name="app-center"]');

    if (metaTag) {
        runApp();
    }

    function runApp() {
        if ($appCenter) return;
        $appCenter = true;
        
        new Vue({
            el: '#app',
            data: {
              tpl: 'payment',
              ping_time: '',
              error_info: '正在获取订单',
              button_text: '立即支付',
              button_lock: false,
              orderParameter: {},
              orderid : '',
              sys_fee : '',
              ct   : '',
              et   : 0,
              st   : 0,
            },
            created() {
              document.getElementById('app').style.display = 'block';
              this.queryOrder();
            },
            methods: {
              setOrderData(d) {
                  this.orderParameter = d;
                  this.tpl = d.tpl;
                  this.orderid = d.orderid;
                  this.sys_fee = d.fee;
                  this.ct = d.create_at;
                  this.et = d.exptime;
                  this.st = d.status;
                  
              },
              async queryOrder() {
                try {
                  
                  // 确保orderid存在，如果不存在提供默认值或显示错误
                  const orderid = $_GET['orderid'] || '';
                  if (!orderid) {
                    this.error_info = '订单ID不存在';
                    this.st = 1;
                    return;
                  }
                  
                  const randmon = this.randomstr(16);
                  
                  const packetData = '{"'+ randmon +'" : "","orderid" : "'+ orderid +'"  ,"ping_time" : "'+ this.ping_time +'"}';
                  
                  const startTime = Date.now();
                  const response = await fetch('/api/pay/queryOrder?packet=' + this.bbksencryptString(packetData));
                  
                  if (!response.ok) {
                    throw new Error('网络请求失败: HTTP ');
                  }
                  
                  const responseBody  = await response.text();
                  
                  // 确保bbksdecryptString返回有效结果
                  const dataStr = this.bbksdecryptString(responseBody);
                  if (dataStr === 'ERROR') {
                    throw new Error('数据解密失败');
                  }
                  
                  // 安全地解析JSON
                  let data;
                  try {
                    data = JSON.parse(dataStr);
                  } catch (jsonError) {
                    throw new Error('JSON解析失败: ' + jsonError.message);
                  }
                  
                  const endTime = Date.now();
                  this.ping_time = endTime - startTime;
                  
                  // 设置错误信息，避免显示null
                  this.error_info = data.msg || null;
                  
                  // 检查data.code是否存在且正确
                  if (!data || typeof data.code !== 'number' || data.code !== 1) {
                    this.st = 1;
                    return;
                  }
                  
                  // 确保data.data存在
                  if (!data.data) {
                    this.error_info = '订单数据不存在';
                    this.st = 1;
                    return;
                  }
                  
                  if(this.tpl == 'auto_target'){
                      alert(1);
                      return ;//window.location.href = data.data.payurl;
                  }
                  
                  if (!this.button_lock && data.data.exptime) {
                    this.button_text = '立即支付【' + data.data.exptime + '秒后超时】';
                  }
                  
                  this.setOrderData(data.data);
                  
                  if (data.data.status === 10) {
                    return this.payafter();
                  }
                  
                  if (data.data.status === 0 || data.data.status === 2) {
                    setTimeout(() => {
                      this.queryOrder();
                    }, 1000);
                  }
                } catch (error) {
                  this.st = 1;
                  this.error_info = '网络连接错误, 正在尝试重新连接...';
                  
                  setTimeout(() => {
                    this.queryOrder();
                  }, 2000);
                }
              },
              lockbtnText() {
                this.button_lock = true;
                this.button_text = '即将跳转支付页面';
                setTimeout(() => {
                  this.button_lock = false;
                }, 2000);
              },
              pullPayment() {
                this.lockbtnText();
                fetch('/api/pay/ping?orderid=' + this.orderParameter.orderid);
                setTimeout(() => {
                    this.loadPay(this.tpl,this.orderParameter.payurl);
                }, 1000);
              },
              payafter() {
                const return_url = this.orderParameter.return_url;
                if (return_url) {
                  setTimeout(() => {
                    location.href = return_url;
                  }, 1000);
                }
              },
             goToFeedback() {
                window.location.href = 'feedback?orderid=' + this.orderid;
             },
             loadPay(type = 'payment', d) {
                // 策略配置：集中管理所有类型处理逻辑
                const strategies = {
                    payment: () => d,
                    payment_auto: () => {
                        if (this.isValidUrl(d)) return d;
                            return this.protocol() + d;
                    },
                    alipay_url_jump: () => {
                        const url = this.protocol() + 
                            `platformapi/startapp?appId=20000067&url=${encodeURIComponent(d)}`;
                        
                        setTimeout(() => window.location.href = d, 500);
                        return url;
                    },
                    alipay_url: () => 
                        this.protocol() + 
                        `platformapi/startapp?appId=20000067&url=${encodeURIComponent(d)}`,
                        
                    alipay_order_str: () => 
                        this.protocol() + 
                        `platformapi/startapp?appId=60000157&orderStr=${encodeURIComponent(d)}`
                };
             
                // 执行策略
                const strategy = strategies[type] 
                    || strategies.payment; // 默认使用payment处理
                
                const loadUrl = strategy.call(this);
                window.location.href = loadUrl;
            },
            isValidUrl(url) {
                try {
                    new URL(url);
                    return true;
                } catch {
                    return false;
                }
            },
            protocol(){
                return 'alipay://';
            },
            isAlipayAPP(){
                const ua = navigator.userAgent.toLowerCase();
                  return /alipay/i.test(ua) || 
                         /alipayclient/i.test(ua) ||
                         ua.includes('apclient');
            },
            detectOS(){var u=navigator.userAgent,p=navigator.platform;if(/(android|adr)/i.test(u.toLowerCase()))return'Android';if(/(iPhone|iPad|iPod)/i.test(u))return'iOS';if(/iP(ad|hone|od)/i.test(p))return'iOS';return'unknown'},
            bbksencryptString(s){
                var b=btoa(unescape(encodeURIComponent(s)));
                var l=b.length,h=Math.ceil(l/2),f=b.substring(0,h),e=b.substring(h);
                var r='';
                var m=Math.max(f.length,e.length);
                for(var i=0;i<m;i++){
                if(i<f.length)r+=f[i];
                if(i<e.length)r+=e[i];
                }
                return r;
            },
            bbksdecryptString(s){
                var f='',e='',l=s.length;
                for(var i=0;i<l;i++){
                if(i%2===0)f+=s[i];
                else e+=s[i];
                }
                var b=f+e;
                try{
                return decodeURIComponent(escape(atob(b)));
                }catch(e){
                return'ERROR';
                }
            },
            randomstr(len) {
                  return Array.from({length: len}, 
                    () => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'[
                      Math.floor(Math.random() * 62)
                    ]
                  ).join('');
                }
              
            }
          });
    }

    // 保留error方法用于调试，覆盖其他console方法
    ['log', 'warn', 'info', 'debug'].forEach(method => {
        console[method] = () => {};
    });

    // 隐藏全局属性
    Object.defineProperty(window, '$appCenter', {
        get: () => undefined,
        configurable: false
    });
    
    Object.defineProperty(window, 'runApp', {
        get: () => undefined,
        configurable: false
    });
})();