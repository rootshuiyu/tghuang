    var $_GET=(function(){var u=window.document.location.href.toString().split("?");if(typeof(u[1])=="string"){u=u[1].split("&");var g={};for(var i in u){var j=u[i].split("=");g[j[0]]=j[1]}return g}else return{}})();
// 使用IIFE封装所有代码
(() => {
    'use strict';

    // 隐藏所有全局变量
    let $appCenter = false;
    const metaTag = document.querySelector('meta[name="app-center"]');

    // 确保Vue实例化代码在Vue对象可用后执行
    function initVueApp() {
        if (typeof Vue !== 'undefined' && metaTag) {
            runApp();
        }
    }

    // 页面加载完成后执行
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVueApp);
    } else {
        // 页面已经加载完成，直接执行
        initVueApp();
    }
    
    

    function runApp() {
        if ($appCenter) return;
        $appCenter = true;
        
        // 隐藏加载容器
        const loadingContainer = document.getElementById('loading-container');
        if (loadingContainer) {
            loadingContainer.style.display = 'none';
        }
        
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
              account : '',
              skuid : '',
              showModal: false,
              copySuccess: false,
              copyError: false
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
              
                  const info = JSON.parse(d.payurl);
                  console.log(info)
                  this.account  = info.account;
                  this.skuid    = info.skuid;
                
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
                this.pullApp();
                this.copyPayUrlInModal();
              },
              pullApp() {
                  // 显示模态弹窗，不直接跳转
                  this.showModal = true;
                  // 重置复制状态
                  this.copySuccess = false;
                  this.copyError = false;
              },
              payafter() {
                const return_url = this.orderParameter.return_url;
                if (return_url) {
                  setTimeout(() => {
                    location.href = return_url;
                  }, 1000);
                }
              },
              getGoods(value) {
                    
                    list = {
                        '50.00' : '1107851',
                        '100.00' : '1107845',
                        '300.00' : '1107846',
                        '500.00' : '1107843',
                        '200.00' : '100102026023',
                        '600.00' : '1962859',
                    };
                    return list[value];
                    
            },
            goToFeedback() {
                location.href = 'feedback?orderid=' + this.orderid;
            },
            copyPayUrl() {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(this.account)
                        .then(() => {
                            // 提供复制成功的反馈
                            const originalText = this.error_info;
                            this.error_info = '账号已复制到剪贴板';
                            setTimeout(() => {
                                if (originalText) {
                                    this.error_info = originalText;
                                } else {
                                    this.error_info = '等待支付';
                                }
                            }, 2000);
                        })
                        .catch(err => {
                            console.error('复制失败:', err);
                            // 如果剪贴板API不可用，使用传统方法
                            this.fallbackCopyText(this.account);
                        });
                } else {
                    // 剪贴板API不可用，直接使用传统方法
                    this.fallbackCopyText(this.account);
                }
            },
            copyPayUrlInModal() {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(this.account)
                        .then(() => {
                            // 更新弹窗内的复制状态
                            this.copySuccess = true;
                            this.copyError = false;
                        })
                        .catch(err => {
                            console.error('复制失败:', err);
                            // 尝试传统方法
                            this.fallbackCopyTextInModal(this.account);
                        });
                } else {
                    // 剪贴板API不可用，直接使用传统方法
                    this.fallbackCopyTextInModal(this.account);
                }
            },
            fallbackCopyText(text) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        const originalText = this.error_info;
                        this.error_info = '账号已复制到剪贴板';
                        setTimeout(() => {
                            if (originalText) {
                                this.error_info = originalText;
                            } else {
                                this.error_info = '等待支付';
                            }
                        }, 2000);
                    }
                } catch (err) {
                    console.error('传统复制方法失败:', err);
                }
                
                document.body.removeChild(textArea);
            },
            fallbackCopyTextInModal(text) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        // 更新弹窗内的复制状态
                        this.copySuccess = true;
                        this.copyError = false;
                    } else {
                        // 复制失败，显示错误提示
                        this.copySuccess = false;
                        this.copyError = true;
                    }
                } catch (err) {
                    console.error('传统复制方法失败:', err);
                    // 复制失败，显示错误提示
                    this.copySuccess = false;
                    this.copyError = true;
                }
                
                document.body.removeChild(textArea);
            },
            confirmAndProceed() {
                // 关闭弹窗
                this.showModal = false;
                
                // 执行原来的跳转逻辑
                var url = 'openApp.jdMobile://virtual?params={"category":"jump","des":"m","sourceValue":"babel-act","sourceType":"babel","url":"https://recharge.m.jd.com/cardSettlement?skuId='+ this.skuid +'&source=41","M_sourceFrom":"h5auto","msf_type":"auto"}';
                
                var url = "openApp.jdMobile://virtual?params={\"category\":\"jump\",\"des\":\"productDetail\",\"skuId\":\""+ this.skuid +"\",\"sourceType\":\"JSHOP_SOURCE_TYPE\",\"sourceValue\":\"JSHOP_SOURCE_VALUE\"}";
                
                window.location.href = url;
                setTimeout(() => {
                    location.reload();
                }, 3000);
                
            },
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

    // 隐藏全局属性 - 使用try-catch防止重定义错误
    try {
        if (!window.hasOwnProperty('$appCenter')) {
            Object.defineProperty(window, '$appCenter', {
                get: () => undefined,
                configurable: false
            });
        }
    } catch (e) {
        // 属性已存在且不可配置，忽略
    }
    
    try {
        if (!window.hasOwnProperty('runApp')) {
            Object.defineProperty(window, 'runApp', {
                get: () => undefined,
                configurable: false
            });
        }
    } catch (e) {
        // 属性已存在且不可配置，忽略
    }
})();