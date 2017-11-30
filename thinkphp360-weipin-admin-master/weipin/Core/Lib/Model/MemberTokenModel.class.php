<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：MemberTokenModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:上午11:18:58
 * ----------------------------------------------------------------------------
 */
class MemberTokenModel extends Model {
    /**
     * 通过token获取用户信息
     * @param unknown $token
     * @param string $field
     */
    public function getUser($token,$field="*") {
        $map['token'] = $token;
        $tokenInfo = $this->where($map)->find();
        
        if(!$tokenInfo) {
            return false;
        }
        
        $time = time();
        if($tokenInfo['expires'] < time()) {
            return false;
        }
        
        return D('Member')->getMember(array('member_id' => $tokenInfo['member_id']));
    }
}