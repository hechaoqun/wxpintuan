var util = require('../utils/util.js');

Page({
  data:{
      "URL" : 2
    // text:"这是一个页面"
  },
  onLoad:function(options){
     this.is_onload = 1;
     this.getConfig();

     var self = this;
     util.checkNet({
        success : function() {
        util.succNetCon(self);
      
        self.getGroups();
      },
      error : function() {
        util.notNetCon(self);
      }
    });
     

    // 页面初始化 options为页面跳转所带来的参数
  },
  onReady:function(){
    // 页面渲染完成
  },
  onShow:function(){
     wx.getSystemInfo( {
      success: ( res ) => {
        this.setData( {
          windowHeight: res.windowHeight,
          windowWidth: res.windowWidth
        })
      }
    })

    if(!this.is_onload) {
      if( wx.getStorageSync('groups_type') != '') {
        if(wx.getStorageSync('groups_type') == '1') {
          this.refresh(1);
        }
        wx.removeStorageSync('groups_type');  
      } else {
        this.refresh();
      }
    } else {
      this.is_onload = 0;
    }
    // 页面显示
  },
  //初始化数据
  refresh:function(isclear=0) {
      
     var self = this;
     util.checkNet({
        success : function() {
        util.succNetCon(self);

        if(isclear) {
          self.setData({'is_over' : 0,'no_data' : 0,'pullDown' : 0});
          self.loadding();   
          self.setData({"groups" : []});
          self.page = 1;
          self.getGroups();
        } else {
          //如果是第一页就刷新数据
          if(self.page == 1) {
            self.setData({'is_over' : 0,'no_data' : 0,'pullDown' : 0});
            self.getGroups(1);
          }
        }
        
        self.group_order_id = undefined;
      },
      error : function() {
        util.notNetCon(self,0);
      }
    }); 

  },  
  onHide:function(){
    // 页面隐藏
  },
  onUnload:function(){
    // 页面关闭
  },
  toDetail:function(e){
    var url = e.currentTarget.dataset.url + "?id=" + e.currentTarget.dataset.id;
    wx.navigateTo({
      url: url
    });
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
     this.size = util.config('page_size');
     this.offset = util.config('page_offset');
     this.page = 1;
     this.token = token;
     this.setData({
       "pullload_text" :  util.config('pullload_text'),
       "tuan_status" : util.config('tuan_status')
     });
  },
  pullDown: function( e ) {
    this.setData({"pullDown" : 1});
    //数据没有加载成功
    if(!this.data.is_over) {
      this.page = this.page + 1;  
      this.getGroups();
    }
  },
  pullUpLoad: function(e) {
  },
  getGroups:function(isclear=0) {
    if(this.data.no_data) return true;
    var offset = (this.page - 1) * this.size;
    var size = this.size;
    var token = this.token;
    var data = {
      "offset" : offset,
      "size" : size,
      "token" : token
    };
    
    var url = this.baseApiUrl + "?g=Api&m=Weuser&a=groups";
    
    var self = this;
    util.ajax({
      "url" : url,
      "data" : data,
      "success" : function(data){
         var allData = '';
         var agoData = isclear ? false : self.data.groups;
         
         var groups = data.group_orders;
        if(data.group_orders.length != 0) {
            if(agoData) {
              allData = agoData;
              groups.map(function(group) {
                allData.push(group);
              });
            } else {
              allData = groups;
            }
            self.setData({loaded:true});
            self.setData({
              "groups" : allData
            });

            
            if(data.group_orders.length < self.size) {
              self.setData({
                "is_over" : 1,
                "no_data" : 1
              });
            }
        }  else {
          if(self.data.groups == false || isclear) self.setData({groups : []});
          self.setData({
            "is_over" : 1,
            "no_data" : 1
          });
         
        }    
       self.loaded();
      }
    });
  },
  loadding:function() {
    this.setData({page : {load : 0}});
  },
  loaded : function() {
    this.setData({page : {load : 1}});
  },
  error:function(data) {
    this.setData({page : {load : 1}});
    if(data['result'] == 'fail') {
       util.toast(this,data.error_info);
    }
  },
  // onShareAppMessage: function () {
  //   return getApp().share({title : '',desc : '',path : ''});
  // }  
})