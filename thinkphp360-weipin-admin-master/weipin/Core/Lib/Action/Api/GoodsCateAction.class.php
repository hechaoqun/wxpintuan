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
 * 时间:上午9:36:58
 * ----------------------------------------------------------------------------
 */
class GoodsCateAction extends ApiAction {
    /**
     * 分类列表
     */
    public function lists() {
        $map = array();
        $GoodsCateDb = D('GoodsCate');
        
        $offset = I('get.offset');
        $size = I('get.size');
        
        $map['parent_cate'] = 0;
        
        $list = $GoodsCateDb->where($map)->order('sort ASC')->limit($offset,$size)->select();
        if(!$list) {
            $ret['cates'] = array();
        } else {
            $ret['cates'] = $list;
        }
        $ret['result'] = 'ok';
        echo json_encode($ret);
    }
}