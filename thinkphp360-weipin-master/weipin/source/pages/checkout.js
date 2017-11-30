var util = require('../utils/util.js')
Page({
  data:{
    "btn_order_done" : false,
    "error" : {
       "result" : '',
       "error_info" : ''
    },
    'order_tixinged' : 0
    // text:"这是一个页面"
  },
  onLoad:function(options){
    this.is_onload = 1;
	  util.loadding(this,1);
    this.sell_type = options.sell_type;
    this.goods_id = options.goods_id;
    this.address_id = options.address_id;

    //团购订单
    if(options.group_order_id != undefined) {
      this.group_order_id = options.group_order_id;
    }
    this.token = wx.getStorageSync('token'); 
    this.baseApiUrl = util.config('baseApiUrl');
  
    this.setData({
      sell_type : this.sell_type,
      goods_id :　this.goods_id
    });

    var self = this;
    setTimeout(function(){
      self.setData({'order_tixinged' : 1});
    }
    ,2500);

    this.doneOrderBanner();
    this.address();
    this.goodsDetail();
     
    //console.log(options);
    // 页面初始化 options为页面跳转所带来的参数
  },
   refresh : function() {
     var self = this;
     util.checkNet({
       success : function() {
          util.succNetCon(self);//恢复网络访问
          self.address();
          self.goodsDetail();
       },
       error : function() {
          util.notNetCon(self,0);
       }
     });
  },
  //跳转地址页面
  redirectAddresses:function() {
    
    if (this.data.address != undefined && this.data.address.address_id != undefined && this.data.address.address_id > 0) {
      var url = "addresses?sell_type=" + this.sell_type + "&goods_id=" + this.goods_id; 
      url += " &address_id=" + this.data.address.address_id;
    } else {
      var url = "address?goods_id=" + this.goods_id + "&sell_type=" + this.sell_type;
    }
       

    //是否生成订单    
    if (!this.order_id) {
      util.redirect(url, 1);
    } else {
      util.toast(this, "订单已生成，信息不可更改");
    }
  },
  doneOrderBanner : function() {
    var url = this.baseApiUrl + "?g=Api&m=Project&a=do_order_banner";
    var self = this;
    util.ajax({
      url : url,
      success : function(data) {
        self.setData({'shippingBanner' : data.shipping});
      }
    });
  },
  order_tixinged:function(e) {
    this.setData({'order_tixinged' : 1});
  },
  onReady:function(){
    // 页面渲染完成
  },
  onShow:function(){
    if(!this.is_onload) {
      this.address_id = wx.getStorageSync('select_address_id');
      this.address();
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
  btnOrderDone:function(e){
    if(!this.data.address) return false;
    if(this.data.btn_order_done) return true;
    var self = this;
    this.setData({
      "btn_order_done" : true
    });


    if(this.data.order_id) {
        self.order_id = this.data.order_id;
        return util.wxpay(self);
    }

    var url = this.baseApiUrl + "?g=Api&m=Weuser&a=orders&token=" + this.token;

    //默认是单独购买
    var data = {
      "goods_id" : this.goods_id,
      "address_id" : this.address_id,
      "groupbuy" : this.sell_type == 1 ? 1 : 0,
      "group_order_id" : this.group_order_id ? this.group_order_id : 0
    };

    util.ajax({
        "url" :  url,
        "method" :　"POST",
        "data" : data,
        "success" : function(data) {
            if(data['result'] == "ok") {
                //服务端生成订单成功
                //  self.setData({
                //   "btn_order_done" : false
                // });
                //微信支付
                self.order_id = data.order_id;
                util.wxpay(self);
                self.setData({"order_id" : self.order_id});
                //self.wxpay();
            } else if(data['result'] == "fail") {

              self.setData({"order_id" : 0});

              util.toast(self,data.error_info);
            } else {

              self.setData({"order_id" : 0});

              util.toast(self,util.config('error_text')[0]);
            }
        }
      });
  },
  error:function(data) {
    util.loaded(this);
    if(data['result'] == 'fail') {
       util.toast(this,data.error_info);
    }
  },
 
  address:function(){{
      var self = this;
      var pra = '';
      if(this.address_id != undefined) {
        var pra = "&address_id=" + this.address_id;
      }
      var url = this.baseApiUrl + "?g=Api&m=Weuser&a=addresses&token=" + this.token + pra;
      util.ajax({
          "url" :  url,
          "method" :　"GET",
          "success" : function(data) {
              if(data['result'] == "ok") {
                  self.setData({loaded:true});
                  if(data.address_list.length > 0) {
                    var address = self.addressSort(data.address_list,self.address_id);
                     self.setData({
                        "address" : address
                    });  

                    //清除缓存
                    wx.removeStorageSync('select_address_id');
                  } else if(self.data.address)  {
                      self.setData({
                         "address" : '',
                      });
                      self.address_id = '';
                  }

                  if(address) {
                    self.address_id = address.address_id;
                  }
              } else {
                self.error(data);
              }
          }
        });
   }},
  addressSort:function(list,address_id) {
    var address;
    if(list.length == 1) {
      return list[0];
    }
    if(list.length <= 0) {
      return null;
    }
    
    for (var i in list) {
      if(undefined != address_id) {
         if(list[i]['address_id'] == address_id) {
            address = list[i];
         }
      } else if(list[i]['status'] == "DEFAULT") {
          address = list[i];
      } else if(i >= list.length - 1) {
          address = list[0];
      }
    }
    return address;
  },

  goodsDetail:function(){
     var url = this.baseApiUrl + "?g=Api&m=Goods&a=detail&goods_id=" + this.goods_id;
     var self = this;
     util.ajax({
        url : url,
        success : function(data){
			     util.loaded(self);
            if(data.result == 'ok') {
              self.setData({
                goods : data.goods,
                gallery : data.gallery
              });
            } else {
              util.notNetCon(self,1,1);
              self.error(data);
            } 
        } 
     });
  },
  errorGo: function(e) {
    wx.redirectTo({
      url: "index"
    })
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