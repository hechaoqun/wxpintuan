<?php 
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：BannerModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:上午11:15:56
 * ----------------------------------------------------------------------------
 */
class BannerModel extends Model {
    protected $_auto = array (
        array('start_time',strtotime,3,'function'), //新增时
        array('end_time',strtotime,3,'function'), //新增时
    );
    
    public function delBanner($where) {
        return $this->where($where)->delete();
    }
    
    public function getBanner($where,$fields='*') {
        return $this->where($where)->field($fields)->find();
    }
}