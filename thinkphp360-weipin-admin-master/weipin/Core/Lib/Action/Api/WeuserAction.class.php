<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：WeuserAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:上午10:52:59
 * ----------------------------------------------------------------------------
 */
class WeuserAction extends ApiAction {
    public $MemberDb;
    public $memberInfo;
    public function _initialize() {
        parent::_initialize();
        $token = I('get.token');
        $this->MemberDb = D('Member');
        $MemberTokenDB  = D('MemberToken');
        
        $memberInfo = $MemberTokenDB->getUser($token);
        
        $time = time();
        if(!$memberInfo) {
           $this->memberInfo =false;
           echo json_encode(array('result' => 'fail','error_code' => 40001,'error_info' => '用户校验失败'));
           exit;
        }
        
        $this->memberInfo = $memberInfo;
    }
    
    /**
     * 获取个人信息
     */
    public function me() {
        echo json_encode(array('result'=>'ok','user_info'=>$this->memberInfo));
    }
    
    
    /**
     * 获取用户所在地
     */
    public function regions() {
        $RegionDb = D('Region');
        $data = file_get_contents("php://input");
        $data = json_decode($data,true);
        $province = $RegionDb->getRegionByName($data['province'],array('level' => 1));
        $city = $RegionDb->getRegionByName($data['city'],array('level' => 2,'parent_id' => $province['region_id']));
        $district = $RegionDb->getRegionByName($data['district'],array('level' => 3,'parent_id' => $city['region_id']));
        
        $ret['region']['country_id'] = "1";
        $ret['region']['province_id'] = $province['region_id'];
        $ret['region']['city_id'] = $city['region_id'];
        $ret['region']['district_id'] = $district['region_id'];
        $ret['result'] = 'ok';
        echo json_encode($ret);
    }
    
    public function address() {
        $AddressDb = D('Address');
        $RegionDb = D('Region');
                    
        //添加地址
        if($this->isPost()) {
            $data = file_get_contents("php://input");
            $data = json_decode($data,true);
            
            $data['member_id'] = $this->memberInfo['member_id'];
            $data['province_id'] = $data['province'];
            $data['city_id'] = $data['city'];
            $data['district_id'] = $data['district'];
            $province = $RegionDb->getRegionById($data['province']);
            $city = $RegionDb->getRegionById($data['city']);
            $district = $RegionDb->getRegionById($data['district']);
            
            $data['full_address'] = $province['region_name'].$city['region_name'].$district['region_name'].$data['address'];
            
            $data['province'] = $province['region_name'];
            $data['city'] = $city['region_name'];
            $data['district'] = $district['region_name'];
            
            $data['created_time'] = time();
            $data['last_updated_time'] = time();
            $address_id = $AddressDb->add($data);
            
            $ret['result'] = 'ok';
            $ret['address_id'] = $address_id;
            echo json_encode($ret);
            exit;
        }
        
        //修改地址
        if($this->isPut()) {
            $address_id = I('get.address_id');
            $data = file_get_contents("php://input");
            $data = json_decode($data,true);
            
            $data['province_id'] = $data['province'];
            $data['city_id'] = $data['city'];
            $data['district_id'] = $data['district'];
            $province = $RegionDb->getRegionById($data['province']);
            $city = $RegionDb->getRegionById($data['city']);
            $district = $RegionDb->getRegionById($data['district']);
            
            $data['province'] = $province['region_name'];
            $data['city'] = $city['region_name'];
            $data['district'] = $district['region_name'];
            
            $data['full_address'] = $province['region_name'].$city['region_name'].$district['region_name'].$data['address'];
            
            $data['last_updated_time'] = time();
            
            $AddressDb->where(array('address_id' => $address_id,'member_id' => $this->memberInfo['member_id']))->save($data);
            
            $ret['result'] = 'ok';
            
            echo json_encode($ret);
            exit;
        }
        
        //获取地址
        if($this->isGet()) {
            $address_id = I('get.address_id');
            if($address_id) {
                $map['address_id'] = $address_id;
            } else {
                $map['status'] = 'DEFAULT';
            }
            $map['member_id'] = $this->memberInfo['member_id'];
            $ret['address'] = $AddressDb->getAddr($map);
            $ret['result'] = 'ok';

            echo json_encode($ret);
            exit;
        }
        
        //删除地址
        if($this->isDelete()) {
            $address_id = I('get.address_id');
            if($AddressDb->where(array('member_id' => $this->memberInfo['member_id'],'address_id' => $address_id))->delete()) {
                $ret['result'] = 'ok';
            } else {
                $ret['result'] = 'fail';
                $ret['error_info'] = '非法提交';
            }
            echo json_encode($ret);
            exit;
        }
    }
    
    /**
     * 地址列表
     */
    public function addresses() {
        $AddressDb = D('Address');
        if($this->isPut()) {
            $address_id = I('get.address_id');
            $rev = file_get_contents("php://input");
            $rev = json_decode($rev,true);
            
            $AddressDb->where(array('member_id' => $this->memberInfo['member_id']))->save(array('status' => 'COMMON'));
            $data['status'] = $rev['status'];
            
            $AddressDb->where(array('member_id' => $this->memberInfo['member_id'],'address_id' => $address_id))->save($data);
            
            $ret['result'] = 'ok';
            echo json_encode($ret);
            exit;
        }
        
        $ret['address_list'] = $AddressDb->getAddress(array('member_id'=>$this->memberInfo['member_id']));
        $ret['result'] = 'ok';
        echo json_encode($ret);
    }
    
    /**
     * 提交订单
     */
    public function orders() {
        if($this->isPost()) {
            $data = file_get_contents("php://input");
            $data = json_decode($data,true);
            
            $GoodsDb = D('Goods');
            $OrdersDb = D('Orders');
            $goodsInfo = $GoodsDb->getGoods(array('goods_id' => $data['goods_id']));
            
            if(!$goodsInfo) {
                echo json_encode(array('result'=>'fail','error_code'=>41002,'error_info'=>'该商品不存在'));
                return;
            }
            
            if($goodsInfo['goods_stock'] <= 0){
                echo json_encode(array('result'=>'fail','error_code'=>42042,'error_info'=>'商品已售罄，去首页逛逛吧'));
                return;
            }
            
            $AddressDb = D('Address');
            $map = array();
            $map['member_id'] = $this->memberInfo['member_id'];
            $map['address_id'] = $data['address_id'];
            $address = $AddressDb->getAddr($map);
            if(!$address){
                echo json_encode(array('result'=>'fail','error_code'=>41002,'error_info'=>'地址不存在'));
                return;
            }
            
            $input['buyer_id'] = $this->memberInfo['member_id'];
            $input['order_sn'] = date('YmdHis').rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);
            $input['goods_id'] = $data['goods_id'];
            $input['group_order_id'] = (int)$data['group_order_id'] ? (int)$data['group_order_id'] : 0;
            $input['group_buy'] = $data['groupbuy'];
            
            if($input['group_buy'] == 1) {
                $groupBuyCount = $OrdersDb->where(array('buyer_id'=>$this->memberInfo['member_id'],'group_buy'=>1,'order_status'=>array('not in',array(1,5))))->count();
                if($groupBuyCount >= $goodsInfo['limit_buy'] && $goodsInfo['limit_buy'] != 0){
                    echo json_encode(array('result'=>'fail','error_code'=>41002,'error_info'=>'该商品一人限购'.$goodsInfo['limit_buy'].'件'));
                    return;
                }
            }
            
            if($input['group_buy'] == 1 && $input['group_order_id'] == 0){
                $input['group_header'] = 1;
            }
            
            if($input['group_buy'] == 1 && $input['group_order_id'] > 0) {
                //加入团操作。。判断该团是否可以进行
                $GroupDb = D('Group');
                $groupInfo = $GroupDb->getGroup(array('group_order_id'=>$input['group_order_id']));
                
                if(!$groupInfo) {
                    echo json_encode(array('result'=>'fail','error_code'=>41003,'error_info'=>'该团不存在'));
                    return;
                }
                
                if($groupInfo['status'] == 1) {
                    echo json_encode(array('result'=>'fail','error_code'=>41003,'error_info'=>'该团已满员'));
                    return;
                }
                
                if($goodsInfo['status'] == 2) {
                    echo json_encode(array('result'=>'fail','error_code'=>41003,'error_info'=>'该团已关闭'));
                    return;
                }
                
                $orderMap['group_order_id'] = $groupInfo['group_order_id'];
                $orderMap['pay_sn'] = array('gt',0);
                $orderMap['pay_time'] = array('gt',0);
                $orderMap['buyer_id'] = $this->memberInfo['member_id'];
                if($OrdersDb->where($orderMap)->find()){
                    echo json_encode(array('result'=>'fail','error_code'=>41003,'error_info'=>'您已加入过此团'));
                    return;
                }
                
            }
            
            $input['province_id'] = $address['province_id'];
            $input['city_id'] = $address['city_id'];
            $input['district_id'] = $address['district_id'];
            
            $input['province_name'] = $address['province'];
            $input['city_name'] = $address['city'];
            $input['district_name'] = $address['district'];
            
            $input['mobile'] = $address['mobile'];
            
            $input['receive_name'] = $address['receive_name'];
            
            $input['nickname'] = $this->memberInfo['nickname'];
            
            $input['order_goods'] = serialize($goodsInfo);
            
            if($data['groupbuy'] == 1) {
                $input['goods_amount'] = $goodsInfo['group_price'];
                $input['order_amount'] = $goodsInfo['group_price'];
                $input['pay_amount'] = $goodsInfo['group_price'];
            } else {
                $input['goods_amount'] = $goodsInfo['alone_price'];
                $input['order_amount'] = $goodsInfo['alone_price'];
                $input['pay_amount'] = $goodsInfo['alone_price'];
            }
            $input['shipping_address'] = $address['full_address'];
            $input['shipping_amount'] = 0.00;
            $input['order_status'] = 0;
            
            $input['order_time'] = time();
            $order_id = $OrdersDb->add($input);
            
            if($order_id){
                echo json_encode(array('result'=>'ok','order_id'=>$order_id));
                return;
            }else{
                echo json_encode(array('result'=>'fail','error_code'=>41002,'error_info'=>'创建订单失败'));
                return;
            }
            
            exit;
        }
    }   
    
    public function wxpay() {
        $order_id = I('get.order_id');
        $OrdersDb = D('Orders');
        $orderInfo = $OrdersDb->getOrder(array('order_id'=>$order_id));
        if(!$orderInfo) {
            echo json_encode(array('result'=>'fail','error_code'=>41001,'error_info'=>'订单不存在'));
            return;
        }
        
        $GoodsDb = D('Goods');
        
        $goodsInfo = $GoodsDb->getGoods(array('goods_id' => $orderInfo['goods_id']));
        
        if(!$goodsInfo) {
            echo json_encode(array('result'=>'fail','error_code'=>41002,'error_info'=>'商品已下架或删除'));
            return;
        }
        
        if(!$orderInfo['goods_stock'] <= 0 ) {
            echo json_encode(array('result'=>'fail','error_code'=>41002,'error_info'=>'商品已售罄，去首页逛逛吧'));
            return;
        }
        
        if($orderInfo['group_buy'] == 1) {
            $groupBuyCount = $OrdersDb->where(array('buyer_id'=>$this->memberInfo['member_id'],'group_buy'=>1,'order_status'=>array('not in',array(1,5))))->count();
            if($groupBuyCount >= $goodsInfo['limit_buy'] && $goodsInfo['limit_buy'] != 0){
                echo json_encode(array('result'=>'fail','error_code'=>41002,'error_info'=>'该商品一人限购'.$goodsInfo['limit_buy'].'件'));
                return;
            }
        }
        
        if($orderInfo['group_buy'] == 1 && $orderInfo['group_order_id'] > 0) {
            $GroupDb = D('Group');
            $groupInfo = $GroupDb->getGroup(array('group_order_id'=>$orderInfo['group_order_id']));
            
            if(!$groupInfo){
                echo json_encode(array('result'=>'fail','error_code'=>41002,'error_info'=>'该团不存在'));
                return;
            }
            
            if($groupInfo['status'] == 1){
                echo json_encode(array('result'=>'fail','error_code'=>41002,'error_info'=>'该团已满员'));
                return;
            }
            
            if($groupInfo['status'] == 2){
                echo json_encode(array('result'=>'fail','error_code'=>41002,'error_info'=>'该团已关闭'));
                return;
            }
            
            $orderMap['group_order_id'] = $groupInfo['group_order_id'];
            $orderMap['pay_sn'] = array('gt',0);
            $orderMap['pay_time'] = array('gt',0);
            $orderMap['buyer_id'] = $this->memberInfo['member_id'];
            if($OrdersDb->where($orderMap)->find()){
                echo json_encode(array('result'=>'fail','error_code'=>41002,'error_info'=>'您已加入过此团'));
                return;
            }
        }
        
        //开始生成支付信息
        $time = time();
        $pay['pay_sn'] = date('YmdHis').rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);
        $pay['order_id'] = $orderInfo['order_id'];
        $pay['pay_amount'] = $orderInfo['pay_amount'];
        $pay['pay_start_time'] = $time;
        $pay['pay_status'] = 0;
        $PaylogDb = D('Paylog');
        $pay_id = $PaylogDb->add($pay);
        $payInfo = $PaylogDb->getPaylog(array('pay_id'=>$pay_id));
        
        if(!$payInfo) {
            echo json_encode(array('result'=>'fail','error_code'=>41001,'error_info'=>'生成支付订单失败'));
            return;
        }
        
        if($payInfo['pay_status'] == 1) {
            echo json_encode(array('result'=>'fail','error_code'=>41003,'error_info'=>'该订单已支付'));
            return;
        }
        import("@.Libary.wxpay.JsApiPay");
        $tools = new JsApiPay();
        
        $openId = $this->memberInfo['open_id'];
        $input = new WxPayUnifiedOrder();
        
        $input->SetBody(C('web_name') . "-" . $goodsInfo['cate_name']);
        $input->SetOut_trade_no($payInfo['pay_sn']);
        $input->SetTotal_fee($orderInfo['pay_amount'] * 100);
        $input->SetTime_start(date('Y-m-d'));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        
        $url = C('web_url').'wxpay_notify.php';
        $input->SetNotify_url($url);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        
        $order = WxPayApi::unifiedOrder($input);
        $PaylogDb->where(array('pay_id'=>$payInfo['pay_id']))->save(array('prepay_id' => $order['prepay_id']));

        $jsApiParameters = $tools->GetJsApiParameters($order);
        $jsApiParameters = json_decode($jsApiParameters,true);
        
        
        $ret['param'] = $jsApiParameters;
        $ret['result'] = 'ok';
        
        
        echo json_encode($ret);
    }
    
    /**
     * 订单详情
     */
    public function order() {
        if($this->isPost()) {
            $order_id = I('get.order_id');
           
            $OrderDb = D('Orders');
            $orderInfo = $OrderDb->getOrder(array('order_id' => $order_id,'buyer_id' => $this->memberInfo['member_id']));
            if(!$orderInfo) {
                echo json_encode(array('result'=>'fail','error_code'=>41001,'error_info'=>'订单不存在'));
            } 
            
            if($orderInfo['pay_sn'] != '' && $orderInfo['order_status'] != 0) {
                echo json_encode(array('result'=>'fail','error_code'=>41001,'error_info'=>'该订单不可取消,请联系客服'));
            }
            
            $OrderDb->setOrderStatus($orderInfo['order_id'],5);
            
            $ret['result'] = 'ok';
            
            echo json_encode($ret);
            exit;
        }
        
        $offset = I('get.offset');
        $size = I('get.size');
        $order_status = I('get.order_status');
        
        if($order_status != '') {
            $map['order_status'] = $order_status;
            
            if($order_status == 1){
                $map['order_status'] = array('between',array('1','3'));
            }
        }
        
        $OrdersDb  =  D('Orders');
        $map['buyer_id'] = $this->memberInfo['member_id'];
 
        $list = $OrdersDb->where($map)->order('order_time desc')->limit($offset,$size)->select();
        
        if(!$list) {
            $ret['order_list'] = array();
        } else {
            foreach ($list as $k => $v) {
                $v['order_goods'] = unserialize($v['order_goods']);   
                $ret['order_list'][] = $v;
            }
        }
         
        $ret['result'] = 'ok';
        
        echo json_encode($ret);exit;
    }
    
    /**
     * 根据获取订单详情
     */
    public function getorder() {
        $order_id = I('get.order_id');
        
        $OrdersDb = D('Orders');
        
        $orderInfo = $OrdersDb->getOrder(array('order_id' => $order_id,'buyer_id' => $this->memberInfo['member_id']));
        if (!$orderInfo) {
            echo json_encode(array('result'=>'fail','error_code'=>41001,'error_info'=>'订单不存在'));
            return;
        }
        $orderInfo['order_goods'] = unserialize($orderInfo['order_goods']);
        
        $ret['order'] = $orderInfo;
        $ret['result'] = 'ok';
        echo json_encode($ret);exit;
    }
    
    public function group_orders() {
        $id = I('get.id');
        
        $GroupDb = D('Group');
        $groupInfo = $GroupDb->getGroup(array('group_order_id'=>$id));
        
        if(!$groupInfo){
            echo json_encode(array('result'=>'fail','error_code'=>41001,'error_info'=>'该团不存在'));
            return;
        }
        
        $OrdersDb = D('Orders');
        
        $map = array();
        $map['group_order_id'] = $groupInfo['group_order_id'];
        $map['group_buy'] = 1;
        $map['order_status'] = array('not in','0,5');
        $orders = $OrdersDb->join(C('db_prefix') . 'member ON ' . C('db_prefix') . 'orders.buyer_id = ' . C('db_prefix') . 'member.member_id')->where($map)->order(C('db_prefix') . 'orders.group_header desc,' . C('db_prefix') . 'orders.pay_time asc')->select();
        
        foreach ($orders as $k => $v) {
            $tmp = array();
            $tmp['avatar'] = $v['headimgurl'];
            $tmp['join_time'] = $v['pay_time'];
            $tmp['nickname'] = $v['nickname'];
            $tmp['user_id'] = $v['member_id'];
            $user[] = $tmp;
        }
        
        $map = array();
        $map['group_order_id'] = $groupInfo['group_order_id'];
        $map['group_buy'] = 1;
        $map['group_header'] = 1;
        $map['order_status'] = array('not in','0,5');
        $order = $OrdersDb->join(C('db_prefix') . 'member ON ' . C('db_prefix') . 'orders.buyer_id = ' . C('db_prefix') . 'member.member_id')->where($map)->order('group_header asc,pay_time asc')->find();
        
        $order['order_goods'] = unserialize($order['order_goods']);
       
        $ret['result'] = 'ok';
        $ret['group_order'] = $groupInfo;
        $ret['group_order']['users'] = $user;
        $ret['group_order']['order'] = $order;
        $ret['group_order']['caller_id'] = $this->memberInfo['member_id'];
        echo json_encode($ret);
    }
    
    public function orders_count() {
    
        $OrdersDb  =  D('Orders');
        $map['buyer_id'] = $this->memberInfo['member_id'];
    
        $map['order_status'] = 0;
        $unpaidCount = $OrdersDb->where($map)->count();
    
    
        $map['order_status'] = array('between',array('1','3'));
        $unreceivedCount = $OrdersDb->where($map)->count();
    
    
        $ret['unpaid'] = $unpaidCount;
        $ret['unreceived'] = $unreceivedCount;
        $ret['result'] = 'ok';
    
        echo json_encode($ret);exit;
    }
    
    /**
     * 团购列表
     */
    public function groups() {
        $OrdersDb = D('Orders');
        
        $offset = I('get.offset');
        $size = I('get.size');
        $map['buyer_id'] = $this->memberInfo['member_id'];
        $map[C('db_prefix') . 'orders.group_order_id'] = array('gt',0);
        $map['group_buy'] = 1;
        $map['pay_sn'] = array('neq','');
        $map['pay_time'] = array('gt',0);
        $map['order_status'] = array(array('neq',0,array('neq',5),'AND'));
        
        $orders = $OrdersDb->join((C('db_prefix') . 'group ON ' . C('db_prefix') . 'orders.group_order_id = ' . C('db_prefix') . 'group.group_order_id'))->where($map)->limit($offset,$size)->order(C('db_prefix') . 'group.group_order_id desc')->select();
        if(!$orders) {
            $orders = array();
        }
        foreach ($orders as $k => $v) {
            $orders[$k]['order_goods'] = unserialize($v['order_goods']);
        }
        $ret['group_orders'] = $orders;
        $ret['result'] = 'ok';
        echo json_encode($ret);
    }
    
    /**
     * 取消订单
     */
    public function cancelOrder() {
        $order_id = I('get.order_id');
        
        $OrdersDb  = D('Orders');
        
        $orderInfo = $OrdersDb->getOrder(array('order_id' => $order_id,'buyer_id' => $this->memberInfo['member_id']));
        
        $OrdersDb->where(array('order_id' => $order_id,'buyer_id' => $this->memberInfo['member_id']))->save(array('order_status' => 5));
        
        $ret['result'] = 'ok';
        
        echo json_encode($ret);
    }
    
    /**
     * 页面分享回调方法
     */
    public function share() {
        if($this->isPost()) {
            $data = file_get_contents('php://input');
            $data = json_decode($data,true);
            
            $data['share_time'] = time();
            $data['member_id'] = $this->memberInfo['member_id'];
            
            D("Share")->add($data);
            
            $ret['result'] = 'ok';
            echo json_encode($ret);exit;
        }
    }
    
    /**
     * 确认发货
     */
    public function receivedOrder() {
        if($this->isPost()) {
            $order_id = I('get.order_id');
            
            D('Orders')->where(array('order_id' => $order_id))->save(array('order_status' => 4,'received_time' => time()));
            
            $ret['result'] = 'ok';
            echo json_encode($ret);
            exit;
        }
    }
    
    
    
    
}