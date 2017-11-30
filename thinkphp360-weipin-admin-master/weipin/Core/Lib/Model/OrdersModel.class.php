<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：OrdersModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:下午4:16:23
 * ----------------------------------------------------------------------------
 */
 class OrdersModel extends Model {
     
     /**
      * 
      * @param 条件 $where
      * @param 字段 $field
      */
     public function getOrder($where='',$field='*') {
         return $this->field($field)->where($where)->find();
     }
     
     /**
      * 
      * @param 订单ID $order_id
      * @param 订单状态 $status
      */
     public function setOrderStatus($order_id,$status) {
        return $this->where(array('order_id' => $order_id))->save(array('order_status' => $status));    
     }
 }