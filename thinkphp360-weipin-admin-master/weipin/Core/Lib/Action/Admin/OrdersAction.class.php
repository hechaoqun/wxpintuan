<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：OrdersAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:下午2:14:12
 * ----------------------------------------------------------------------------
 */
 class OrdersAction extends AdminAction {
     public function index() {
         $map = array();
         
         $get = I('get.search');
         
         foreach ($get as $k => $v) {
             if($v != '' && $v != - 1) {
                 $map[$k] = $v;
             }
             if($v == 999) {
                 $map[$k] = array(1,2,"OR");
             }
         }
         
         if(isset($map['start_time']) && isset($map['end_time'])) {
             $map['order_time'] = array(array('egt',strtotime($map['start_time'])),array('elt',strtotime($map['end_time'])),'AND');
             unset($map['start_time']);
             unset($map['end_time']);
         }
         if(isset($map['start_time'])) {
             $map['order_time'] = array('egt',strtotime($map['start_time']));
             unset($map['start_time']);
         }
         
         if(isset($map['end_time'])) {
             $map['order_time'] = array('elt',strtotime($map['end_time']));
             unset($map['end_time']);
         }
         
         import('ORG.Util.Page');
         
         $order = 'order_id desc';
         $OrdersDb = D('Orders');
         $count = $OrdersDb->where($map)->count();
         
         $Page = new Page($count,C('web_admin_pagenum'));
         $nowPage = isset($_GET['p']) ? $_GET['p'] : 1;
         $show = $Page->show();// 分页显示输出
         
         $list = $OrdersDb->field('*,'.C('db_prefix').'orders.group_order_id as order_group_order_id')->join(C('db_prefix').'member ON '.C('db_prefix').'orders.buyer_id = '.C('db_prefix').'member.member_id')->join(C('db_prefix').'group ON '.C('db_prefix').'orders.group_order_id = '.C('db_prefix').'group.group_order_id')->where($map)->order($order)->page($nowPage.','.C('web_admin_pagenum'))->select();
            
         if(I('get.export') == 1) {
             $this->order_export($map);
         }
         $this->assign('list',$list);
         $this->assign('page',$show);// 赋值分页输出
         
         $this->display();
     }
     
     public function edit() {
         $id = $this->_get('id','intval',0);
         if(!$id) $this->error('参数错误！');
         $OrderDb = D('Orders');
         
         $MemberDb = D('Member');
         $PaylogDb = D('Paylog');
         $GroupDb = D('Group');
         $orderInfo = $OrderDb->getOrder(array('order_id' => $id));
         
         $payInfo = $PaylogDb->getPaylog(array('pay_id' => $orderInfo['pay_id']));
         $orderInfo['order_goods'] = unserialize($orderInfo['order_goods']);
         $memberInfo = $MemberDb->getMember(array('member_id' => $orderInfo['buyer_id']));
         
         if($orderInfo['group_order_id'] > 0 && $orderInfo['pay_time'] > 0){
             $group = $GroupDb->getGroup(array('group_order_id'=>$orderInfo['group_order_id']));
         }
         
         $nextOrder = $OrderDb->where(array('order_id' => array('gt',$orderInfo['order_id'])))->find();
         $preOrder = $OrderDb->where(array('order_id' => array('lt',$orderInfo['order_id'])))->find();
         $log = D('OrderLog')->where(array('order_id' => $id))->select();
         
         $this->assign('preOrder',$preOrder);
         $this->assign('nextOrder',$nextOrder);
         $this->assign('memberInfo',$memberInfo);
         $this->assign('log',$log);
         $this->assign('group',$group);
         $this->assign('info',$orderInfo);
         $this->assign('payinfo',$payInfo);
         
         $this->display();
     }
     
     public function cancel() {
         $order_id = $this->_post('order_id','intval',0);
         
         if(!$order_id) {
             $ret['result'] = 'fail';
         }
         $OrderDb = D('Orders');
         
         if($OrderDb->where(array('order_id' => $order_id))->save(array('order_status' => 5))) {
             $this->order_log($order_id,I('post.remark'),'取消订单');
             $ret['result'] = 'ok';
         } else {
             $ret['result'] = 'fail';
         }
         echo json_encode($ret);
     }
     
     private function order_log($order_id,$remark,$do) {
         $log['manage'] = session('username');
         $log['order_id'] = $order_id;
         $log['remark'] = $remark;
         $log['time'] = time();
         $log['do'] = $do;
         
         D('OrderLog')->add($log);
     }
     
     public function confirm() {
         $order_id = $this->_post('order_id','intval',0);
         
         if(!$order_id) {
             $ret['result'] = 'fail';
         }
         $OrderDb = D('Orders');
         
         if($OrderDb->where(array('order_id' => $order_id))->save(array('order_status' => 2))) {
             $this->order_log($order_id,I('post.remark'),'确认订单');
             $ret['result'] = 'ok';
         } else {
             $ret['result'] = 'fail';
         }
         
         echo json_encode($ret);
     } 
     
     /**
      * 发货
      */
     public function delivery(){
         $ShippingDb = D('Shipping');
         $PaylogDb = D('Paylog');
         if(IS_POST){
             $shipping_id = I('post.shipping_id');
             $shipping = $ShippingDb->where(array('shipping_id'=>$shipping_id))->find();
             $order_id = I('get.order_id');
     
             $Orders = D('Orders');
             $orderInfo = $Orders->getOrder(array('order_id'=>$order_id));
             $MemberDb = D('Member');
             $memberInfo = $MemberDb->getMember(array('member_id'=>$orderInfo['buyer_id']));
             $data['shipping_time'] = time();
             $data['shipping_name'] = $shipping['shipping_name'];
             $data['shipping_code'] = $shipping['shipping_code'];
             $data['tracking_number'] = I('post.tracking_number');
             $data['order_status'] = 3;
             if($Orders->where(array('order_id'=>$order_id))->save($data)){
     
                 $data['first'] = array('value'=>'尊敬的会员，您的订单已发货。','color'=>'#000');
                 $data['keyword1'] = array('value'=>$memberInfo['nickname'],'color'=>'#000');
                 $data['keyword2'] = array('value'=>$orderInfo['shipping_address'],'color'=>'#000');
                 $data['keyword3'] = array('value'=>$orderInfo['order_sn'],'color'=>'#000');
                 $data['keyword4'] = array('value'=>$shipping['shipping_name'],'color'=>'#000');
     
                 $data['keyword5'] = array('value'=>$data['tracking_number'],'color'=>'#000');
                 $data['remark'] = array('value'=>'感谢您购买！','color'=>'#000');

                 $payInfo = $PaylogDb->getPaylog(array('pay_id'=>$orderInfo['pay_id']));
                 if(C('weapp_tpl.open') == 1){
                     send_weapp_msg($memberInfo['open_id'],C('weapp_tpl.shipping_notify_tpl'),'pages/order?id='.$orderInfo['order_id'],$payInfo['prepay_id'],$data,'keyword1.DATA');
                 }

                 $this->order_log($order_id,'','发货');
                 //$this->success('发货成功！');

                 header("Content-type:text/html;charset=utf-8");
                 echo "<script>alert('发货成功');window.top.main.location.reload();window.top.art.dialog({id:'delivery'}).close();</script>";
                 exit;
             }

             $this->error('发货失败！');
             exit;
         }
     
         $shipping = $ShippingDb->select();
         $this->assign('shipping',$shipping);
         $this->display();
     }
     
     public function refound() {
         $id = I('get.order_id');
         $OrderDb= D('Orders');
         $PaylogDb = D('Paylog');
         $map[C('db_prefix') . 'orders.order_id'] = $id;
         $orderInfo = $OrderDb->join(C('db_prefix') . 'member ON ' . C('db_prefix') . 'orders.buyer_id = ' . C('db_prefix') . 'member.member_id')
         ->join(C('db_prefix') . 'paylog ON ' . C('db_prefix') . 'orders.pay_id = ' . C('db_prefix') . 'paylog.pay_id')->where($map)->find();
         
         if(IS_POST) {
             $noSend = array(0,1,2);
             $isSend = array(3,4);
             $noFound = array(5,6,7,8,9);

             header("Content-type:text/html;charset=utf-8");
             if(in_array($orderInfo['order_status'],$noFound)) {
                 echo "<script>alert('该订单不可退款');window.top.main.location.reload();window.top.art.dialog({id:'refound'}).close();</script>";
             }
             $refound_money = I('post.refound_money');
             import('@.Libary.wxpay.JsApiPay');
             
             $transaction_id = $orderInfo['transaction_id'];
             $total_fee = $refound_money * 100;
             $refound_money = $refound_money * 100;
             $input = new WxPayRefund();
             $input->SetTransaction_id($transaction_id);
             $input->SetTotal_fee($total_fee);
             $input->SetRefund_fee($refound_money);
             $input->SetOut_refund_no(WxPayConfig::getMchid() . date('YmdHis') . rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9));
             $input->SetOp_user_id(WxPayConfig::getMchid());
             $ret = WxPayApi::refund($input);
            
             if($ret['return_code'] == "SUCCESS") {
                 if(in_array($orderInfo['order_status'], $noSend)) {
                    $orderData['order_status'] = 7;   
                 }
                 if(in_array($orderInfo['order_status'], $isSend)) {
                     $orderData['order_status'] = 9;
                 }    
                 $msg = '您的订单退款申请已经提交给微信处理了哦~微信系统需要审核，微信最迟5个工作日内会退款到您的支付账户哦~谢谢您的支持！';

                 $userInfo = D('Member')->getMember(array('member_id'=>$orderInfo['buyer_id']));
                 $payInfo = $PaylogDb->getPaylog(array('pay_id'=>$orderInfo['pay_id']));
                 $goodsInfo = unserialize($orderInfo['order_goods']);
                 if(C('weapp_tpl.open') == 1){
                     //发送退款通知
                     $data['keyword1'] = array('value'=>$orderInfo['order_sn'],'color'=>'#000');
                     $data['keyword2'] = array('value'=>$goodsInfo['goods_name'],'color'=>'#000');
                     $data['keyword3'] = array('value'=> "" . I('post.refound_money'),'color'=>'#000');
                     $data['keyword4'] = array('value'=> date('Y-m-d H:i:s',time()) ,'color'=>'#000');
                     $data['keyword5'] = array('value'=> $msg,'color'=>'#000');

                     send_weapp_msg($userInfo['open_id'],C('weapp_tpl.refound_notify_tpl'),'pages/order?id='.$orderInfo['order_id'],$payInfo['prepay_id'],$data,'keyword1.DATA');
                 }

                 $this->order_log($orderInfo['order_id'],'','退款');
                 
                 $OrderDb->where(array('order_id' => $id))->save($orderData);
                 echo "<script>alert('退款成功  ');window.top.main.location.reload();window.top.art.dialog({id:'refound'}).close();</script>";
             } else {
                 echo "<script>alert('退款失败');window.top.main.location.reload();window.top.art.dialog({id:'refound'}).close();</script>";
             }
                 
             exit;
         }
                  
         $this->assign('orderInfo',$orderInfo);
         $this->display();
     }
     
     public function order_export($map) {
         //        $str = '123456789';
         //        $new = '';
         //        for ($i=0; $i<strlen($str); ++$i) {
         //
         //            var_dump(chr(ord('a') + intval($str{$i}) - 1));
         //        }
         //
         //        exit;
         
         import('ORG.Util.Page');// 导入分页类
         
         import("@.Libary.PHPExcel.PHPExcel");
         $resultPHPExcel = new PHPExcel();
         
         $orderby = 'order_id desc';
         $OrdersDb = D('Orders');
         $count = $OrdersDb->where($map)->count();
         $Page  = new Page($count,C('web_admin_pagenum'));// 实例化分页类 传入总记录数
         // 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
         
         $maxPage = ceil($count/C('web_admin_pagenum'));
         $line = 2;
         
         $order_field_name = C('export.order_field_name');  
         
         
         $order_field_name = explode(',',substr($order_field_name,0,strlen($order_field_name)-1));
         $order_field_i = 1;
         foreach($order_field_name as $v){
             $col = strtoupper(chr(ord('a') + $order_field_i - 1));
             $resultPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
             $resultPHPExcel->getActiveSheet()->getStyle($col . $line)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
             $resultPHPExcel->getActiveSheet()->setCellValueExplicit($col . '1',$v,PHPExcel_Cell_DataType::TYPE_STRING);
             $order_field_i++;
         }
         
         for ($nowPage = 1; $nowPage <= $maxPage; $nowPage++){
         
         
             $list = $OrdersDb->field('*,'.C('db_prefix').'orders.group_order_id as order_group_order_id')->join(C('db_prefix').'member ON '.C('db_prefix').'orders.buyer_id = '.C('db_prefix').'member.member_id')->join(C('db_prefix').'group ON '.C('db_prefix').'orders.group_order_id = '.C('db_prefix').'group.group_order_id')->where($map)->order($orderby)->page($nowPage.','.C('web_admin_pagenum'))->select();
         
         
             $order_field = C('export.order_field');
             $order_field = explode(',',substr($order_field,0,strlen($order_field)-1));
         
             foreach($list as $v){
                 $v['order_goods'] = unserialize($v['order_goods']);
                 $order_field_i = 1;
                 foreach($order_field as $vv){
                     $col = strtoupper(chr(ord('a') + $order_field_i - 1));
         
                     if(strpos($vv,'.')){
                         $arr = explode('.',$vv);
                         $fieldValue = $v[$arr[0]][$arr[1]];
                     }else{
                         $fieldValue = $v[$vv];
                     }
         
                     if($vv == 'order_status'){
                         $order_status = array('待支付','已支付，未确认','已确认，待发货','配送中','已签收','交易已取消','未发货退款处理中','未发货退款成功','已发货退款处理中','已发货退款成功');
         
                         $fieldValue = $order_status[$v[$vv]];
                     }
         
         
         
                     $resultPHPExcel->getActiveSheet()->setCellValueExplicit($col . $line, (string)$fieldValue,PHPExcel_Cell_DataType::TYPE_STRING);
         
                     $order_field_i++;
                 }
         
                 $line++;
             }
         }
         
         $outputFileName = '导出订单'.date('Y-m-d-H-i-s').'.xls';
         $xlsWriter = new PHPExcel_Writer_Excel5($resultPHPExcel);
         //ob_start(); ob_flush();
         header("Content-Type: application/force-download");
         header("Content-Type: application/octet-stream");
         header("Content-Type: application/download");
         header('Content-Disposition:inline;filename="'.$outputFileName.'"');
         header("Content-Transfer-Encoding: binary");
         header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
         header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
         header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
         header("Pragma: no-cache");
         
         if(!is_dir(RUNTIME_PATH.'Xls/')) {
             mkdir(RUNTIME_PATH.'Xls/');
         }
         $finalFileName  = RUNTIME_PATH.'Xls/'.time().'.xls';
         $xlsWriter->save($finalFileName);
         echo file_get_contents($finalFileName);
     }
     
     /**
      * 批量发货
      */
     public function batch_delivery(){
     
         if(IS_POST){
             import('ORG.Net.UploadFile');
             $upload_config = C('upload_config');
             $upload_config['allowExts'] = array('xls');
             $upload_config['savePath'] = RUNTIME_PATH.'Xls/';
     
             $upload = new UploadFile($upload_config);// 实例化上传类
             if(!$upload->upload()) {// 上传错误提示错误信息
                 $this->error($upload->getErrorMsg());
             }else{// 上传成功 获取上传文件信息
                 $info =  $upload->getUploadFileInfo();
             }
     
     
             import("@.Libary.PHPExcel.PHPExcel");
     
             $reader = PHPExcel_IOFactory::createReader('Excel5'); //设置以Excel5格式(Excel97-2003工作簿)
             $objPHPExcel = $reader->load($info[0]['savepath'].$info[0]['savename']); // 载入excel文件
     
             $sheet = $objPHPExcel->getSheet(0);
             $highestRow = $sheet->getHighestRow(); // 取得总行数
             $highestColumn = $sheet->getHighestColumn(); // 取得总列数
             $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn);
     
     
             $lists = array();
             for($j=2;$j<=$highestRow;$j++){
     
                 $shipping_id = $sheet->getCellByColumnAndRow($highestColumnNum-2,$j)->getValue();
                 $tracking_number =  $sheet->getCellByColumnAndRow($highestColumnNum-1,$j)->getValue();
     
                 $order_id = $sheet->getCellByColumnAndRow(0,$j)->getValue();
                 $order_sn = $sheet->getCellByColumnAndRow(1,$j)->getValue();
                 $ShippingDb = D('Shipping');
                 $shipping = $ShippingDb->where(array('shipping_id'=>$shipping_id))->find();
     
                 $list = array();
     
                 $list['order_id'] = $order_id;
     
                 $list['order_sn'] = $order_sn;
     
                 $list['shipping'] = $shipping;
                 $list['tracking_number'] = $tracking_number;
     
                 $lists[] = $list;
     
     
                 /*
                  $Orders = D('Orders');
                  $orderInfo = $Orders->getOrder(array('order_id'=>$order_id));
     
                  if($orderInfo['tracking_number'] != ''){
                  echo '订单'.$order_sn."已经发过货了<br/>";
                  continue;
                  }
                  $MemberDb = D('Member');
                  $memberInfo = $MemberDb->getMember(array('member_id'=>$orderInfo['buyer_id']));
                  $data['shipping_time'] = time();
                  $data['shipping_name'] = $shipping['shipping_name'];
                  $data['shipping_code'] = $shipping['shipping_code'];
                  $data['tracking_number'] = $tracking_number;
                  $data['order_status'] = 3;
                  if($Orders->where(array('order_id'=>$order_id))->save($data)){
     
                  $data['first'] = array('value'=>'尊敬的会员，您的订单已发货。','color'=>'#000');
                  $data['keyword1'] = array('value'=>$memberInfo['nickname'],'color'=>'#000');
                  $data['keyword2'] = array('value'=>$orderInfo['shipping_address'],'color'=>'#000');
                  $data['keyword3'] = array('value'=>$orderInfo['order_sn'],'color'=>'#000');
                  $data['keyword4'] = array('value'=>$shipping['shipping_name'],'color'=>'#000');
     
                  $data['keyword5'] = array('value'=>$data['tracking_number'],'color'=>'#000');
                  $data['remark'] = array('value'=>'感谢您购买！','color'=>'#000');
     
                  send_wechat_msg($memberInfo['open_id'],C('wechat_tpl.shipping_notify_tpl'),C('app_url').'order.html?id='.$orderInfo['order_id'],$data);
     
                  $this->order_log($order_id,'','发货');
                  //$this->success('发货成功！');
     
                  echo '订单'.$order_sn."发货成功<br/>";
     
                  }
     
                  */
     
             }
             $this->assign('lists',$lists);
             $this->display('delivery_list');
             exit;
         }
         $this->display();
     }
     
     
     public function delivery_submit(){
         $order = I('post.order');
     
         foreach($order as $v){
             $arr = explode(',',$v);
     
             $order_id = $arr[0];
     
             $shipping['shipping_name'] = $arr[1];
             $shipping['shipping_code'] = $arr[2];
             $tracking_number = $arr[3];
             $Orders = D('Orders');
             $orderInfo = $Orders->getOrder(array('order_id'=>$order_id));
             $order_sn = $orderInfo['order_sn'];
             if($orderInfo['tracking_number'] != ''){
                 header("Content-type:text/html;charset=utf-8");
                 echo '订单'.$order_sn."已经发过货了<br/>";
                 continue;
             }

             $PaylogDb = D('Paylog');
             $MemberDb = D('Member');
             $memberInfo = $MemberDb->getMember(array('member_id'=>$orderInfo['buyer_id']));
             $data['shipping_time'] = time();
             $data['shipping_name'] = $shipping['shipping_name'];
             $data['shipping_code'] = $shipping['shipping_code'];
             $data['tracking_number'] = $tracking_number;
             $data['order_status'] = 3;
             if($Orders->where(array('order_id'=>$order_id))->save($data)){
     
                 $data['first'] = array('value'=>'尊敬的会员，您的订单已发货。','color'=>'#000');
                 $data['keyword1'] = array('value'=>$memberInfo['nickname'],'color'=>'#000');
                 $data['keyword2'] = array('value'=>$orderInfo['shipping_address'],'color'=>'#000');
                 $data['keyword3'] = array('value'=>$orderInfo['order_sn'],'color'=>'#000');
                 $data['keyword4'] = array('value'=>$shipping['shipping_name'],'color'=>'#000');
     
                 $data['keyword5'] = array('value'=>$data['tracking_number'],'color'=>'#000');
                 $data['remark'] = array('value'=>'感谢您购买！','color'=>'#000');

                 $payInfo = $PaylogDb->getPaylog(array('pay_id'=>$orderInfo['pay_id']));
                 if(C('weapp_tpl.open') == 1) {
                     send_weapp_msg($memberInfo['open_id'], C('weapp_tpl.shipping_notify_tpl'), 'pages/order?id=' . $orderInfo['order_id'], $payInfo['prepay_id'], $data, 'keyword1.DATA');
                 }

                 $this->order_log($order_id,'','发货');
                 //$this->success('发货成功！');
     
                 echo '订单'.$order_sn."发货成功<br/>";
     
             }
     
         }
     
     }

     public function orders_count() {
         $OrdersDb  =  D('Orders');
         $GroupDb  =  D('Group');
         $map['order_status'] = 1;
         $paidCount = $OrdersDb->where($map)->count();

         $groupCount = $GroupDb->where(array('status' => 0))->count();
         $ret['status'] = 1;
         $ret['paid_unconfirmed'] = $paidCount;
         $ret['group_ing'] = $groupCount;
         $this->ajaxReturn($ret);
     }
 }