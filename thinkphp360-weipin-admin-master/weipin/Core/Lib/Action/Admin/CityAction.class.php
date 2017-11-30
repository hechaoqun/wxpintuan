<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：CityAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:下午5:07:55
 * ----------------------------------------------------------------------------
 */
class CityAction extends AdminAction {
    public function index() {

        $id = I('get.id');
        $RegionDb = D('Region');
        if($id){
            $map['parent_id'] = $id;
        }else{
            $map['parent_id'] = 0;
        }
        $list = $RegionDb->where($map)->select();
        
        $region = $RegionDb->where(array('region_id'=>$id))->find();
        if(!$region){
            $t = '一级地区';
        }else{
            switch($region['level']){
                case 0:
                    $t = '二级地区';
                    break;
                case 1:
                    $t = '三级地区';
                    break;
                case 2:
                    $t = '四级地区';
                    break;
                case 3:
                    $t = '五级地区';
                    break;
            }
        }
        $this->assign('region',$region);
        $this->assign('t',$t);
        $this->assign('list',$list);
        $this->display();
    }
    
    public function add() {
        $data['region_name'] = I('post.region_name');
        if(!$data['region_name']) $this->error('名称不能为空！');
        $data['parent_id'] = I('post.parent_id');
        $data['level'] = I('post.level');
        D('Region')->add($data);
        $this->success('添加成功');
    }
    
    public function del() {
        $id = I('get.id');
        $RegionDb = D('Region');
        $region = $RegionDb->where(array('parent_id' => $id))->find();
        
        if($region) {
            $this->error('还有子地区存在，不可删除');
        }
        
        if($RegionDb->where(array('region_id' => $id))->delete()) {
            $this->error('删除成功');
        } else {
            $this->error('删除失败');   
        }
    }
    
}