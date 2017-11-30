<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GoodsCateModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:下午3:10:41
 * ----------------------------------------------------------------------------
 */
class GoodsCateModel extends Model {
    
    //自动完成
    protected $_auto = array (
        array('time','time',1,'function'), 	//新增时
    );
    //自动验证
    protected $_validate=array(
        array('cate_name','require','分类名称必须！',1,'',3),
    );
    
    
    // 获取所有分类信息
    public function getAllGoodsCate($where = '' ,$order = 'sort asc') {
        return $this->where($where)->order($order)->select();
    }
    
    // 获取单个分类信息
    public function getGoodsCate($where = '',$field='*') {
        return $this->where($where)->field($field)->find();
    }
    
    //获取子分类
    public function childGoodsCate($id) {
        return $this->where(array('parent_cate' => $id))->select();
    }
    
    // 删除分类
    public function delGoodsCate($where) {
        if($where) {
            return $this->where($where)->delete();
        } else {
            return false;
        }
    }
    
    // 更新分类
    public function upNode($data) {
       if($data) {
           return $this->save($data);
       } else {
           return false;
       }
    }
    
}