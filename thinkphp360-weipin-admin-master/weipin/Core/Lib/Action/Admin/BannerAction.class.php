<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：BannerAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:上午11:09:37
 * ----------------------------------------------------------------------------
 */
 class BannerAction extends AdminAction {
     public function index() {
         import('ORG.Util.Page');// 导入分页类
         $map = array();
         $BannerDb = D('Banner');
         
         $count = $BannerDb->where($map)->count();
         $Page       = new Page($count,C('web_admin_pagenum'));// 实例化分页类 传入总记录数
         // 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
         $nowPage = isset($_GET['p'])?$_GET['p']:1;
         $show       = $Page->show();// 分页显示输出
         $list = $BannerDb->where($map)->order('banner_id ASC')->page($nowPage.','.C('web_admin_pagenum'))->select();
      
         $this->assign('list',$list);
         $this->assign('page',$show);// 赋值分页输出
         $this->display();
     }
     
     public function add() {
         $BannerDb = D('Banner');
         if(isset($_POST['dosubmit'])) {
             if($BannerDb->create()) {
                 $goodId = $BannerDb->add();
                 if($goodId) {
                     $this->success('添加成功',U('Banner/index'));
                 } else {
                     $this->error('添加失败',U('Banner/index'));
                 }
             } else {
                $this->error($BannerDb->getError());
            }
            exit;
         }
         $this->display();
     }
     
     public function del() {
         $id = $this->_get('id','intval',0);
         if(!$id)$this->error('参数错误!');
         $BannerDb = D('Banner');
         
         if($BannerDb->delBanner('banner_id=' . $id)) {
             $this->success('删除成功！',U('Admin/Banner/index'));
         } else {
             $this->error('删除失败!');
         }
         
     }
        
     public function edit() {
         $BannerDb = D('Banner');
         if(isset($_POST['dosubmit'])) {
             if($BannerDb->create()) {
                 $bannerId = $BannerDb->save();
                 if($bannerId) {
                     $this->success('编辑成功',U('Banner/index'));
                 } else {
                     $this->success('编辑失败',U('Banner/index'));
                 }
             } else {
                 $this->error($BannerDb->getError());
             }
             exit;
         }
         
         $id = $this->_get('id','intval',0);
         if(!$id)$this->error('参数错误!');
         $info = $BannerDb->getBanner('banner_id=' . $id);
         
         $this->assign('info',$info);
         $this->display('add');
     }
     
     
     
     
    
 }
 
 