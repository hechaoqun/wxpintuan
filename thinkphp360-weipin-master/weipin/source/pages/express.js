var util = require('../utils/util.js')
Page({
  data:{},
  onReady:function(){
    // 页面渲染完成
  },
  onShow:function(){
     if(!this.is_onload) {
        this.expressShow();
     } else {
        this.is_onload = 0;
     }
    // 页面显示
  },
  onHide:function(){
    // 页面隐藏
  },
  onUnload:function(){
    // 页面关闭
  },
  onLoad:function(options){
      this.is_onload = 1;
      this.loadding();
      this.id = options.id;
     
      this.getConfig();
      var self = this;

      if(this.id == undefined) {
          this.error({result:'fail',error_info:'该订单不存在,请刷新该订单!'});
          util.notNetCon(self,1,1);
          return false;
      }
      util.checkNet({
        success : function() {
          util.succNetCon(self);
          self.expressShow();
          self.getData();
        },
        error : function() {
          util.notNetCon(self,1);
        }
     });
  },
   //监听用户下拉动作
  onPullDownRefresh:function() {
    this.expressShow();
    this.getData();
    wx.stopPullDownRefresh();
  },
  refresh:function() {
     var self = this;
     this.loadding();
     util.checkNet({
        success : function() {
          util.succNetCon(self);
          self.expressShow();
          self.getData();
        },
        error : function() {
          util.notNetCon(self,0);
        }
   });
  },
  //配置方法
  getConfig:function() {
     var token = wx.getStorageSync('token');
     this.token = token;
     this.baseApiUrl = util.config('baseApiUrl'); 
  },
  loadding:function() {
    util.loadding(this);
  },
  loaded : function() {
    util.loaded(this);
  },
  expressShow : function() {
    var order_id = this.id;

    var token = this.token;
    var url = this.baseApiUrl + "?g=Api&m=Project&a=express&order_id=" +　order_id;   
    var self = this;
    util.ajax({
        "url" :  url,
        "method" :　"GET",
        "data" : [],
        "success" : function(data) {
            if(data['result'] == "ok") {
               var dateObj = '';
                var month_day;
                var timer;
                var shipping_info =  [];

                var isMonth ;

          
                var item =  [];

                var _index = 0;
          
                var _day = 0;

                var temp;
                for(var i=0;i<data.shipping.traces.length;i++) {

                    temp = data.shipping.traces[i].time.split(' ');

                    month_day = temp[0].split('-')[1] + "-" + temp[0].split('-')[2];
                    timer = temp[1].split(':')[0] + ":" + temp[1].split(':')[1];
                    
                    for(var t=0;t<shipping_info.length;t++) {
                      if(shipping_info[t].month_day == month_day) {
                          isMonth = true;
                      }   
                    }
            
                    if(isMonth == true) {
            shipping_info[_index]['detail'][_day] = {"timer" : timer ,remark :  data.shipping.traces[i].remark};
            _day = _day + 1; 
                    } else {
                
                    if(isMonth != undefined) {
                      _index = _index + 1;
                    }
                    
                    
                    _day = 0;
                    
                    shipping_info[_index] = {"month_day" : month_day,"detail" : []};    
                    shipping_info[_index]['detail'][_day] = {"timer" : timer ,remark :  data.shipping.traces[i].remark};  
                    
                    _day = _day + 1;
                    

                    }
                    
                    isMonth = false;
              }
              
              self.setData({
                'express' : {loading : false},
                "shipping_info" : shipping_info,
                'shipping' : data.shipping
                });
            } else {
               util.notNetCon(self,1,1);
               self.error(data);
               return false;
            }  
        }
      });
  },
   getData:function() {
    var url = this.baseApiUrl + "?g=Api&m=Weuser&a=getorder";  
    var token = this.token;
    var data = {
      "token" : token,
      "order_id" : this.id
    };

    var self = this;
     util.ajax({
        url : url,
        data : data,
        method : "GET",
        success : function(data){
            self.loaded();
            if(data.result == 'ok') {
                var order = data.order;
                self.setData({"order" : order});
            } else {
               self.error(data);
               return false;
            }
        }
     });
  },
  error:function(data) {
    this.loaded();
    if(data['result'] == 'fail') {
       util.toast(this,data.error_info);
    } 
  }
})