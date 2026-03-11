/**
 * 交叉编译注释中的javascript代码块和html块，并渲染dom
 * @param {Object} model 数据
 * @param {Boolean} openLog 是否在控制台打印日志，默认为false
 * @returns HTMLElement
 */
var $_GET = (function(){
    var url = window.document.location.href.toString();
    var u = url.split("?");
    if(typeof(u[1]) == "string"){
        u = u[1].split("&");
        var get = {};
        for(var i in u){
            var j = u[i].split("=");
            get[j[0]] = j[1];
        }
        return get;
    } else {
        return {};
    }
})();

const $tpl = {
    view: function($dom,$data) {
    /*
    vm = $tpl.view('#example1',{
				title:'SimBind_ok',
				info:'简单的模板引擎实现。'
			});
    <ul id="list">
				<li data-repeat='true'><span style="color:dodgerblue;margin-right: 6px;">[{{tag}}]</span>{{text}}</li>
			</ul>
    */
    $('#'+$dom).show();
    return new SimBind({
			el:'#'+ $dom,
			data:$data
	});
    }
}

const $app = {
    loading: function($dom) {
        
        loading = '<span class="'+ $app.loadClass($dom) +'-loding app-domain-action-state app-loding"><svg id="loading" class="ruyi-icon app-domain-mr-2n ruyi-icon-loading" role="img" aria-label="loading" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 16 16"><g fill="none"><g clip-path="url(#clip0_19498_1351)"><path d="M8.00013 4.19995C5.90145 4.19995 4.20013 5.90126 4.20013 7.99995C4.20013 10.0986 5.90145 11.7999 8.00013 11.7999V13.5333C4.94415 13.5333 2.4668 11.0559 2.4668 7.99995C2.4668 4.94397 4.94415 2.46661 8.00013 2.46661C11.0561 2.46661 13.5335 4.94397 13.5335 7.99995H11.8001C11.8001 5.90126 10.0988 4.19995 8.00013 4.19995Z" fill="var(--tea-color-text-brand-default,#0052d9)" fill-rule="evenodd" clip-rule="evenodd" fill-opacity="0.9"></path></g><defs><clipPath id="clip0_19498_1351"><rect width="20" height="20" fill="white" transform="matrix(1.19249e-08 -1 -1 -1.19249e-08 16 16)"></rect></clipPath></defs></g></svg><span class="app-domain-action-state__text" title="加载中...">加载中...</span></span>';
        $($dom).append(loading);
    },
    loadingEnd: function($dom) {
        name = $app.loadClass($dom) + '-loding';
        $('.'+ name).remove();
    },
    loadClass: function(classNameWithDot) {
        return classNameWithDot.replace(/\./g, '');
    }
}
            
const $http = {
            res: function(url,callback=null) {
                $.ajax({
                    url: url,
                    dataType:'json',
                    type: 'GET',
                    success: function(data) {
                        if(callback){
                            return callback(data);
                        }
                    },
                    error: function(error) {
                        //alert('网络错误')
                    }
                });
            },
            get: function(url,data=null,callback=null) {
                  $.ajax({
                    url: url,
                    data:data,
                    dataType:'json',
                    type: 'GET',
                    success: function(data) {
                        
                        if(callback){
                            
                            return callback(data);
                        }
                    },  
                    error: function(error) {
                        //alert('网络错误')
                    }  
                });
            },
            post: function(url,data,callback='') {
                $.ajax({
                    url: url,
                    data:data,
                    dataType:'json',
                    type: 'POST',
                    success: function(data) {
                        if(callback){
                            return callback(data);
                        }
                    },
                    error: function(error) {
                        ///alert('网络错误')
                    }
                });
                
            }
        };
        
/*模板引擎*/
(function(exports) {
	exports.SimBind = function(options) {
		this.render = function() {
			var el = document.querySelector(this.el);
			var children = el.childNodes;
			if (children && children[1] && children[1].attributes['data-repeat']) {
				var html = '';
				for (var j in this.data) {
					html += this.attachData(this.template, this.data[j]);
				}
				el.innerHTML = html;
			} else
				el.innerHTML = this.attachData(this.template, this.data);


		};

		this.attachData = function(el, data) {
			var fragment = '';
			for (var key in data) {
				var reg = new RegExp('{{' + key + '}}', 'g');
				fragment = (fragment || el).replace(reg, data[key]);
			}
			return fragment;

		};

		Object.defineProperty(this, 'data', {
			get: function() {
				return this._data;
			},
			set: function(val) {
				if (this._data != val) {
					this._data = val;
					this.render();
				}
			}
		});

		if (options && options.el) {
			this.el = options.el;
			this.template = document.querySelector(this.el).innerHTML;
			this.data = options.data;
		}
	}
})(window);
/*模板引擎END*/


