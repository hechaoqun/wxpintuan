<?php
/**
 * ============================================================================
 * Copyright (c) 2016-2017 #### All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：WeAppAction.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：########
 * ----------------------------------------------------------------------------
 * 作者:chenbei
 * ----------------------------------------------------------------------------
 * 时间:上午9:30:36
 * ----------------------------------------------------------------------------
 */
class WeAppAction extends ApiAction {
    public $weChat;
    public function _initialize() {
        parent::_initialize();
        import("@.Libary.weapp");
        $option['appid'] = C('weapp.appid');
        $option['appsecret'] = C('weapp.appsecret');
        $option['token'] = C('weapp.token');
        $option['encodingaeskey'] = C('weapp.encodingaeskey');
        $option['debug'] = true;
        $this->weChat = new Weapp($option);
    }
    
    public function wechat_entry() {
        $this->weChat->valid();
        $type = $this->weChat->getRev()->getRevType();
        switch($type) {
            case Weapp::MSGTYPE_TEXT:
                $this->weChat->text("hello, I'm wechat")->reply();
                exit;
                break;
        }
    }
    
    public function login() {
        $MemberDb = D('Member');
        $MemberTokenDB  = D('MemberToken');
        if(IS_PUT) {
            $token = I('get.token');
            $data = file_get_contents("php://input");
            
            $input = json_decode($data,true);
            
            $encryptedData = $input['encryptedData'];
    
            $memberInfo = $MemberTokenDB->getUser($token);
            if(!$memberInfo) {
                echo json_encode(array('result' => 'fail','error_code' => 40001,'error_info' => '用户校验失败'));
                exit;
            }
            
            import("@.Libary.weapp.wxBizDataCrypt");
            $pc = new WXBizDataCrypt(C('weapp.appid'),$memberInfo['access_token']);
            $errCode = $pc->decryptData($encryptedData, $input['iv'],$userinfo);
            if ($errCode != 0) {
                echo json_encode(array('result' => 'fail','error_code' => $errCode,'error_info' => '用户校验失败1'));
                exit;
            }
            
            $data = array();
            $userinfo = json_decode($userinfo,true);
            if(isset($userinfo['unionid'])) {
                $data['unionid'] = $userinfo['unionid'];
            }
            
            if(isset($userinfo['nickName'])) {
                $data['nickname'] = $userinfo['nickName'];
                $data['subscribe'] = 1;
                $data['sex'] = $userinfo['gender'];
                $data['headimgurl'] = $userinfo['avatarUrl'];
            }
            
            $MemberDb->where(array('member_id'=>$memberInfo['member_id']))->save($data);
            echo json_encode(array('result' => 'ok'));
            exit;
        } 
        


        $ret = $this->weChat->getOauthAccessToken();
        if(!$ret){
            echo json_encode(array('result'=>'fail','error_code'=>42032,'error_info'=>'获取用户信息失败'));
            return;
        }
        $memberInfo = $MemberDb->getMember(array('open_id'=>$ret['openid']));
        
        $data['access_token'] = $ret['session_key'];
        $data['expires'] = time() . $ret['expires_in'];
        $data['refresh_token'] = $ret['session_key'];
        
        if(!$memberInfo) {
            $data['open_id'] = $ret['openid'];
            $data['nickname'] = C('weapp.nick_pre') . time();
            $data['headimgurl'] = C('weapp.headimgurl');
            $data['subscribe'] = 0;
            $data['time'] = time();
            $data['disablle'] = 0;
        }
        
        if(!$memberInfo) {
            $userId = $MemberDb->add($data);
        } else {
            $MemberDb->where(array('member_id' => $memberInfo['member_id']))->save($data);
            $userId = $memberInfo['member_id'];
        }
        
        $data['result'] = 'ok';
        $data['user_sn'] = $userId;
        
        $memberToken['member_id'] = $userId;
        $memberToken['token'] = generate_password(48);
        $memberToken['expires'] =  $data['expires'];
        
        $data['token'] = $memberToken['token'];
        $memberTokenDb = D('MemberToken');
        
        $memberTokenDb->add($memberToken,array(),true);
        
        echo json_encode($data);
    }
    
}