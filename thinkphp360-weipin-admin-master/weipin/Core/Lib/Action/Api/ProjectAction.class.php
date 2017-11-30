<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ProjectAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:上午9:53:35
 * ----------------------------------------------------------------------------
 */
class ProjectAction extends ApiAction {
    public function data_version() {
        $type = I('get.type');
        switch ($type) {
            case 'region_list':
                $ret['result'] = 'ok';
                $ret['data_version']['data_key'] = 'region_list';
                $ret['data_version']['version'] = '234';
                break;
            default:
                $ret['result'] = 'fail';
                $ret['error_code'] = 42000;
                $ret['error_info'] = '非法参数';
                break;
        }
        echo json_encode($ret);
    }
    
    public function regions() {
        $RegionDb = D('Region');
        $city[1] = $RegionDb->getRegion(array('level'=>1));
        $city[2] = $RegionDb->getRegion(array('level'=>2));
        $city[3] = $RegionDb->getRegion(array('level'=>3));
        
        
        $ret['regions'] = $city;
        $ret['result'] = 'ok';
        
        echo json_encode($ret);
    }
    
    public function express() {
        $order_id = I('get.order_id');
        
        $OrdersDb = D('Orders');
        $orderInfo = $OrdersDb->getOrder(array('order_id' => $order_id));
        
        if(!$orderInfo) {
            echo json_encode(array('result' => 'fail','error_code' => 41001,'error_info' => '该订单不存在'));
            return;
        }
        
        $url = 'http://www.kuaidi100.com/query?type='.$orderInfo['shipping_code'].'&postid='.$orderInfo['tracking_number'].'&id=1&valicode=&temp='.random(4).'&sessionid=&tmp='.random(4);
        $content = file_get_contents($url);
        $content = json_decode($content,true);
        if($content['status'] != 200) {
            echo json_encode(array('result'=>'fail','error_code'=>41001,'error_info'=>'查询物流信息出错'));
            return;
        }
        
        $traces = array();
        foreach($content['data'] as $k=>$v) {
            $tmp = array();
            $tmp['time'] = $v['time'];
            $tmp['address'] = $v['address'];
            $tmp['remark'] = $v['context'];
            $traces[] = $tmp;
        }
        $ret['shipping']['traces'] = $traces;
        $ret['shipping']['shipper'] = $orderInfo['shipping_name'];
        $ret['shipping']['tracking_number'] = $orderInfo['tracking_number'];
        $ret['result'] = 'ok';
        
        echo json_encode($ret);
    }

    public function crontab() {
        $filename = 'crontab.txt';
        $word = date('Y-m-d H:i:s',time() . '\r\n');
        $fh = fopen($filename, "a");
        fwrite($fh,$word);
        fclose($fh);
        
        //团即将过期提醒
        $this->groupMsgCrontab();
        
        //团 超时退款
        
        $this->groupRefound();
    }
    
    public function groupMsgCrontab() {
        $GroupDb = D('Group');
        $OrdersDb = D('Orders');
        $PaylogDb = D('Paylog');

        $map = array();
        $map['group_buy'] = 1;
        $map['pay_sn'] = array('neq','');
        $map['pay_time'] = array('gt',0);
        $map['notify'] = 0;
        $map['status'] = 0;
        $time = time() + 3600 * C('group.notify_hour');
        $map['expire_time'] = array(array('lt',$time),array('gt',time()),'AND');
        
        $orders = $OrdersDb->join(C('db_prefix').'member ON '.C('db_prefix').'orders.buyer_id = '.C('db_prefix').'member.member_id')->join(C('db_prefix').'group ON '.C('db_prefix').'orders.group_order_id = '.C('db_prefix').'group.group_order_id')->where($map)->select();
        
		if($orders) {
            foreach ($orders as $v) {
                $groupIds[] = $v['group_order_id'];
                $v['order_goods'] = unserialize($v['order_goods']);

                $msg = '您参加的 '.$v['order_goods']['goods_name'].'还有'.C('group.notify_hour').'小时到期,目前还差'.($v['require_num'] - $v['people']).'人,快去叫上身边的小伙伴一起' . C('web_name') . '吧';
                if(C('weapp_tpl.open') == 1){
                    //发送团即将过期提醒
                    $data['keyword1'] = array('value'=> $v['order_goods']['goods_name'],'color'=>'#000');
                    $data['keyword2'] = array('value'=> C('group.notify_hour') . '小时','color'=>'#000');
                    $data['keyword3'] = array('value'=> $v['require_num'] - $v['people'] . '人' ,'color'=>'#000');
                    $data['keyword4'] = array('value'=> $msg,'color'=>'#000');

                    $payInfo = $PaylogDb->getPaylog(array('pay_id' => $v['pay_id']));
                    send_weapp_msg($v['open_id'],C('weapp_tpl.group_notify_tpl'),'pages/group?id='.$v['group_order_id'],$payInfo['prepay_id'],$data,'keyword1.DATA');
                }

            }
        }
        
        $map = array();
        $map['group_order_id'] = array('in',$groupIds);
        $GroupDb->where($map)->save(array('notify'=>1));
    }
    
    public function groupRefound() {
        $OrdersDb = D('Orders');
        $GroupDb = D('Group');
        $PaylogDb = D('Paylog');
        $time = time();
        
        $map['expire_time'] = array('lt',$time);
        $map['status'] = 0;
        $groups = $GroupDb->where($map)->select();
        if($groups) {
            foreach($groups as $v){
                $groupIds[] = $v['group_order_id'];
            }
            $GroupDb->where(array('group_order_id'=>array('in',$groupIds)))->save(array('status'=>2));
        }
        
        $map = array();
        $map['group_buy'] = 1;
        $map[C('db_prefix') . 'orders.pay_sn'] = array('neq','');
        $map['pay_time'] = array('gt',0);
        $map['group_order_id'] = array('in',$groupIds);
        $orders = $OrdersDb->join(C('db_prefix').'member ON '.C('db_prefix').'orders.buyer_id = '.C('db_prefix').'member.member_id')->join(C('db_prefix').'paylog ON '.C('db_prefix').'orders.pay_id = '.C('db_prefix').'paylog.pay_id')->where($map)->select();
        
        if($orders) {
            foreach($orders as $v) {
                $ret = $this->refound($v['transaction_id'],$v['pay_amount'] *100 , $v['pay_amount'] *100);
                
                if($ret){
                    $PaylogDb->where(array('pay_id'=>$v['pay_id']))->save(array('refound_id'=>$ret['out_refund_no']));
                    $OrdersDb->where(array('order_id'=>$v['order_id']))->save(array('order_status'=>7));
                }else{
                    $OrdersDb->where(array('order_id'=>$v['order_id']))->save(array('order_status'=>6));
                }
                              
                $msg = '您的订单退款申请已经提交给微信处理了哦~微信系统需要审核，微信最迟5个工作日内会退款到您的支付账户哦~谢谢您的支持！';

                if(C('weapp_tpl.open') == 1){
                    //发送退款通知
                    $goodsInfo = unserialize($v['order_goods']);
                    $data['keyword1'] = array('value'=>$v['order_sn'],'color'=>'#000');
                    $data['keyword2'] = array('value'=>$goodsInfo['goods_name'],'color'=>'#000');
                    $data['keyword3'] = array('value'=> $v['pay_amount'] . "",'color'=>'#000');
                    $data['keyword4'] = array('value'=> date('Y-m-d H:i:s',time()) ,'color'=>'#000');
                    $data['keyword5'] = array('value'=> $msg,'color'=>'#000');

                    $payInfo = $PaylogDb->getPaylog(array('pay_id' => $v['pay_id']));
                    send_weapp_msg($v['open_id'],C('weapp_tpl.refound_notify_tpl'),'pages/order?id='.$v['order_id'],$payInfo['prepay_id'],$data,'keyword1.DATA');
                }
            }
        }
            
    }
    
    private function refound($transaction_id,$total_fee,$refund_fee) {
        import("@.Libary.wxpay.JsApiPay");
        $input = new WxPayRefund();
        $input->SetTransaction_id($transaction_id);
        $input->SetTotal_fee($total_fee);
        $input->SetRefund_fee($refund_fee);
        $input->SetOut_refund_no(WxPayConfig::getMchid().date("YmdHis").rand(0,9).rand(0,9).rand(0,9).rand(0,9));
        $input->SetOp_user_id(WxPayConfig::getMchid());
        $ret = WxPayApi::refund($input);
        
        if($ret['result_code'] = 'SUCCESS'){
            return $ret;
        } else {
            return false;
        }
    }

    /**
     * 下单公告
     */
    public function do_order_banner() {
        exit(json_encode(array('result' => 'ok','shipping' => C('shipping_banner'))));
    }
}