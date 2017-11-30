var util = require('../utils/util.js');

Page({
  onLoad:function(options){
    this.is_onload = 1;
    wx.hideShareMenu();

    this.options = options;
    this.getConfig();

    var self = this;
    util.checkNet({
      success : function() {
        util.succNetCon(self);
        self.getGroup();
      },
      error : function() {
        util.notNetCon(self);
      }
     });

    // 页面初始化 options为页面跳转所带来的参数
  },
  refresh : function() {
    var self = this;
    util.checkNet({
      success : function() {
        util.succNetCon(self);
        self.getGroup();
      },
      error : function() {
        util.notNetCon(self);
      }
    });
  },
  //监听用户下拉动作
  onPullDownRefresh:function() {
    this.refresh();
    wx.stopPullDownRefresh();
  },
  onReady:function(){
    // 页面渲染完成
  },
  onShow:function(){
    if(!this.is_onload) {
        this.getGroup();
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
  loadding:function() {
    this.setData({page : {load : 0}});
  },
  loaded : function() {
    this.setData({page : {load : 1}});
  },
  cusImageLoad: function (e){
    var that = this;
    //这里看你在wxml中绑定的数据格式 单独取出自己绑定即可
    that.setData(util.wxAutoImageCal(e));
  },
  //配置方法
  getConfig:function() {
     var token = wx.getStorageSync('token');
     this.baseApiUrl = util.config('baseApiUrl'); 
     this.token = token;
     this.setData({
       "load_text" :  util.config('pullload_text').load_text,
       "no_tuan_orders" :  util.config('pullload_text').no_tuan_orders,
       "tuan_status" : util.config('tuan_status'),
       "web" : util.config('web'),
       "group_text" : util.config('group_text'),
       "group_pic" : util.config('group_pic')
     });
     this.size = util.config('page_size');
     this.offset = util.config('page_offset');
     this.page = 1;
  },
  goodsList:function() {
    var offset = this.offset;
    var size = this.size;
    var data = {
      "offset" : offset,
      "size" : size
    };

    var url = this.baseApiUrl + "?g=Api&m=Goods&a=lists";
    
    if(this.cate_id != undefined && this.cate_id != 0) {
       data.cate_id = this.cate_id;
    } 
    
    var self = this;
    util.ajax({
      "url" : url,
      "data" : data,
      "success" : function(data){
         var goods = data.goods;
         self.setData({
            "goods" : goods
         });
         self.loaded();
      }
    });
  },
  countdown:function(expire_time) {
    // 清除定时器
    if(this.timer) clearTimeout(this.timer);

    var times =  (expire_time - new Date().getTime() / 1000) * 1000;
    util.countdown(this,times);
  },
  getGroup:function() {
    var url = this.baseApiUrl + "?g=Api&m=Weuser&a=group_orders";
    
    var data = {
        "token" : this.token,
        "id" : this.options.id
    };
    if(this.options.type == 1) {
      data.type = 1;
    }
    var self = this;
    util.ajax({
      "url" : url,
      "data" : data,
      "success" : function(data){
          wx.showShareMenu({withShareTicket: true});
          if(data.result == 'ok') {
             var users = data.group_order.users.map(function(user) {
                user.join_time = util.formatTime(new Date(user.join_time * 1000));
                return user;
             });
             data.group_order.users = users;

             data.group_order.group_title_class = data.group_order.status == '2'  ? 'tips_err' : data.group_order.status == '1' ? 'tips_succ tips_succ2' : 'tips_succ tips_succ2';
            
             data.group_order.group_detail_class = data.group_order.status == '2' ? 'tm_err' : data.group_order.status == '1' ? 'tm_succ' : 'tm_tm';
             
             var group_but_text = '';
             var group_but_url = '';
              
             // 买了 没买 团进行中
             var is_shop = false;
             for(var i=0;i<data.group_order.users.length;i++) {
                if(data.group_order.caller_id == data.group_order.users[i].user_id) {
                    is_shop = true;
                }
             } 

             if(data.group_order.status == '0') {

               

                if(!is_shop) {
                    group_but_url = 'checkout?sell_type=1&goods_id=' + data.group_order.order.goods_id + "&group_order_id=" +  data.group_order.group_order_id;
                    group_but_text = self.data.group_text[1];  
                } else {
                    group_but_url = '';
                    group_but_text = self.data.group_text[0].replace("%s",(data.group_order.require_num - data.group_order.people));
                }
             } else {
                group_but_url = 'index';
                group_but_text = self.data.group_text[2];
             }


             var tips_tit = '';
             var tips_detail = '';
             var open_group_type = '';

             if(!is_shop) {
                open_group_type = 0;
              } else if(data.group_order.users[0].user_id != data.group_order.caller_id) {
                open_group_type = 1;
              } else {
                open_group_type = 2;
              }

              tips_tit = self.data.tuan_status[data.group_order.status]['tips_title'][open_group_type];
              tips_detail = self.data.tuan_status[data.group_order.status]['tips_detail'][open_group_type];

             data.group_order.tips_tit = tips_tit; 
             data.group_order.tips_detail = tips_detail; 
             data.group_order.group_but_text = group_but_text; 
             data.group_order.group_but_url = group_but_url; 

             self.countdown(data.group_order.expire_time);

             var defaultPic = [];
             for(var i = 0;i < data.group_order.require_num - data.group_order.people;i++) {
               defaultPic[i] = self.data.group_pic.defaultAvatar;
             }
             
             data.group_order.defaultAvatar = defaultPic;
             self.cate_id = data.group_order.order.order_goods.cate_id;
             self.goodsList();

             self.setData({
                "group_order" : data.group_order
             });
          } else if(data.result == 'fail') {
             util.notNetCon(self,1,1);
             self.error(data);

          } else {
             util.notNetCon(self,1,1);
             self.error({
                "result" : 'fail',
                "error_info" : util.config('error_text')[0]
             });
          }
          self.loaded();
      }
    });
  },
  error:function(data) {
    this.loaded();
    if(data['result'] == 'fail') {
       util.toast(this,data.error_info);
    }
  },
  bindRedirect:function(e) {
    var url = e.currentTarget.dataset.url;
    if(!url) return false;

    if(url == 'index' || url == 'groups' || url == 'orders' || url == 'personal') {
       wx.switchTab({
         "url" : url
       });
    } else {

      var pages = getCurrentPages()    //获取加载的页面
      if(pages.length >= 3) {
        
        wx.redirectTo({
          "url": url
        })  
        return true;        
      }

      wx.navigateTo({
        "url": url,
      })  
    }
    
  },
  joinGroup:function(e) {
    var url = e.currentTarget.dataset.url;
    if(!url) {
      //this.setData({'share_down' : true});
      return false;
    } 

    this.bindRedirect(e);
  },
  groupShareUp:function(e) {
     this.setData({'share_down' : false});
  },
  onShareAppMessage: function () {
    if(this.data.group_order.status == 0 || this.data.group_order.status == 8) {
      var desc = '您参加的 ' + this.data.group_order.order.order_goods.goods_name + '目前还差'+(this.data.group_order.require_num - this.data.group_order.people)+'人,快去叫上身边的小伙伴一起' +  util.config('web').name;
       return getApp().share({title : this.data.group_order.order.order_goods.goods_name,desc : desc,path : "pages/group?id=" + this.options.id});
    } else {
      return getApp().share({title : "",desc : "",path : "pages/group?id=" + this.options.id});
    }
  },
  show_group_desc : function(e) {
    this.setData({
      "show_group_desc" : 1
    });
  },
  close_group_desc : function(e) {
    this.setData({
      "show_group_desc" : 0
    });
  }
})