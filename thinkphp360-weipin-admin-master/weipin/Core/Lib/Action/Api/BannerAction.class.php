<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：BannerAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:上午8:25:28
 * ----------------------------------------------------------------------------
 */
class BannerAction extends ApiAction {
    public function lists() {
        import('ORG.Util.Page');// 导入分页类
        $map = array();
        $BannerDb = D('Banner');

        //公告开始时间 小于 或 等于
        $map['start_time'] = array('elt',time());

        //公告结束时间 大于当前时间
        $map['end_time'] = array('gt',time());

        $count = $BannerDb->where($map)->count();

        $Page = new Page($count);
        // 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
        $nowPage = isset($_GET['p'])?$_GET['p']:1;
        $show       = $Page->show();// 分页显示输出
        $list = $BannerDb->where($map)->order('banner_sort ASC')->page($nowPage . ',' . C('web_home_pagenum'))->select();
        $ret['result'] = 'ok';
        $ret['banners'] = $list;
        echo json_encode($ret);
    }
}