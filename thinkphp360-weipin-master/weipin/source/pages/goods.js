var util = require('../utils/util.js');
var WxParse = require('../utils/wxParse/wxParse.js');
const ImgLoader = require('../utils/img-loader/img-loader.js')

Page({
  data:{
    indicatorDots: false,
    autoplay: false,
    interval: 3500,
    duration: 800,
    loaded: false,
    "current" : 1
  },
  onLoad:function(options){
     this.is_onload = 1;
     wx.hideShareMenu();
     this.goods_id = options.goods_id;
    //  wx.showNavigationBarLoading();
     this.baseApiUrl = util.config('baseApiUrl'); 

     var self = this;
     util.checkNet({
       success : function() {
          util.succNetCon(self);
          self.goodsDetail(options.goods_id);
          self.goodsGroups(options.goods_id);
       },
       error : function() {
          util.notNetCon(self);
       }
     });

     // 页面初始化 options为页面跳转所带来的参数
     this.imgLoader = new ImgLoader(this)
     
     //this.doneOrderBanner();
      //console.log(options);
    // 页面初始化 options为页面跳转所带来的参数
  },
  refresh : function() {
     var self = this;
     util.checkNet({
       success : function() {
          util.succNetCon(self);//恢复网络访问
          self.goodsDetail(self.goods_id);
          self.goodsGroups(self.goods_id);
       },
       error : function() {
          util.notNetCon(self,0);
       }
     });
  },
  bindchange:function(e) {
    var current = e.detail.current + 1;
    this.setData({current : current});
  },
  onReady:function(){
    
    // 页面渲染完成
  },
  onShow:function(){  
     if(!this.is_onload) {
        this.goodsGroups(this.goods_id);
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
   //监听用户下拉动作
  onPullDownRefresh:function() {
    util.loadding(this,1);
    this.goodsDetail(this.goods_id);
    this.goodsGroups(this.goods_id);
    wx.stopPullDownRefresh();
  },
  goodsDetail: function (goods_id, obj = {}){

     
     var url = this.baseApiUrl + "?g=Api&m=Goods&a=detail&goods_id=" + goods_id;
     var self = this;
     util.ajax({
        url : url,
        success : function(data){
            if(data.result == 'ok') {
              self.setData({
                goods : data.goods,
                isShow_out: 0 >= parseInt(data.goods.goods_stock),
                gallery : data.gallery,
                wxParseData : WxParse.wxParse('goods_desc', 'html', data.goods.goods_desc, self, 0)
              });

              var goods_image_cache = wx.getStorageSync('goods_banner_' + data.goods.goods_id + "_windowHeight_" + self.data.windowHeight + "_windowWidth_" + self.data.windowWidth);
              if (goods_image_cache) {
                self.setData({ 'goods_banner': { "width": goods_image_cache.width + "px", "height": goods_image_cache.height + "px", "onload": 1 }, });
                util.loaded(self);

                obj.success != undefined ? obj.success() : '';
              } else {
                util.loadding(self, 1);
                self.imgLoader.load(data.gallery[0].img_url, function (err, imgs) {
                  var goods_banner = util.wxAutoImageCal(imgs.ev);
                  self.setData({ 'goods_banner': { "width": goods_banner.imageWidth + "px", "height": goods_banner.imageheight + "px", "onload": 1 }, });
                  wx.setStorageSync('goods_banner_' + data.goods.goods_id + "_windowHeight_" + self.data.windowHeight + "_windowWidth_" + self.data.windowWidth, { width: goods_banner.imageWidth, height: goods_banner.imageheight });

                  util.loaded(self);

                  obj.success != undefined ? obj.success() : '';
                });
              }
                
              wx.showShareMenu({withShareTicket: true});
              
            } else {
              util.notNetCon(self,1,1);
              self.error(data);
            }
        }
     });
  },
  goodsGroups:function(goods_id) {
    var url = this.baseApiUrl + "?g=Api&m=Goods&a=groups&goods_id=" + goods_id;
    var self = this;

    var data = [];
    data['offset'] = 0;
    data['size'] = 5;
    util.ajax({
       url : url,
       data : data,
       success : function(data) {
          if(data.result == 'ok') {

            var times = [];
            var i = 0
            
            var goods_groups = data.goods_groups.map(function(group) {
            // 清除定时器
              times[i++] =  (group.expire_time - new Date().getTime() / 1000) * 1000;
            });
            self.setData({'groups' : data});

            if(self.timer) clearTimeout(self.timer);
            util.countdowns(self,times);
          } else {
            self.error(data);
          }
       }
    });
  }
  ,
 loadding:function() {
    util.loadding(this);
    //this.setData({page : {load : 0}});
 },
 loaded : function() {
    util.loaded(this);
    //this.setData({page : {load : 1}});
 },
  //错误处理函数
  error:function(data) {
    this.loaded();
    if(data['result'] == 'fail') {
       util.toast(this,data.error_info);
    }
  },
  cusImageLoad: function (e){
    var that = this;
    console.log(util.wxAutoImageCal(e));
    that.setData(util.wxAutoImageCal(e));
  },
  onShareAppMessage: function () {
    return getApp().share({title : this.data.goods.goods_name,path : "pages/goods?goods_id=" + this.goods_id});
  },
  toIndex:function(e) {
    wx.switchTab({
      url: './index'
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
  },
  show_service_detail : function(e) {
     this.setData({
      "service_detail" : 1
    });
  },
  close_service_detail : function(e) {
    this.setData({
      "service_detail" : 0
    });
  },
  show_group_list : function(e) {
     this.setData({
        "show_group_list" : 1
    });
  },
  close_group_lists : function(e) {
     this.setData({
        "show_group_list" : 0
    });
  },
  previewImage : function(e) {
    var idx = e.currentTarget.dataset.idx;
    var gallery = [];
    
    for(var i=0;i<this.data.gallery.length;i++) {
      gallery[i] = this.data.gallery[i].img_url;
    }
    
    wx.previewImage({
      current: gallery[idx], 
      urls: gallery 
    })
  },
})