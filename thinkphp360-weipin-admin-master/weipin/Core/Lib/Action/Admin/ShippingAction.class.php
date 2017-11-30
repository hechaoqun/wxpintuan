<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ShippingAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:上午8:21:24
 * ----------------------------------------------------------------------------
 */
class ShippingAction extends AdminAction {
    public function index() {  
        import('ORG.Util.Page');// 导入分页类
        $map = array();
        $ShippingDb = D('Shipping');
        
        $count = $ShippingDb->where($map)->count();
        $Page = new Page($count);
        $nowPage = isset($_GET['p']) ? $_GET['p'] : 1;
        $show = $Page->show();
        
        $list = $ShippingDb->where($map)->order('shipping_id ASC')->page($nowPage . ',' . C('web_admin_pagenum'))->select();
         
        $this->assign('list',$list);
        $this->assign('page',$show);
        $this->display();
    }
    
    public function add() {
        $ShippingDb = D('Shipping');
        if(isset($_POST['dosubmit'])) {
            if($ShippingDb->create()) {
               $shippingId = $ShippingDb->add();
               if($shippingId) {
                  $this->success('添加成功',U('Shipping/index'));  
               } else {
                   $this->error('添加失败',U('Shipping/index'));
               }
            } else {
                $this->error($ShippingDb->getError());
            }
            exit();
        }
        $this->display();
    }
    
    public function edit() {
        $ShippingDb = D('Shipping');
        if(isset($_POST['dosubmit'])) {
            if($ShippingDb->create()) {
                $shippingId = $ShippingDb->save();
                if($shippingId) {
                    $this->success('修改成功',U('Shipping/index'));
                } else {
                    $this->error('修改失败',U('Shipping/index'));
                }
            } else {
                $this->error($ShippingDb->getError());
            }
            exit;
        }
        
        $info = $ShippingDb->where(array('shipping_id' => I('get.id')))->find();
        $this->assign('info',$info);
        $this->display('add');
    }
    
    public function del() {
        $id = $this->_get('id','intval',0);
        if(!$id) $this->error('参数错误！');
        $ShippingDb = D('Shipping');
        
        if($ShippingDb->where(array('shipping_id' => $id))->delete()) {
            $this->success('删除成功！',U('Admin/Shipping/index'));
        } else {
            $this->error('删除失败!');
        }
    }
    
}