<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GoodsAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：商品管理控制器
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:下午2:54:28
 * ----------------------------------------------------------------------------
 */
class GoodsAction extends AdminAction {
   public function _initialize() {
       parent::_initialize();  //RBAC 验证接口初始化
       Vendor('Common.Tree');	//导入通用树型类
   }
    
   public function index() {
       import('ORG.Util.Page');// 导入分页类
       $map = array();
       $GoodsDb = D("Goods");
       $count = $GoodsDb->where($map)->count();
       $Page = new Page($count,C('web_admin_pagenum'));// 实例化分页类 传入总记录数
       // 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
       $nowPage = isset($_GET['p'])?$_GET['p']:1;
       $show       = $Page->show();// 分页显示输出
       
       $list = $GoodsDb->where($map)->order('goods_sort ASC')->page($nowPage.','.C('web_admin_pagenum'))->select();
       
       $this->assign('list',$list);
       $this->assign('page',$show);// 赋值分页输出
       
       $this->display();
   }
   
   public function add() {
       C('TOKEN_ON',false);
       $GoodsDb = D('Goods');
       
       if(isset($_POST['dosubmit'])) {
           if($GoodsDb->create()) {
               $goodId = $GoodsDb->add();
               if($goodId) {
                   $this->success('添加成功',U('Admin/Goods/index'));
               } else {
                   $this->error('添加失败',U('Admin/Goods/index'));
               }
           } else {
               $this->error($GoodsDb->getError());
           }
           exit;
       }
       $GoodsCateDb = D('GoodsCate');
       
       $GoodsCate = $GoodsCateDb->getAllGoodsCate();
       $array = array();
       // 构建生成树中所需的数据
       foreach($GoodsCate as $k=>$r){
           $r['id'] = $r['cate_id'];
           $r['pid'] = $r['parent_cate'];
           $r['name'] = $r['cate_name'];
           $array[$r['id']]      = $r;
       }
       
       $str  = "<option value='\$id' \$selected \$disabled >\$spacer \$name</option>";
       
       $Tree = new Tree();
       $Tree->init($array);
       $select_categorys = $Tree->get_tree(0, $str);
       $this->assign('select_categorys',$select_categorys);
       
       $this->display();
        
   }
   
   public function edit() {
       C('TOKEN_ON',false);
       $GoodsDb = D('Goods');
       if(isset($_POST['dosubmit'])) {
           
           if($GoodsDb->create()) {
               $goodsId = $GoodsDb->save();
               
               if($goodsId) {
                   $this->success('编辑成功',U('Goods/index'));
               } else {
                   $this->success('编辑失败 ',U('Goods/index'));
               }
           }  else {
               $this->error($GoodsDb->getError());
           }
           exit;
       }
       
       $id = $this->_get('id','intval',0);
       if(!$id) $this->error();
       $info = $GoodsDb->getGoods(array('goods_id' => $id));
       $GoodsCateDb = D('GoodsCate');
       
       $GoodsCate = $GoodsCateDb->getAllGoodsCate();
       $array = array();
       // 构建生成树中所需的数据
       foreach($GoodsCate as $k=>$r){
           $r['id'] = $r['cate_id'];
           $r['pid'] = $r['parent_cate'];
           $r['name'] = $r['cate_name'];
           $array[$r['id']]      = $r;
       }
       
       $str  = "<option value='\$id' \$selected \$disabled >\$spacer \$name</option>";
       
       $Tree = new Tree();
       $Tree->init($array);
       $select_categorys = $Tree->get_tree(0, $str,$info['cate_id']);
       $this->assign('select_categorys',$select_categorys);
       $this->assign('info',$info);
       
       $this->display('add');
   }
   
   public function del() {
       $id = $this->_get('id','intval',0);
       if(!$id)$this->error('参数错误!');
       
       $GoodsDb = D('Goods');
       if($GoodsDb->delGoods('goods_id='.$id)){
           $this->success('删除成功！',U('Admin/Goods/index'));
       }else{
           $this->error('删除失败!');
       }
   }

    /**
     * 商品小程序二维码
     */
   public function get() {
       $id = $this->_get('id','intval',0);
       if(!$id)$this->error('参数错误!');
       
       header('Content-Type: image/jpeg');
       exit(get_weapp_qrcode('pages/goods?goods_id=' . $id));
   }
}