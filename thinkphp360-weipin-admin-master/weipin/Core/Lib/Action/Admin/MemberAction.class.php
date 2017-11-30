<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：MemberAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:下午1:39:47
 * ----------------------------------------------------------------------------
 */
 class MemberAction extends AdminAction {
     /**
      * 列表
      */
     public function index() {
          import('ORG.Util.Page');
          $map = array();
          $MemberDb = D("Member");
          $count = $MemberDb->where($map)->count();
          $Page = new Page($count,C('web_admin_pagenum'));
          $nowPage = isset($_GET['p']) ? $_GET['p'] : 0;
          $show = $Page->show();
          $list = $MemberDb->where($map)->order('member_id ASC')->page($nowPage. ',' . C('web_admin_pagenum'))->select();
          $this->assign('list',$list);
          $this->assign('page',$show);
          $this->display();
     }
     
     /**
      * 编辑
      */
     public function edit() {
         $id = I('get.id');
         $MemberDb = D("Member");
         
         $memberInfo = $MemberDb->where(array('member_id' => $id))->find();
         
         $this->assign('memberInfo',$memberInfo);
         $this->display('add');   
     }
     
     public function del() {
         $id = $this->_get('id','intval',0);
         if(!$id) $this->error('参数错误！');
         $MemberDb = D('Member');
         
         if($MemberDb->where(array('member_id' => $id))->delete()) {
             $this->success("删除成功！",U('Admin/Member/index'));
         } else {
             $this->error("删除失败！");
         }
     }
 }