
function formatTime(date) {
  var year = date.getFullYear()
  var month = date.getMonth() + 1
  var day = date.getDate()

  var hour = date.getHours()
  var minute = date.getMinutes()
  var second = date.getSeconds()


  return [year, month, day].map(formatNumber).join('-') + ' ' + [hour, minute, second].map(formatNumber).join(':')
}

 function formatDiff(startData,endData) {
 }

/* 毫秒级倒计时 */
function countdown(that,total_micro_second) {
   
   if (total_micro_second <= 0 || isNaN(total_micro_second)) {
     that.setData({
       clock:[0, 0, 0].map(formatNumber)
     });
     if(that.timer != undefined) {
        clearTimeout(that.timer); 
     }
     // timeout则跳出递归
     return ;
   }  else {
       // 渲染倒计时时钟
       that.setData({
         clock:dateformat(total_micro_second)
       });
   }

   that.timer = setTimeout(function(){
      // 放在最后--
      total_micro_second -= 60;
      countdown(that,total_micro_second);
   }
   ,60);
   
}

function remove_array(array,index) {
  for(var i =0;i <array.length;i++){ 
    var temp = array[i]; 
    if(!isNaN(index)){ 
     temp=i; 
    } 
    if(temp == index){ 
    for(var j = i;j <array.length;j++){ 
      array[j]=array[j+1]; 
    } 
    array.length = array.length-1; 
    } 
  } 
  return array;
}
 
/** 
 *多个定时器
*/
function countdowns(that,total_micro_seconds) {
   var clock = [];
   for(var i =0;i<total_micro_seconds.length;i++) {
      var total_micro_second = total_micro_seconds[i];
      if (total_micro_second <= 0 || isNaN(total_micro_second)) {
        clock[i] = [0, 0, 0].map(formatNumber);
       // that.setData({
       //   clock:[0, 0, 0].map(formatNumber)
       // });
        if(that.timer != undefined) {
           total_micro_seconds = remove_array(total_micro_seconds,i);
           that.setData({groups : {
              "goods_groups" : remove_array(that.data.groups.goods_groups,i),
              "groups" : that.data.groups.groups
           }});
        }
       // timeout则跳出递归
        continue ;
     }  else {
        clock[i] = dateformat(total_micro_second);
     }
   }

   that.setData({clock : clock});

   if(total_micro_seconds.length < 1 ) {
      if(that.timer != undefined) {
          clearTimeout(that.timer); 
      }
   }
   

   that.timer = setTimeout(function(){
      var temp_array = [];
      for(var i =0;i<total_micro_seconds.length;i++) {
         var total_micro_second = total_micro_seconds[i] - 60;
         temp_array[i] = total_micro_second;
      }
      
      countdowns(that,temp_array);
   }
   ,60);
}

// 时间格式化输出，如3:25:19 86。每10ms都会调用一次
function dateformat(micro_second) {
   // 秒数
   var second = Math.floor(micro_second / 1000);
   // 小时位
   var hr = Math.floor(second / 3600);
   
   // 分钟位
   var min = Math.floor((second - hr * 3600) / 60);
   
   // 秒位
   var sec = (second - hr * 3600 - min * 60);// equal to => var sec = second % 60;
  
  // 毫秒位，保留2位
  var micro_sec = Math.floor((micro_second % 1000) / 10);
 
  return [hr, min, sec].map(formatNumber);
}

function formatNumber(n) {
  n = n.toString()
  return n[1] ? n : '0' + n
}


function ajax(obj) {

  checkNet({
    success : function() {
      var endtime = undefined;

      var retrunType = {
        'json' : "Content-Type': 'application/json",
      };
      if(undefined == obj.retrunType) {
        obj.retrunType = 'json';
      }
      var returnType = obj.retrunType;

      var starttime = new Date().getTime();

      
      wx.request({
        url: obj.url,
        data: obj.data || [],
        method : obj.method || "GET",
        header: {
            returnType
        },
        success : function(data){
           endtime = new Date().getTime();
           console.warn('Info : '   + " Url : " + obj.url  + " Time : " + (endtime - starttime) / 1000 + "s");
           if(data.statusCode == 200) {
              var data_core = data.data;
              if(config('appDebug')) {
                console.log(data_core);
              }
              if(data_core.result != undefined) {
                if(data_core.result == "fail") {
                   var page = getCurrentPages();
                   //用户 token过期，自动登录
                   if(data_core.error_code == "40001") {
                        wx.removeStorageSync('token');
                        getApp().login({
                          //登录成功 刷新请求
                          success : function(token) {
                            if(page[(page.length - 1) >= 0 ? (page.length - 1) : 0]) {
                              page[(page.length - 1) >= 0 ? (page.length - 1) : 0].onLoad(page[(page.length - 1) >= 0 ? (page.length - 1) : 0].options);  
                            }
                          },
                          //登录失败回调函数
                          error : function() {
                            if( page[(page.length - 1) >= 0 ? (page.length - 1) : 0])  {
                              page[(page.length - 1) >= 0 ? (page.length - 1) : 0].error(data_core);                              
                              obj.success(data_core);
                            }
                          }
                        });
                            
                        return false; //不执行，回调
                   } else {
                     if( page[(page.length - 1) >= 0 ? (page.length - 1) : 0]) 
                        page[(page.length - 1) >= 0 ? (page.length - 1) : 0].error(data_core);                       
                   }
                   //return false;
                }
              } 
              obj.success(data_core);
           } else {
              console.error("Mes : " + "_状态码: " + data.statusCode  + ' ERR: Args : ' + JSON.stringify(obj.data) + " Url : " + obj.url + " Time : " + (endtime - starttime) / 1000 + "s");   
           }
        },
        fail : function($data){
          endtime = new Date().getTime();
          console.error('ERR: Args : ' + JSON.stringify(obj.data) + " Url : " + obj.url + " Time : " + (endtime - starttime) / 1000 + "s");
          var page = getCurrentPages();
          notNetCon( page[(page.length - 1) >= 0 ? (page.length - 1) : 0],1,1);
            
          var info = {'result' : 'fail','error_info' : config('error_text')[0]};
          if(typeof obj.error == "function") {
              obj.error(info);
          } else {
              page[(page.length - 1) >= 0 ? (page.length - 1) : 0].error(info);                       
          }
        }
      })      
    },
    error : function() {
       var page = getCurrentPages();
       notNetCon( page[(page.length - 1) >= 0 ? (page.length - 1) : 0],0);
    }
  });

  
}

function config(name) {
  var obj = require('../config')
  if(name) {
    return obj.config()[name];
  } else {
    return obj.config();
  }
  
}

function wxpay(self) {
    
     var url = self.baseApiUrl + "?g=Api&m=Weuser&a=wxpay";
     var data = {
        "token" : self.token,
        "order_id" : self.order_id
     };

     var order_id = self.order_id;

     ajax({
        "url" :  url,
        "method" :　"GET",
        "data" : data,
        "success" : function(data) {  
            if(data['result'] == "ok") {
               wx.requestPayment({
                  'timeStamp': data.param.timeStamp,
                  'nonceStr': data.param.nonceStr,
                  'package': data.param.package,
                  'signType': 'MD5',
                  'paySign': data.param.paySign,
                  'success':function(res) {

                      paySuccessRedirect(order_id);
                      
                    },
                  'fail':function($data){
                    self.setData({ "btn_order_done": false });
                     if($data.errMsg == "requestPayment:fail cancel") {
                        //用户取消支付
                         if(self.route == "pages/checkout") {
                            self.error( {"result" : 'fail',"error_info" : '支付未完成， 请重新支付'});           
                            self.setData({'cancel_pay' : 1});
                         }

                     } else {
                        self.error({"result" : 'fail',"error_info" : $data.errMsg});
                     }
                  }
                })
            } else if(data['result'] == "fail") {
               data.url  = 'orders';
               self.error(data);
            } else {
              var data = {"result" : 'fail',"error_info" : '支付异常,请联系客服完成订单。'};
              self.setData({ "btn_order_done": false });
              self.error(data);
            }
        }
      });
  }

  function wxlogin(that) {
    if(wx.getStorageSync('token')) {
      return true;
    }
    console.log("auto login");
    if(that.__route__  == "pages/personal") return false;
    wx.login({
      success: function(res) {
        if (res.code) {
          var url = config('baseApiUrl') + "?g=api&m=WeApp&a=login&code=" + res.code;
          ajax({
              "url" :  url,
              "method" :　"GET",
              "success" : function(data) {
                   var token = data.token;
                   var value = wx.getStorageSync('token')
                   while(!value) wx.setStorageSync('token',token);
                   if(typeof(that.refresh) == "function") {
                      that.refresh();
                   } else {
                     that.error({"result" : "fail","error_info" : "登录已经过期,请重新登录。","url" : "personal"});
                   }
                   
              }
          })
        }
      }
    });
  }

//util.js 
function imageUtil(e) { 
  var imageSize = {}; 
  var originalWidth = e.detail.width;//图片原始宽 
  var originalHeight = e.detail.height;//图片原始高 
  var originalScale = originalHeight/originalWidth;//图片高宽比 
  //console.log('originalWidth: ' + originalWidth) 
  //console.log('originalHeight: ' + originalHeight) 
  //获取屏幕宽高 
  wx.getSystemInfo({ 
    success: function (res) { 
    var windowWidth = res.windowWidth; 
    var windowHeight = res.windowHeight; 
    var windowscale = windowHeight/windowWidth;//屏幕高宽比 
    //console.log('windowWidth: ' + windowWidth) 
    //console.log('windowHeight: ' + windowHeight) 
    if(originalScale < windowscale){//图片高宽比小于屏幕高宽比 
      //图片缩放后的宽为屏幕宽 
      imageSize.imageWidth = windowWidth; 
      imageSize.imageHeight = (windowWidth * originalHeight) / originalWidth; 
    }else{//图片高宽比大于屏幕高宽比 
      //图片缩放后的高为屏幕高 
      imageSize.imageHeight = windowHeight; 
      imageSize.imageWidth = (windowHeight * originalWidth) / originalHeight; 
    } 
      
    } 
  }) 
  //console.log('缩放后的宽: ' + imageSize.imageWidth) 
  //console.log('缩放后的高: ' + imageSize.imageHeight) 
  return imageSize; 
} 


function loadding(self,operate) {
  if(!operate ) { 
    operate = 0;
  }
  
  self.setData({page : {"load" : 0,"operate" : operate}});  
}

function loaded(self) {
    self.setData({page : {"load" : 1,"operate" : 0}});
}

function toast(self,mess) {
    self.setData({"toast" : {"toast" : 1,"mess" : mess}});
    setTimeout(function(){
      self.setData({"btn_order_done" : false});
      self.setData({"toast" : {"toast" : 0,"mess" : ''}});} ,2000);
}

function notNetCon(self,type=1,is_ajax=0) {
   if(!type) {
      toast(self,"网络不太顺畅哦,请重新加载"); 
   } else {
      self.setData({
        'notNetCon' : {
           show : 1,
           error : type,
           is_ajax : is_ajax
        }
      });
   }
   
   loaded(self);
}

function succNetCon(self) {
  if(self.data.notNetCon && self.data.notNetCon.error && self.data.notNetCon.show) loadding(self);

   self.setData({
    'notNetCon' : {
       show : 0,
       error : 0
    }
   });
}

function checkNet(obj={}) {
  wx.getNetworkType({
    success: function(res) {
      var networkType = res.networkType
      if(networkType == "none") {

          if(obj.error != undefined) {
            wx.hideShareMenu();
            obj.error();
          }
      } else {
         if(obj.success != undefined) 
           obj.success();
         
      }
    },
    error: function(res) {

      console.log('获取网络类型失败');
    }
  })
}

function redirect(url,isNew=0) {
  if(!url) {
    wx.navigateBack();
  }

  if(url == 'page/index' || url == 'page/orders' || url == 'page/groups' || url == 'page/personal') {
    wx.switchTab({
      url: url
    })
  } else {
    if(isNew) {
      wx.navigateTo({
        url: url
      })
    } else {
      wx.redirectTo({
        url: url
      })  
    }
  }
}

/**
*支付完成 回调
*/
function paySuccessRedirect(order_id) {
  var url = config('baseApiUrl') + "?g=Api&m=Weuser&a=getorder";  
  var token = wx.getStorageSync('token');
  
  var data = {
    "token" : token,
    "order_id" : order_id
  };

  ajax({
    url : url,
    data : data,
    method : "GET",
    success : function(data){
      if(data.result == 'ok') {
         if(data.order.group_order_id && data.order.group_order_id != '0') {
            wx.redirectTo({'url' : 'group?id=' + data.order.group_order_id});
         } else {
            wx.redirectTo({'url' : 'order?id=' + order_id});
         }
      } else {
         wx.redirectTo({'url' : 'orders'});
      }
      
    }
  });
}

/***
 * wxAutoImageCal 计算宽高
 * 
 * 参数 e: iamge load函数的取得的值
 * 返回计算后的图片宽高
 * {
 *  imageWidth: 100px;
 *  imageHeight: 100px;
 * }
 */
function wxAutoImageCal(e) {
  //console.dir(e);
  //获取图片的原始长宽
  var originalWidth = e.detail.width;
  var originalHeight = e.detail.height;
  var windowWidth = 0, windowHeight = 0;
  var autoWidth = 0, autoHeight = 0;
  var results = {};
  wx.getSystemInfo({
    success: function (res) {
      //console.dir(res);
      windowWidth = res.windowWidth;
      windowHeight = res.windowHeight;
      //判断按照那种方式进行缩放
      //console.log("windowWidth"+windowWidth);
      if (originalWidth > windowWidth) {//在图片width大于手机屏幕width时候
        autoWidth = windowWidth;
        // console.log("autoWidth"+autoWidth);
        autoHeight = (autoWidth * originalHeight) / originalWidth;
        // console.log("autoHeight"+autoHeight);
        results.imageWidth = autoWidth;
        results.imageheight = autoHeight;
      } else {//否则展示原来的数据
        results.imageWidth = originalWidth;
        results.imageheight = originalHeight;
      }
    }
  })
  return results;
}

module.exports = {
  formatTime: formatTime,
  ajax : ajax,
  config : config,
  formatDiff : formatDiff,
  countdown : countdown,
  wxpay : wxpay,
  wxlogin : wxlogin,
  imageUtil: imageUtil,
  loadding : loadding,
  loaded : loaded,
  toast : toast,
  checkNet : checkNet,
  notNetCon : notNetCon,
  succNetCon : succNetCon,
  redirect : redirect,
  countdowns : countdowns,
  paySuccessRedirect : paySuccessRedirect,
  wxAutoImageCal : wxAutoImageCal
}
