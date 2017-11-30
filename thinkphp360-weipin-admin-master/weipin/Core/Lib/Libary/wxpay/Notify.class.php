<?php
ini_set('date.timezone','Asia/Shanghai');
error_reporting(E_ERROR);

require_once "WxPay.Api.php";
require_once 'WxPay.Notify.php';
//require_once 'Log.php';

//初始化日志
//$logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');


//$log = Log::Init($logHandler, 15);

class PayNotifyCallBack extends WxPayNotify
{
	//查询订单
	public function Queryorder($transaction_id)
	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = WxPayApi::orderQuery($input);
		//Log::DEBUG("query:" . json_encode($result));
        log::record("query:" . json_encode($result),log::DEBUG);
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return $result;
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{
		//Log::DEBUG("call back:" . json_encode($data));
        log::record("call back:" . json_encode($data),log::DEBUG);
		$notfiyOutput = array();
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
        $order_result = $this->Queryorder($data["transaction_id"]);
		if(!$order_result){
			$msg = "订单查询失败";
			return false;
		}

        if($order_result['trade_state'] != 'SUCCESS'){
            $msg = "订单未支";
            return false;
        }

        log::record('call back: 订单'.$data['out_trade_no'].'支付成功开始支付后业务',log::WARN);

        $PaylogDb = D('Paylog');
        $OrdersDb = D('Orders');
        $payInfo = $PaylogDb->getPaylog(array('pay_sn'=>$data['out_trade_no']));

        if(!$payInfo){
            $msg = "支付订单号不存在";
            log::record('call back: 订单'.$data['out_trade_no'].'支付订单号不存在',log::WARN);
            return true;
        }


        $orderInfo = $OrdersDb->getOrder(array('order_id'=>$payInfo['order_id']));

        if(!$orderInfo){
            $msg = "订单不存在";
            log::record('call back: 订单'.$data['out_trade_no'].'订单号不存在',log::WARN);
            return true;
        }

        if($orderInfo['order_status'] != 0){
            $msg = "该订单已支付";
            log::record('call back: 订单'.$data['out_trade_no'].'已支付',log::WARN);
            return true;
        }
        $GroupDb = D('Group');
        if($orderInfo['group_order_id'] > 0){
            $groupInfo = $GroupDb->getGroup(array('group_order_id'=>$orderInfo['group_order_id']));

            if(!$groupInfo){
                $msg = "订单不存在";
                log::record('call back: 订单'.$data['out_trade_no'].'中团'.$orderInfo['group_order_id'].'不存在,加入团失败',log::WARN);
                return true;
            }
        }


        //修改支付订单状态
        $time = time();
        $PaylogDb->where(array('pay_id'=>$payInfo['pay_id']))->save(array('pay_done_time'=>$time,'pay_status'=>1,'pay_type'=>'微信JSAPI支付','transaction_id'=>$data['transaction_id']));

        $GoodsDb = D('Goods');
        $goodsInfo = $GoodsDb->getGoods(array('goods_id'=>$orderInfo['goods_id']));


        $orderData['pay_sn'] = $payInfo['pay_sn'];
        $orderData['pay_id'] = $payInfo['pay_id'];
        $orderData['pay_time'] = $time;
        $orderData['order_status'] = 1;
        if($orderInfo['group_buy'] == 1){

            //是团购
            if($orderInfo['group_order_id'] == 0){
                //新开团
                $group['require_num'] = $goodsInfo['group_number'];
                $group['people'] = 1;
                $group['status'] = 0;
                $group['create_time'] = time();
                $group['expire_time'] = time()+3600* C('group.expire_time');
                $group['owner_id'] = $orderInfo['buyer_id'];
                $group_id = $GroupDb->add($group);
                $orderData['group_order_id'] = $group_id;
            }else{
                //加入团
                $groupData['people'] = $groupInfo['people'] + 1;

                if($groupData['people'] == $groupInfo['require_num'] ){
                    $groupData['status'] = 1;
                    $groupData['success_time'] = time();
                }
                $GroupDb->where(array('group_order_id'=>$groupInfo['group_order_id']))->save($groupData);
            }

        }

        $OrdersDb->where(array('order_id'=>$orderInfo['order_id']))->save($orderData);
        
        $GoodsDb->where(array('goods_id'=>$goodsInfo['goods_id']))->setDec('goods_stock');
        $GoodsDb->where(array('goods_id'=>$goodsInfo['goods_id']))->setInc('sell_count');
        
        $userInfo = D('Member')->getMember(array('member_id'=>$orderInfo['buyer_id']));
        if(C('weapp_tpl.open') == 1){
            //发送付款通知
            $data['first'] = array('value'=>'尊敬的会员，您已成功付款。','color'=>'#000');
            $data['keyword1'] = array('value'=>$orderInfo['order_sn'],'color'=>'#000');
            $data['keyword2'] = array('value'=>$goodsInfo['goods_name'],'color'=>'#000');
            $data['keyword3'] = array('value'=>$payInfo['pay_amount'] . "",'color'=>'#000');
            $data['keyword4'] = array('value'=>'微信支付','color'=>'#000');

            $data['keyword5'] = array('value'=>date('Y-m-d H:i:s',$orderInfo['order_time']),'color'=>'#000');
            $data['remark'] = array('value'=>'感谢您购买！','color'=>'#000');
            
            send_weapp_msg($userInfo['open_id'],C('weapp_tpl.pay_notify_tpl'),'pages/order?id='.$orderInfo['order_id'],$payInfo['prepay_id'],$data,'keyword1.DATA');
        }

		return true;
	}
}


