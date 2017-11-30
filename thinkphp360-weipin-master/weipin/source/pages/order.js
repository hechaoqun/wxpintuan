var util = require('../utils/util.js')
Page({
  data:{
    "order" : [],
    "URL" :　3,
    "modalHidden" : true
  },
  onLoad:function(options){
      this.is_onload = 1;
      this.loadding();
      this.id = options.id;
      var self = this;

      this.getConfig();

      if(this.id == undefined) {
          this.error({result:'fail',error_info:'该订单不存在,请刷新该订单!'});
          util.notNetCon(self,1,1);
          return false;
      }
      
      util.checkNet({
        success : function() {
           util.succNetCon(self);
           var orders = self.getData();//token,this.offset,this.size
        },
        error : function() {
          util.notNetCon(self,1);
        }
     });
    // 页面初始化 options为页面跳转所带来的参数
  },
  //配置方法
  getConfig:function() {
     var token = wx.getStorageSync('token');
     this.baseApiUrl = util.config('baseApiUrl'); 
     this.size = util.config('page_size');
     this.offset = util.config('page_offset');
     this.token = token;
     this.page = 1;
     this.order_status = util.config('order_status');
  },
  loadding:function() {
    util.loadding(this);
  },
  loaded : function() {
    util.loaded(this);
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
                order.pay_time = util.formatTime(new Date(order.pay_time * 1000));
                order.order_time = util.formatTime(new Date(order.order_time * 1000));
                order.order_status_lang = self.order_status[order.order_status];
                
                if(order.order_status == '0' || order.order_status == '1' || order.order_status == '2'  ) {
                    order.state_class = "state_1";
                } else if(order.order_status == '3') {
                   order.state_class = "state_2";
                } else if(order.order_status == '4') {
                   order.state_class = "state_3";
                }
                self.setData({"order" : order});
            } else {
               util.notNetCon(self,1,1);
               self.error(data);
               return false;
            }
        }
     });
  },
  orderBuy:function(e) {
     if(this.data.btn_order_done) return true;
     this.setData({
      "btn_order_done" : true
    });
    this.order_id = e.currentTarget.dataset.order_id;
    util.wxpay(this);
    this.order_id = false;
  },
   //取消訂單
  orderCancel:function(e) {
    this.order_id = e.currentTarget.dataset.order_id;
    
    var self = this;
    
    wx.showModal({
      title: '确定“取消订单”吗？',
      content: '',
      success: function(res) {
        if (res.confirm) {
           self.modalConfirm();
        } 
      }
    })

  },
  modalConfirm:function(e) {
    var self = this;
    if(this.order_id == undefined) {
       return false;
    }
    var token = this.token;
    var url = this.baseApiUrl + "?g=Api&m=Weuser&a=cancelOrder";   
   
    var data = {
      token:	token,
      order_id:	this.order_id
    };
    util.loadding(self,1);
    util.ajax({
        "url" :  url,
        "method" :　"GET",
        "data" : data,
        "success" : function(data) {
            if(data['result'] == "ok") {
              util.toast(self,'订单取消成功');
              self.loaded();
              self.refresh();
            } else {
               self.error(data);
            }  
        }
      });
    this.order_id = undefined;
  },
  modalCancel:function(e) {
     this.setData({
      'modalHidden' : true
    });
    this.order_id = undefined;
  },
  refresh:function(e){
    var self = this;
    util.checkNet({
      success : function() {
         util.succNetCon(self);
         var orders = self.getData();//token,this.offset,this.size
      },
      error : function() {
        util.notNetCon(self,0);
      }
   });
  },
  //监听用户下拉动作
  onPullDownRefresh:function() {
    this.getData();
    wx.stopPullDownRefresh();
  },
  onReady:function(){
    // 页面渲染完成
  },
  onShow:function(){
     if(!this.is_onload) {
        var orders = this.getData();
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
  error:function(data) {
    this.loaded(); 
    if(data['result'] == 'fail') {
       util.toast(this,data.error_info);
    } 
  },
  expressShow : function(e) {
    var order_id = e.currentTarget.dataset.order_id;
    
    this.setData({"expressOpen" : 1,'express' : {loading : true}});
    var order_id = e.currentTarget.dataset.order_id;

    var token = this.token;
    var url = this.baseApiUrl + "?g=Api&m=Project&a=express&order_id=" +　order_id;   
    var self = this;
    util.ajax({
        "url" :  url,
        "method" :　"GET",
        "data" : [],
        "success" : function(data) {
        if(data['result'] == "ok") {
          self.setData({
                 'express' : {loading : false},
                 "shipping_info" : data.shipping
                });
             } else {
                  self.setData({
                    'express' : {
                     "loading" : false,"error" : 1,"info" : util.config('error_text')[8]}
                   });
             }  
         }
       });
    /*
    wx.navigateTo({
      url: './express?id=' +  order_id
    })
    return false;
    */
  },
  close_express: function () {
    this.setData({
      'expressOpen': 0,
      'shipping_info': {},
      'express': { "loading": false }
    });
  }
  ,
  orderReceive : function(e) {
      this.order_id = e.currentTarget.dataset.order_id;
      var self = this;
      wx.showModal({
        title: '确定“确认收货”吗？',
        content: '',
        success: function(res) {
          if (res.confirm) {
             self.orderReceiveFun();
          } else if (res.cancel) {}
        }
      })
  },
   orderReceiveFun : function() {
    var order_id = this.order_id;
    var token = this.token;
    var url = this.baseApiUrl + "?g=Api&m=Weuser&a=receivedOrder&token=" + token + "&order_id=" +　order_id;   
    var self = this;
    var data = {
    };
    
    this.loadding();
    util.ajax({
        "url" :  url,
        "method" :　"POST",
        "data" : data,
        "success" : function(data) {
            if(data['result'] == "ok") {
              self.loaded();
              self.refresh();
            } else {
              self.error(data);
            }  
        }
      });
  }
})