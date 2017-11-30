var util = require('../utils/util.js')
var app = getApp();
Page({
  data:{
      "URL" : 4,
      "userInfo" : util.config('userInfo')
    // text:"这是一个页面"
  },
  onLoad:function(options){
     var token = wx.getStorageSync('token');
     this.me(token);

    // 页面初始化 options为页面跳转所带来的参数
  },
  onReady:function(){
    // 页面渲染完成
  },
  onShow:function() {
     var token = wx.getStorageSync('token');
     this.getOrderCount(token);
  },
  onHide:function(){
    // 页面隐藏
  },
  onUnload:function(){
    // 页面关闭
  },
  me:function(token){
     util.loadding(this,1);
     this.baseApiUrl = util.config('baseApiUrl');
     var self = this;
     if(token) {
        var data = {
           "token" : token
        };
        var url = this.baseApiUrl + "?g=Api&m=Weuser&a=me";
        util.ajax({
          "url" :  url,
          "data" : data,
          "success" : function(data) {
              util.loaded(self);
              if(data['result'] == "ok") {
                  self.getOrderCount(token);
                  self.setData({
                    "userInfo" : {
                      "avatarUrl" : data.user_info.headimgurl,
                      "nickName" : data.user_info.nickname
                    }
                  });  
                  //self.setData({loaded:true});
              } else {
                //console.log('token');
                //wx.removeStorageSync('token');  
                //self.login();
              }
          }
        });
     } else {
       //self.login();
     }
  },
  // //清除緩存
  // refreshLogin:function(){
  //    this.clearCache();
  //    this.setData({"userInfo" : util.config('userInfo')});
  //    wx.removeStorageSync('token');
  //    this.me();
  // },
  clearCache:function(){
    wx.clearStorageSync();
  },
  login:function(){
    var self = this;
    var code = '';
    var token = wx.getStorageSync('token');
    getApp().login(token);
    
    self.me(token);
  },
   error:function(data) {
    this.setData({page : {load : 1}});
    if(data['result'] == 'fail') {
       util.toast(this,data.error_info);
    }
  },
  switch2Change: function (e){
    //console.log('switch1 发生 change 事件，携带值为', e.detail.value)
  },
  // onShareAppMessage: function () {
  //   return getApp().share({title : '',desc : '',path : ''});
  // },
  getOrderCount: function(token) {
     this.baseApiUrl = util.config('baseApiUrl');
     var self = this;
    
     var data = {
        "token" : token
     };
     var url = this.baseApiUrl + "?g=Api&m=Weuser&a=orders_count";
     util.ajax({
        "url" :  url,
        "data" : data,
        "success" : function(data) {
            if(data['result'] == "ok") {
               self.setData({
                 "unpaid" : data.unpaid,
                 "unreceived" : data.unreceived
               });              
            } else {
                //console.log('token');
                wx.removeStorageSync('token');  
                self.login();
            }
            
        }
      });  
  },
  scroll : function(data) {
    
  },
  toOrder : function(e) {
    wx.setStorageSync('order_type',e.currentTarget.dataset.type);  
    wx.switchTab({url : "orders"});
  },
  toGroup : function(e) {
    wx.setStorageSync('groups_type',1);  
    wx.switchTab({url : "groups"});
  }
})