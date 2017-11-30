<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GoodsCateAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:下午3:39:00
 * ----------------------------------------------------------------------------
 */
 class GoodsCateAction extends AdminAction {
     public function _initialize() {
         parent::_initialize();  //RBAC 验证接口初始化
         Vendor('Common.Tree');	//导入通用树型类
     }
     
     public function index() {
         $GoodsCate = D('GoodsCate')->getAllGoodsCate();
          
         $array = array();
         // 构建生成树中所需的数据
         foreach($GoodsCate as $k=>$r){
             $r['id'] = $r['cate_id'];
             $r['pid'] = $r['parent_cate'];
             $r['name'] = $r['cate_name'];
             $r['submenu'] = "<a href='".U('Admin/GoodsCate/add',array('pid'=>$r[id]))."'>添加子菜单</a>";
             $r['edit']    = "<a href='".U('Admin/GoodsCate/edit',array('id'=>$r['id'],'pid'=>$r['pid']))."'>修改</a>";
             $r['del']     = "<a onClick='return confirmurl(\"".U('Admin/GoodsCate/del',array('id'=>$r[id]))."\",\"确定删除该菜单吗?\")' href='javascript:void(0)'>删除</a>";
             $array[$r['id']]      = $r;
         }
         $str  = "<tr class='tr'>
				    <td align='center'><input type='text' value='\$sort' size='3' name='sort[\$id]'></td>
				    <td align='center'>\$id</td>
				    <td >\$spacer \$name</td>
         
					<td align='center'>
						\$submenu | \$edit | \$del
					</td>
				  </tr>";
         $Tree = new Tree();
         $Tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
         $Tree->nbsp = '&nbsp;&nbsp;&nbsp;';
         $Tree->init($array);
         $html_tree = $Tree->get_tree(0, $str);
         $this->assign('html_tree',$html_tree);
         $this->display();
     }
     
     public function add() {
         $GoodsCateDb = D('GoodsCate');
         if(isset($_POST['dosubmit'])) {
             if($GoodsCateDb->create()) {
                 $cateId = $GoodsCateDb->add();
                 if($cateId){
                     $this->success('添加成功',U('GoodsCate/index'));
             
                 }else{
                     $this->error('添加失败 ',U('GoodsCate/index'));
                 }
             }else{
                 $this->error($GoodsCateDb->getError());
             }
             exit;
         }
         
         $GoodsCate = $GoodsCateDb->getAllGoodsCate();
         $pid = $this->_get('pid','intval',0);	//选择子菜单
         
         $array = array();
         // 构建生成树中所需的数据
         foreach($GoodsCate as $k => $r) {
             $r['id'] = $r['cate_id'];
             $r['pid'] = $r['parent_cate'];
             $r['name'] = $r['cate_name'];
             $array[$r['id']]      = $r;
         }
         
         $str  = "<option value='\$id' \$selected \$disabled >\$spacer \$name</option>";
         
         $Tree = new Tree();
         $Tree->init($array);
         $select_categorys = $Tree->get_tree(0, $str, $pid);
         $this->assign('tpltitle','添加');
         $this->assign('select_categorys',$select_categorys);
         $this->display();
     }
    
     public function edit() {
         $GoodsCateDb = D('GoodsCate');
         if(isset($_POST['dosubmit'])) {
             if($GoodsCateDb->create()) {
                $cateId = $GoodsCateDb->save();
                if($cateId) {
                    $this->success('修改成功',U('GoodsCate/index'));
                } else {
                    $this->error('修改失败',U('GoodsCate/index'));
                }
             } else {
                 $this->error($GoodsCateDb->getError());
             }
             exit;
         } 
         
         $GoodsCate = $GoodsCateDb->getAllGoodsCate();
         $id = $this->_get('id','intval',0);	//选择子菜单
         $pid = $this->_get('pid','intval',0);	//选择子菜单
         
         $array = array();
         // 构建生成树中所需的数据
         foreach($GoodsCate as $k=>$r) {
             $r['id'] = $r['cate_id'];
             $r['pid'] = $r['parent_cate'];
             $r['name'] = $r['cate_name'];
             $array[$r['id']]      = $r;
         }  
         
         $str  = "<option value='\$id' \$selected \$disabled >\$spacer \$name</option>";
         
         $Tree = new Tree();
         $Tree->init($array);
         $select_categorys = $Tree->get_tree(0, $str, $pid);
         $this->assign('tpltitle','编辑');
         $this->assign('select_categorys',$select_categorys);
         $this->assign('info', $GoodsCateDb->getGoodsCate('cate_id='.$id));
         $this->display('add');
     }
     
     public function del() {
         $id = $this->_get('id','intval',0);
         if(!$id) $this->error('参数错误!');
         $GoodsCateDb = D('GoodsCate');
         $info = $GoodsCateDb ->getGoodsCate(array('cate_id'=>$id),'cate_id');
         
         if(D('Goods')->getGoods(array('cate_id'=>$id))){
             $this->error('该分类下存在商品，不可删除!');
         }
         
         if($GoodsCateDb->childGoodsCate($info['cate_id'])){
             $this->error('存在子分类，不可删除!');
         }
         
         if($GoodsCateDb->delGoodsCate('cate_id=' . $info['cate_id'])) {
             $this->assign("jumpUrl",U('Admin/GoodsCate/index'));
             $this->success('删除成功！');
         } else {
             $this->error('删除失败!');
         }
         
     }
     
     public function sort() {
         $sorts = $this->_POST('sort');
         if(!is_array($sorts))$this->error('参数错误!');
         $GoodsCateDb = D('GoodsCate');
         foreach ($sorts as $id => $sort) {
             $GoodsCateDb->upNode(array('cate_id' => $id,'sort' => intval($sort)));
         }
         $this->assign('jumpUrl',U('Admin/GoodsCate/index'));
         $this->success('更新成功！');
     }
     
 }