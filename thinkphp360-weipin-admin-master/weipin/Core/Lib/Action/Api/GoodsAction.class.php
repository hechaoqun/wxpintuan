<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GoodsAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:上午9:39:07
 * ----------------------------------------------------------------------------
 */
class GoodsAction extends ApiAction {
    /**
     * 商品列表
     */
    public function lists() {
        $map = array();
        $GoodsDb = D('Goods');
        $GoodCateDb = D('GoodsCate');
                
        $offset = I('get.offset');
        $size = I('get.size');

        $cate_id = I('get.cate_id',0,'intval');

        //分类ID
        if($cate_id) {
            $map['cate_id'] = $cate_id;
        }
        
        //父级分类ID
        if(isset($_GET['parent_cate'])) {
            $parent_cate = I('get.parent_cate',0,'intval');
            $cate_ids[] = $parent_cate;
            $cateAll = $GoodCateDb->childGoodsCate($parent_cate);
            foreach ($cateAll as $key => $value) {
                $cate_ids[] = $value['cate_id'];
            }
            $map['cate_id'] = array('in',$cate_ids);
        }

        
        $list = $GoodsDb->where($map)->order('goods_sort ASC')->limit($offset,$size)->select();
        if(!$list) {
            $ret['goods'] = array();
        } else {
            $ret['goods'] = $list;
        }
        $ret['result'] = 'ok';
        echo json_encode($ret);
    }
    
    /**
     * 商品详情
     */
    public function detail() {
        $GoodsDb = D('Goods');
        $goods_id = $this->_get('goods_id','intval',0);
        
        if(!$goods_id) {
            echo json_encode(array('result'=>'fail','error_info'=>'错误的请求地址或方法'));
            return;
        }
        
        $detail = $GoodsDb->getGoods('goods_id = ' . $goods_id);
        
        if(!$detail){
            echo json_encode(array('result'=>'fail','error_code'=>41002,'error_info'=>'商品已下架或不存在'));
            return;
        }
        
        $imgs = unserialize($detail['goods_imgs']);
        
        foreach($imgs as $v) {
            $gallery[]['img_url'] =  $v;
        }
        echo json_encode(array('result'=>'ok','goods'=>$detail,'gallery'=>$gallery));   
    }

    /**
     * 商品开团列表
     */
    public function groups() {
        $GoodsDb = D('Goods');
        $goods_id = $this->_get('goods_id','intval',0);
        
        if(!$goods_id) {
            echo json_encode(array('result'=>'fail','error_info'=>'错误的请求地址或方法'));
            return;
        }
    
        //获取团列表
        $OrdersDb = D('Orders');
        
        $offset = I('get.offset');
        $size = I('get.size');
        $map = array();
        $map['goods_id'] = $goods_id;
        $map['group_buy'] = 1;
        $map['group_header'] = 1;
        $map['order_status'] = array('not in','0,5');
        $map[ C('db_prefix') . 'group.status'] = 0;
        $map[ C('db_prefix') . 'group.expire_time'] = array('gt',time());

        $count = $OrdersDb->join(C('db_prefix') . 'member ON ' . C('db_prefix') . 'orders.buyer_id = ' . C('db_prefix') . 'member.member_id')->join(C('db_prefix') . 'group ON ' . C('db_prefix') . 'orders.group_order_id = ' . C('db_prefix') . 'group.group_order_id')->where($map)->count();
        $orders = $OrdersDb->join(C('db_prefix') . 'member ON ' . C('db_prefix') . 'orders.buyer_id = ' . C('db_prefix') . 'member.member_id')->join(C('db_prefix') . 'group ON ' . C('db_prefix') . 'orders.group_order_id = ' . C('db_prefix') . 'group.group_order_id')->limit($offset,$size)->group('member_id')->where($map)->order('group_header asc,RAND()')->select();

        $groups = array();
        foreach ($orders as $k => $v) {
            $tmp = array();
            $tmp['avatar'] = $v['headimgurl'];
            $tmp['nickname'] = $v['nickname'];
            $tmp['user_id'] = $v['member_id'];
            $tmp['create_time'] = $v['create_time'];
            $tmp['expire_time'] = $v['expire_time'];
            $tmp['require_num'] = $v['require_num'];
            $tmp['people'] = $v['people'];
            $tmp['group_order_id'] = $v['group_order_id'];
            $groups[] = $tmp;
        }

        $ret['goods_groups'] = $groups;
        $ret['groups'] = $count;
        $ret['result'] = 'ok';

        echo json_encode($ret);   
    }
}