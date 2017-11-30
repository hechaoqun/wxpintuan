<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GroupAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：团购管理
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:下午2:08:39
 * ----------------------------------------------------------------------------
 */
 class GroupAction extends AdminAction {
      public function index() {
          import('ORG.Util.Page');
          $GroupDb = D('Group');
          $map = array();

          $get = I('get.search');
          foreach ($get as $k => $v) {
              if ($v != '' && $v != -1) {
                  $map[$k] = $v;
              }
          }

          if(isset($map['start_time']) && isset($map['end_time'])) {
              $map['create_time'] = array(array('egt',strtotime($map['start_time'])),array('elt',strtotime($map['end_time'])),'AND');
              unset($map['start_time']);
              unset($map['end_time']);
          }
          if(isset($map['start_time'])) {
              $map['create_time'] = array('egt',strtotime($map['start_time']));
              unset($map['start_time']);
          }

          if(isset($map['end_time'])) {
              $map['create_time'] = array('elt',strtotime($map['end_time']));
              unset($map['end_time']);
          }

          $order = 'group_order_id desc';
          $count = $GroupDb->where($map)->count();
          $Page = new Page($count,C('web_admin_pagenum'));
          $nowPage = isset($_GET['p']) ? $_GET['p'] : 1;
          $show = $Page->show();
          $list = $GroupDb->join(C('db_prefix') . 'member ON ' . C('db_prefix') . 'group.owner_id = ' . C('db_prefix') . 'member.member_id')->where($map)->page($nowPage . ',' . C('web_admin_pagenum'))->order($order)->select();
          
          $this->assign('list',$list);
          $this->assign('page',$show);
          
          $this->display();
      }
      
      public function edit() {
          $id = I('get.id');
          
          $GroupDb = D('Group');
          $OrderDb = D('Orders');
          $map = array();
          $map['group_order_id'] = $id;
          
          $group = $GroupDb->where($map)->find();
          
          $map = array();
          $map[C('db_prefix') . 'orders.group_order_id'] = $id;
          $map[C('db_prefix') . 'orders.pay_time'] = array('gt',0);
          $orderInfo = $OrderDb->join(C('db_prefix') . 'member ON '. C('db_prefix') . 'orders.buyer_id = ' . C('db_prefix') . 'member.member_id')->where($map)->order(C('db_prefix') . 'orders.pay_time asc')->select();
          
          $this->assign('group',$group);
          $this->assign('order',$orderInfo);
          $goods = unserialize($orderInfo[0]['order_goods']);
          $this->assign('goods',$goods);
          $this->display();
      }

     public function get() {
         $id = $this->_get('id','intval',0);
         if(!$id)$this->error('参数错误!');

         header('Content-Type: image/jpeg');
         exit(get_weapp_qrcode('pages/group?id=' . $id));
     }
 }