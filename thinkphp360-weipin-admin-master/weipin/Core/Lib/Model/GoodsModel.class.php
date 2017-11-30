<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GoodsModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:下午3:03:12
 * ----------------------------------------------------------------------------
 */
class GoodsModel extends Model {
    
    protected $_auto = array (
        array('goods_imgs','serialize',3,'function'), 	//新增时
        array('time','time',1,'function'), 	//新增时
        array('in_selling','1',1), 	//新增时
    );
    
    
    public function delGoods($where) {
        return $this->where($where)->delete();
    }
    
    public function getGoods($where,$field='*') {
        return $this->where($where)->field($field)->find();
    }
}