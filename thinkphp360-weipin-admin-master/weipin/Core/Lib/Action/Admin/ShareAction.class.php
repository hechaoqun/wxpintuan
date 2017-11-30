<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ShareAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：分享控制器
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:下午4:51:09
 * ----------------------------------------------------------------------------
 */
class ShareAction extends AdminAction {
    public function index() {
        import('ORG.Util.Page');
        $map = array();
        $ShareDb = D('Share');
        $count = $ShareDb->where($map)->count();
        $Page = new Page($count,C('web_admin_pagenum'));
        $nowPage = isset($_GET['p']) ? $_GET['p'] : 1;
        $show = $Page->show();
        $list = $ShareDb->join(C('db_prefix').'member ON '.C('db_prefix').'share.member_id = '.C('db_prefix').'member.member_id')->where($map)->order('share_id desc')->page($nowPage.','.C('web_admin_pagenum'))->select();
        $this->assign('list',$list);
        $this->assign('page',$show);
        $this->display();
    }
}