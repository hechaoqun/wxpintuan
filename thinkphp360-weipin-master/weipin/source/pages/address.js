var util = require('../utils/util.js')

Page({
  data:{
    "provinces" : [
        {
          'region_id' : 0,
          "region_name" : '请选择省'
        }
     ],     
    "citys" : [{
      'region_id' : 0,
      "region_name" : '请选择市'
    }],
    "areas" : [{
        'region_id' : 0,
        "region_name" : '请选择区'
    }],
     "regions" : false,
     "address_select" : 0,
     "provinceValue" : '',
     "cityValue" : '',
     "areaValue" : '',
     "provinceIndex" : '',
     "cityIndex" : '',
     "areaIndex" : '',
     "adTypes" : ['家庭','公司'],
     "address_name" : ['HOME','WORK'],
     "adTypeIndex" : 0,
     "areasAnimation" : {},
     "citysAnimation" : {},
     "provincesAnimation" : {},
     'provinceTop' : "0em",
     'cityTop' : "0em",
     'areaTop'  : "0em",
     'provinceVal' : "0",
     'cityVal' : "0",
     'areaVal'  : "0",
     'modalHidden' : true,
     'address' :  {
       "receive_name" : '',
       "mobile" : '',
       "address" : ''
     },
     'receive_name_tip' : false,
     'mobile_tip' : false,
     'province_tip' : false,
     'city_tip' : false,
     'district_tip' : false,
     'adType_tip' : false,
     'adinfo_tip' : false
  },
  formId:function(options){
  },
  deletes:function(options){
    var self = this;
    wx.showModal({
      title: '确定删除这个地址吗？',
      content: '',
      success: function(res) {
        if (res.confirm) {
           self.modalConfirm();
        } 
      }
    })
    //this.setData({modalHidden : false});
  },
  modalConfirm:function(options){
    util.loadding(this,1);
    var url = this.baseApiUrl + "?g=Api&m=Weuser&a=address&address_id=" + this.options.address_id + "&token=" + this.token;;
    var self = this;
     util.ajax({
        url : url,
        method : "DELETE",
        success : function(data){
            if(data.result == 'ok') {
               util.loadding(self);
               var url = "./addresses";
               if(self.options.goods_id != undefined) {
                   url = url + "?sell_type=" + self.options.sell_type + "&goods_id=" 
                        + self.options.goods_id + "&address_id=" + self.options.address_id;
               }
               
               wx.navigateBack();
               // wx.redirectTo({
               //    url: url
               // })
            } else {
                self.error(data);
            }
        }
     });
    this.setData({modalHidden : true});
  },
  //错误处理函数
  error:function(data) {
    this.setData({page : {load : 1}});
    if(data['result'] == 'fail') {
       util.toast(this,data.error_info);
    }
  },
  modalCancel:function(options){
    this.setData({modalHidden : true});
  },
  receive_function:function(e){
    if(!e.detail.value.trim()) {
       this.setData({'receive_name_tip' : true});
    } else {
       this.setData({'receive_name_tip' : false});
    }
  },
  mobile_function:function(e){
      if(!e.detail.value.trim()) {
        this.setData({'mobile_tip' : true});
      } else {
        this.setData({'mobile_tip' : false});
      }

      if(!/^\d{11}$/.test(e.detail.value.trim())) {

        this.setData({'mobile_tip' : true});
      } else {
        this.setData({'mobile_tip' : false});
      }
  },
  adinfo_function:function(e){
    if(!e.detail.value.trim()) {
       this.setData({'adinfo_tip' : true});
    } else {
       this.setData({'adinfo_tip' : false});
    }
  },
  //添加地址 || 或者修改地址
  listenFormSubmit:function(e){
    
    var that = this;  
    var formData = e.detail.value;   

   
    if(!formData.receive_name.trim()) {
       this.setData({'receive_name_tip' : true});
    } else {
       this.setData({'receive_name_tip' : false});
    }

    
    if(!formData.mobile.trim()) {
  
       this.setData({'mobile_tip' : true});
    } else {
       this.setData({'mobile_tip' : false});
    }

    if(!/^\d{11}$/.test(formData.mobile.trim())) {

       this.setData({'mobile_tip' : true});
    } else {
      this.setData({'mobile_tip' : false});
    }

    if(!formData.address_name.trim()) {
  
       this.setData({'adType_tip' : true});
    } else {
      this.setData({'adType_tip' : false});
    }

    if(!formData.province.trim()) {
       this.setData({'province_tip' : true});
    } else {
      this.setData({'province_tip' : false});
    }

     if(!formData.city.trim() && !this.data.province_tip) {
       this.setData({'city_tip' : true});
    } else {
       this.setData({'city_tip' : false});
    }
    
    if(!formData.district.trim() && !this.data.city_tip && !this.data.province_tip) {
    
       this.setData({'district_tip' : true});
    } else {
       this.setData({'district_tip' : false});
    }

    
    if(!formData.address.trim()) {
       this.setData({'adinfo_tip' : true});
    } else {
       this.setData({'adinfo_tip' : false});
    }

    if(this.data.receive_name_tip || this.data.mobile_tip || this.data.province_tip || this.data.city_tip ||this.data.district_tip ||this.data.adType_tip ||this.data.adinfo_tip) {
      return false;
    }

    util.loadding(this,1);
    if(!this.options.address_id) {
      this.addAddress(formData);
    } else {
       this.editAddress(formData);
    }
  },
   bindHideKeyboard: function(e) {
      wx.hideKeyboard()
  },
  addAddress:function(formData) {
    var url = this.baseApiUrl + "?g=Api&m=Weuser&a=address&token=" + this.token;
     var self = this;
     util.ajax({
        url : url,
        data : formData,
        method : "POST",
        success : function(data){
            util.loaded(self);
            if(data.result == 'ok') {
               if(self.options.goods_id != undefined && self.options.sell_type != undefined) {
                  wx.setStorageSync('select_address_id',data.address_id);

                  var pages = getCurrentPages()    //获取加载的页面
                  if(pages[pages.length-2] != undefined && pages[pages.length-2].route == "pages/addresses") {
                    wx.navigateBack({delta : 2});
                    return; 
                  }

                  wx.navigateBack({delta : 1});
                  return ;
               }
               
               wx.navigateBack(); 
            } 
        }
     });
  },
  editAddress:function(formData){
     var url = this.baseApiUrl + "?g=Api&m=Weuser&a=address&address_id=" + this.options.address_id + "&token=" + this.token;
     var self = this;
     util.ajax({
        url : url,
        data : formData,
        method : "PUT",
        success : function(data){
            util.loaded(self);
            if(data.result == 'ok') {
               var url = "./addresses";
               if(self.options.goods_id != undefined && self.options.goods_id != "undefined" &&  self.options.sell_type != undefined &&  self.options.sell_type != "undefined") {
                  wx.setStorageSync('select_address_id',self.options.address_id);
                  wx.navigateBack({delta : 2});
                  return;
               }

               wx.navigateBack();
            }
        }
     });
  },
  onLoad:function(options){
    if(this.options.address_id) {
      wx.setNavigationBarTitle({
        title: '编辑地址'
      })
    } else {
      wx.setNavigationBarTitle({
        title: '添加新地址'
      })
    }

    util.loadding(this);

    console.log("op : " + JSON.stringify(options));
    this.options = options;
    var self = this;
    this.token = wx.getStorageSync('token');
    this.baseApiUrl = util.config('baseApiUrl'); 
    
    var data_version = wx.getStorageSync('data_version');
    
    var url = this.baseApiUrl + "?g=api&m=project&a=data_version&type=region_list"; 

    var regions = [];
    util.ajax({
      "url" :  url,
      "data" : {},
      success : function(data) {
        if(data.result == 'ok') {
            
            if(data.data_version.version != data_version) {
              self.regions();  
              wx.setStorageSync('data_version',data.data_version.version);
              regions = this.regions_data;
            } else {
               regions = wx.getStorageSync('regions');
               if(regions) {
                 self.setData({
                   //regions :　regions,
                   provinces : regions[1]
                 });
                 self.regions_data = regions;
                 self.editHtml(regions);
              } else {
                  self.regions();  
                  regions = this.regions_data;
              }
            }

            
            //util.loaded(self);
          //  self.setData({loaded:false});
            // while(true) {
            //   if(regions) {
            //       break;
            //   }
            // }
          
        }
      },
      error : function(res) {
        //console.log("网络错误");
      }
    });
    
    //console.log(regions);
   
    
    //console.log(regions);
    // 页面初始化 options为页面跳转所带来的参数
     //this.init();
  },
  editHtml:function(regions){
    var self = this;
    if(this.options.address_id) {
       util.loadding(this,1);
       var url = this.baseApiUrl + "?g=Api&m=Weuser&a=address&address_id=" + this.options.address_id + "&token=" + this.token;
       util.ajax({
          "url" :  url,
          "data" : {},
          success : function(data) {
             util.loaded(self);
             if(data.result == 'ok') {
                self.setData({loaded:true});
                var adTypeIndex = -1;
                var address_names = self.data.address_name;
                //console.log(address_names);
                for(var i=0;i<address_names.length;i++) {
                  if(address_names[i] == data.address.address_name) {
                    adTypeIndex = i;
                    break;
                  }
                }

                
                var provinceId = data.address.province_id;
                var provinces = regions[1];
                var provinceVal = 0;

                var cityId = data.address.city_id;
                //var citys = regions[2];
                var cityVal = 0;

                var areaVal = 0;
                
                var areaId = data.address.district_id;
                
                //start重置省份
                var province = regions[1];

                  //console.log('value ： ' + val);
                  //id = data.address.province_id;

                  var citys = [];
                  var city = regions[2];
                  for(var ii = 0;ii < city.length;ii++) {
                      if(provinceId == city[ii].parent_id) {
                        citys.push(city[ii]);
                      }
                  }


                  var areas = [];

                  var area = regions[3];
                  for(var iii = 0;iii < area.length;iii++) {
                    if(cityId == area[iii].parent_id) {
                      areas.push(area[iii]);
                    }
                  }

                  //console.log('areas===');
                  //console.log(areas);
                  
                  // _that.setData({
                  //     provinces : province,
                  //     citys : citys,
                  //     areas : areas,
                  //     areaIndex : areas[0].region_id
                  // });
                  //end

               
                //var areas = regions[3];
                

                
               
                for(var ii = 0;ii < citys.length;ii++) {
                    if(cityId == citys[ii].region_id) {
                      cityVal = ii;
                      var cityValue = citys[ii].region_name;
                      break;
                    }
                }

               for(var iiii = 0 ; iiii < areas.length; iiii++) {
                  if(areaId == areas[iiii].region_id) {
                    areaVal = iiii;
                    var areaValue = areas[iiii].region_name;
                    break;
                  }
               }
              
                for(var ii = 0;ii < provinces.length;ii++) {
                    if(provinceId == provinces[ii].region_id) {
                        provinceVal = ii;
                        var provinceValue = provinces[ii].region_name;
                        break;
                    }
                }

                self.setData({
                  citys : citys,
                  areas : areas,
                  "address" : data.address,
                  "adTypeIndex" : adTypeIndex,
                  'provinceVal' : provinceVal,
                  'cityVal' : cityVal,
                  'areaVal'  : areaVal,
                  "provinceIndex" : provinceId,
                  "cityIndex" : cityId,
                  "areaIndex" : areaId,
                  "provinceValue" : provinceValue,
                  "cityValue" : cityValue,
                  "areaValue" : areaValue,
                });

                self.init();
                self.setData({
                  "provinceTop" : -2.5 * provinceVal + "em",
                  'cityTop' : -2.5 * cityVal  + "em",
                  'areaTop'  : -2.5 * areaVal  + "em"
                });
             }
          },
          error : function(res) {
            //console.log("网络错误");
          }
        });
    } else {
      //根据系统定位,获取默认地址
      var userMap = wx.getStorageSync("userMap");
      if(userMap != undefined && userMap) {
        var url = this.baseApiUrl + "?g=Api&m=Weuser&a=regions&token=" + this.token;
        util.ajax({
        url : url,
        data : userMap,
        method : "POST",
        success : function(data){
            if(data.result == 'ok') {
              self.byRegionsId(regions,data.region.province_id,data.region.city_id,data.region.district_id);  
            }
        }})};
      this.setData({loaded:true});
    }


  },
  //添加地址根据地区id定位城市
  byRegionsId: function(regions,provinceId,cityId,areaId) {
    var self = this;
    //var provinceId = data.address.province_id;
    var provinces = regions[1];
    var provinceVal = 0;

    //var cityId = data.address.city_id;
    //var citys = regions[2];
    var cityVal = 0;

    var areaVal = 0;
    
    //var areaId = data.address.district_id;
    
    //start重置省份
    var province = regions[1];

      //console.log('value ： ' + val);
      //id = data.address.province_id;

      var citys = [];
      var city = regions[2];
      for(var ii = 0;ii < city.length;ii++) {
          if(provinceId == city[ii].parent_id) {
            citys.push(city[ii]);
          }
      }


      var areas = [];

      var area = regions[3];
      for(var iii = 0;iii < area.length;iii++) {
        if(cityId == area[iii].parent_id) {
          areas.push(area[iii]);
        }
      }

    
    for(var ii = 0;ii < citys.length;ii++) {
        if(cityId == citys[ii].region_id) {
          cityVal = ii;
          var cityValue = citys[ii].region_name;
          break;
        }
    }

    for(var iiii = 0 ; iiii < areas.length; iiii++) {
      if(areaId == areas[iiii].region_id) {
        areaVal = iiii;
        var areaValue = areas[iiii].region_name;
        break;
      }
    }
  
    for(var ii = 0;ii < provinces.length;ii++) {
        if(provinceId == provinces[ii].region_id) {
            provinceVal = ii;
            var provinceValue = provinces[ii].region_name;
            break;
        }
    }

    //console.log(areaValue);

    self.setData({
      citys : citys,
      areas : areas,
      'provinceVal' : provinceVal,
      'cityVal' : cityVal,
      'areaVal'  : areaVal,
      "provinceIndex" : provinceId,
      "cityIndex" : cityId,
      "areaIndex" : areaId,
      "provinceValue" : provinceValue,
      "cityValue" : cityValue,
      "areaValue" : areaValue
    });

    self.init();

    self.setData({
      "provinceTop" : -2.5 * provinceVal + "em",
      'cityTop' : -2.5 * cityVal  + "em",
      'areaTop'  : -2.5 * areaVal  + "em"
    });
  }
  ,
  init: function() {
    //this.data.cityValue;
    //this.data.provinceValue;
    //this.data.areaValue;
    //var pos = target["pos_" + target.id];
    //var speed = target["spd_" + target.id] * Math.exp(-0.03 * d);
    var provinceTop = parseFloat(this.data.provinceTop.replace(/em/g, ""));
    var cityTop = parseFloat(this.data.cityTop.replace(/em/g, ""));
    var areaTop = parseFloat(this.data.areaTop.replace(/em/g, ""));

    //坐标
    var provincePos = provinceTop;
    var cityPos = cityTop;
    var areaPos = areaTop;

    for(var i = 0;i < parseInt(this.data.provinceVal); i++) {
        provincePos = provincePos - 2.5;
    }

    for(var i = 0;i < parseInt(this.data.cityVal); i++) {
        cityPos = cityPos - 2.5;
    }

    for(var i = 0;i < parseInt(this.data.areaVal); i++) {
        areaPos = areaPos - 2.5;
    }

    this.animationObj1.translate3d(0,provincePos + "em",0).step({duration: 80});
    this.setData({
      animationProvinces : this.animationObj1.export(),
    }); 

    this.animationObj2.translate3d(0,cityPos + "em",0).step({duration: 80});
    this.setData({
      animationCitys : this.animationObj2.export(),
    });

    this.animationObj3.translate3d(0,areaPos + "em",0).step({duration: 80});
    this.setData({
        animationAreas : this.animationObj3.export(),
     })

    //console.log(this.data); 
  },
  regions: function(e) {
    util.loadding(this);
    var self = this;
    var url = this.baseApiUrl + "?g=api&m=project&a=regions";
    util.ajax({
      "url" :  url,
      "data" : {},
      success : function(data) {
        if(data.result == 'ok') {
            util.loaded(self);
            var regions = data.regions;
            if(regions) {
              self.setData({
                //regions :　regions,
                provinces : regions[1]
              });
              self.regions_data = regions;
              self.editHtml(regions);
            }
            wx.setStorageSync('regions',regions);
        }
      },
      error : function(res) {
        //console.log("网络错误");
      }
    });
  },
  bindPickerChange: function(e) {
    //console.log('picker发送选择改变，携带值为', e.detail.value)
    this.setData({
      index: e.detail.value
    })
  },
  tapName:function(e){
    //console.log(e);
  },
  onReady:function(){
    // 页面渲染完成
  },
  onShow:function(){
	this.setData({page : {"load" : 1}});
    wx.getSystemInfo( {
      success: ( res ) => {
        this.setData( {
          windowHeight: res.windowHeight,
          windowWidth: res.windowWidth
        })
      }
    })

    this.animationObj1 = wx.createAnimation({
      duration: 80,
      timingFunction: 'ease',
    })
    
    this.animationObj2 = wx.createAnimation({
      duration: 80,
      timingFunction: 'ease',
    })
    this.animationObj3 = wx.createAnimation({
      duration: 80,
      timingFunction: 'ease',
    })
    // 页面显示
  },
  onHide:function(){
    // 页面隐藏
  },
  onUnload:function(){
    // 页面关闭
  },
  address_select: function(e) {
    //console.log(this.regions_data[1]);
     this.setData({
      address_select: 1
    })
  },
  provinces : function(e){
    var id = e.currentTarget.dataset.id;
    var citys = [];

     var city = this.regions_data[2];
     for(var ii = 0;ii < city.length;ii++) {
        if(id == city[ii][2]) {
          citys.push(city[ii]);
        }
     }
     this.setData({
        'citys' : citys
     });
    //console.log(citys);
  },
  citys : function(e){
    var id = e.currentTarget.dataset.id;
    var areas = [];

    var area = this.regions_data[3];
    for(var iii = 0;iii < area.length;iii++) {
      if(id == area[iii][2]) {
        areas.push(area[iii]);
      }
    }
    this.setData({
        'areas' : areas
     });
    //console.log(areas);
  },
  areas : function(e){
     var id = e.currentTarget.dataset.id;
     this.setData({
        "areaIndex" : id
     });
  },
  
  adTypesChange: function(e) {
    var adTypeIndex = e.detail.value; 
    if(adTypeIndex == this.data.adTypeIndex || adTypeIndex == "null") {
      return false;
    }
    this.setData({
      adTypeIndex: adTypeIndex
    })
  },

  finish: function(e) {
     var id = this.data.areaIndex;
     var city = this.data.citys;
     var cityName = '';
     var cityPid = '';



    var province = this.data.provinces;
    var provinceName = '';
    var provinceId = '';

    var area = this.data.areas;
    var areaPid = '';
    var areaName = '';


    for(var iiii = 0 ; iiii < area.length; iiii++) {
      if(id == area[iiii].region_id) {
        areaPid = area[iiii].parent_id;
        areaName = area[iiii].region_name;
        break;
      }
    }

     for(var ii = 0;ii < city.length;ii++) {
        if(areaPid == city[ii].region_id) {
          cityName = city[ii].region_name;
          cityPid = city[ii].parent_id;
          break;
        }
     }

     if(cityPid) {
       for(var ii = 0;ii < province.length;ii++) {
          if(cityPid == province[ii].region_id) {
            provinceName = province[ii].region_name;
            break;
          }
       }
     }
    
    if(!provinceName.trim()) {
       this.setData({'province_tip' : true});
    } else {
      this.setData({'province_tip' : false});
    }

     if(!cityName.trim()) {
       this.setData({'city_tip' : true});
    } else {
       this.setData({'city_tip' : false});
    }
    
    if(!areaName.trim()) {
    
       this.setData({'district_tip' : true});
    } else {
       this.setData({'district_tip' : false});
    }

    this.setData({
       "provinceValue" : provinceName,
       "cityValue" : cityName,
       "areaValue" : areaName,
       "provinceIndex" : cityPid,
       "cityIndex" : areaPid,
       "areaIndex" : id,
       "address_select" : 0
    });
  },
  close: function(e) {
    this.setData({
      "address_select" : 0
    });
  },

  //触摸开始
  touchstart: function(e) {
     console.log('touchstart');

         
     var target = e.currentTarget;
     
     this.childTarget = e.target;

     while (true) {
        if (target.dataset.class == undefined || target.dataset.class != "gear") {
            target = target.target;
        } else {
            break
        }
      } 
      clearInterval(target["int_" + target.id]);
      target["old_" + target.id] = e.touches[0].clientY;
      target["o_t_" + target.id] = (new Date()).getTime();
      var top = target.dataset.top;

      //console.log('top:' + top);
      if(target.dataset.type == 'provinces') {
        top = this.data.provinceTop;
        this.setData({
            provinceTop : top
        });
      } else if(target.dataset.type == 'citys') {
        top = this.data.cityTop;
        this.setData({
            cityTop : top
        });
      } else if(target.dataset.type == 'areas') {
        top = this.data.areaTop;
        this.setData({
            areaTop : top
        });
      }
      if (top) {
          target["o_d_" + target.id] = parseFloat(top.replace(/em/g, ""));
      } else {
          target["o_d_" + target.id] = 0;
      }

      if(target.dataset.type == 'provinces') {
           this.animationObj1.step({duration: 0}); 
            this.setData({
              animationProvinces : this.animationObj1.export()
            })
      } else if(target.dataset.type == 'citys') {
        this.animationObj2.step({duration: 0}); 
        this.setData({
          animationCitys : this.animationObj2.export()
        })
      } else if(target.dataset.type == 'areas') {
         this.animationObj3.step({duration: 0}); 
          this.setData({
            animationAreas : this.animationObj3.export()
          })
      }   

      this.target = target;
      
  },
  //触摸移动
  touchmove: function(e) {
    
     //console.log('touchmove');

     var target = this.target;
     while (true) {
        if (target.dataset.class == undefined || target.dataset.class != "gear") {
            target = target.target;
        } else {
            break
        }
      } 

      target["new_" + target.id] = e.touches[0].clientY;
      target["n_t_" + target.id] = (new Date()).getTime();

      var f = (target["new_" + target.id] - target["old_" + target.id]) * 30 / this.data.windowHeight;


      //console.log(f);

      target["pos_" + target.id] = target["o_d_" + target.id] + f;


      //console.log("y_" +　target["new_" + target.id]);
      //console.log("old" +　target["old_" + target.id]);
      //console.log("clientY" +　e.touches[0].clientY);
      //console.log("pos:" + target["pos_" + target.id]);

      if(target.dataset.type == 'provinces') {
          this.animationObj1.translate3d(0,target["pos_" + target.id] + "em",0).step(); 
          this.setData({
            animationProvinces : this.animationObj1.export()
          })
      } else if(target.dataset.type == 'citys') {
        this.animationObj2.translate3d(0,target["pos_" + target.id] + "em",0).step(); 
        this.setData({
          animationCitys : this.animationObj2.export()
        })
      } else if(target.dataset.type == 'areas') {
        this.animationObj3.translate3d(0,target["pos_" + target.id] + "em",0).step(); 
        this.setData({
          animationAreas : this.animationObj3.export()
        })
      }   


      if(e.touches[0].clientY < 1){
          this.gearTouchEnd(e);
      };   

      this.target = target;

  },

  //触摸结束
  touchend: function(e) {
     var target = this.target;
     while (true) {
        if (target.dataset.class == undefined || target.dataset.class != "gear") {
            target = target.target;
        } else {
            break
        }
      } 

      var flag = (target["new_" + target.id] - target["old_" + target.id]) / (target["n_t_" + target.id] - target["o_t_" + target.id]);
      if (Math.abs(flag) <= 0.2) {
          target["spd_" + target.id] = (flag < 0 ? -0.08 : 0.08);
      } else {
          if (Math.abs(flag) <= 0.5) {
              target["spd_" + target.id] = (flag < 0 ? -0.16 : 0.16);
          } else {
              target["spd_" + target.id] = flag / 2;
          }
      }
      if (!target["pos_" + target.id]) {
          target["pos_" + target.id] = 0;
      }
      
      this.rollGear(target);
      
      this.target = target;

  },
  rollGear : function(target) {
     var _that = this;
     //console.log(target);
     //控制插件滚动后停留的值
     function setGear (target, val) {
        //console.log("val:" + val);

        val = Math.round(val);

        if(isNaN(val)) {
          val = 0;
        }

        target.dataset.val = val;

        //console.log(target);
        var id = false;
      
        
        switch(target.dataset.type){
              case "provinces":
                  var province = _that.regions_data[1];

                  //console.log('value ： ' + val);
                  id = province[val].region_id;

                  var citys = [];
                  var city = _that.regions_data[2];
                  for(var ii = 0;ii < city.length;ii++) {
                      if(id == city[ii].parent_id) {
                        citys.push(city[ii]);
                      }
                  }
                  
                  var cityVal = _that.data.cityVal;
                  if(citys.length - 1 < cityVal) {
                      cityVal = citys.length - 1;
                  } 
                  _that.animationObj2.translate3d(0,cityVal * -2.5 + "em",0).step({duration: 200});
                  _that.setData({
                      animationCitys : _that.animationObj2.export(),
                  })
                  
                  var areas = [];

                  var area = _that.regions_data[3];
                  for(var iii = 0;iii < area.length;iii++) {
                    if(citys[cityVal].region_id == area[iii].parent_id) {
                      areas.push(area[iii]);
                    }
                  }

                  //console.log('areas===');
                 // console.log(areas);
                  var areaVal = _that.data.areaVal;
                  if(areas.length - 1 < areaVal) {
                      areaVal = areas.length - 1;
                  } 
                  _that.animationObj3.translate3d(0,areaVal * -2.5 + "em",0).step({duration: 200});
                  _that.setData({
                    animationAreas : _that.animationObj3.export(),
                  })

                  _that.setData({
                      provinces : province,
                      citys : citys,
                      areas : areas,
                      areaIndex : areas[areaVal].region_id
                  });

                  // _that.animationObj2.translate3d(0,0 + "em",0).step({duration: 200});
                  // _that.setData({
                  //     animationCitys : _that.animationObj2.export(),
                  // })

                  // _that.animationObj3.translate3d(0,0 + "em",0).step({duration: 200});
                  // _that.setData({
                  //     animationAreas : _that.animationObj3.export(),
                  // })
                
                  break;
              case 'citys':
                var city = _that.data.citys;
                id = city[val].region_id;

                var areas = [];

                var area = _that.regions_data[3];
                for(var iii = 0;iii < area.length;iii++) {
                  if(id == area[iii].parent_id) {
                    areas.push(area[iii]);
                  }
                }

                var areaVal = _that.data.areaVal;
                if(areas.length - 1 < areaVal) {
                    areaVal = areas.length - 1;
                } else {
                   _that.setData({
                      cityVal : val
                  })   
                }
                _that.animationObj3.translate3d(0,areaVal * -2.5 + "em",0).step({duration: 200});
                _that.setData({
                  animationAreas : _that.animationObj3.export(),
                })

                 _that.setData({
                    citys : city,
                    areas : areas,
                    areaIndex : areas[areaVal].region_id
                  });
                  
                break;
                
                case 'areas':
                  var area = _that.data.areas;
                  id = area[val].region_id;

                  _that.setData({
                      areaIndex : id,
                      areaVal : val
                  });
                break;
          }
    }
    var d = 0;
    var stopGear = false;
    function setDuration() {
        stopGear = true;
    }
    clearInterval(target["int_" + target.id]);
    target["int_" + target.id] = setInterval(function() {
        var pos = target["pos_" + target.id];
        var speed = target["spd_" + target.id] * Math.exp(-0.03 * d);
        pos += speed;
        if (Math.abs(speed) > 0.1) {} else {
            var b = Math.round(pos / 2.5) * 2.5;
            pos = b;
            setDuration();
        }
        if (pos > 0) {
            pos = 0;
            setDuration();
        }
        var minTop = -(target.dataset.len - 1) * 2.5;
      
        if (pos < minTop) {
            pos = minTop;
            setDuration();
        }
        if (stopGear) {
            var gearVal = Math.abs(pos) / 2.5;
            setGear(target, gearVal);
            clearInterval(target["int_" + target.id]);
        }
        target["pos_" + target.id] = pos;

        
         if(target.dataset.type == 'provinces') {
            _that.animationObj1.translate3d(0,pos + "em",0).step({duration: 80});
            _that.setData({
              animationProvinces : _that.animationObj1.export(),
            })
        } else if(target.dataset.type == 'citys') {
             _that.animationObj2.translate3d(0,pos + "em",0).step({duration: 80});
             _that.setData({
                animationCitys : _that.animationObj2.export(),
            })
        } else if(target.dataset.type == 'areas') {
           _that.animationObj3.translate3d(0,pos + "em",0).step({duration: 80});
           _that.setData({
              animationAreas : _that.animationObj3.export(),
           })
        }   
        
        var top = pos  + 'em';
        if(target.dataset.type == 'provinces') {
          
          _that.setData({
              provinceTop : top
          });
        } else if(target.dataset.type == 'citys') {
         
          _that.setData({
              cityTop : top
          });
        } else if(target.dataset.type == 'areas') {
         
          _that.setData({
              areaTop : top
          });
        }
        d++;
    }, 30);  
  },
})