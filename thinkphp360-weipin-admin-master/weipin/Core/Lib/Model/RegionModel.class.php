<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：RegionModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:下午5:21:58
 * ----------------------------------------------------------------------------
 */
class RegionModel extends Model {
    
    public function getRegion($where = '',$field = '*'){
        return $this->field($field)->where($where)->select();
    }
    
    public function getRegionById($id,$field="*") {
        return $this->where(array('region_id' => $id))->field($field)->find();
    }
    
    public function getRegionByName($name,$where=array(),$field="*") {
        $map =  array();
        $map['region_name'] = $name;
        if ($where) {
            $map = array_merge($where,$map);
        }
        return $this->where($map)->field($field)->find();
    }
    
}