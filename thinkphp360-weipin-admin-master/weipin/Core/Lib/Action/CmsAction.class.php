<?php
/*
* 系统公共类
*/
class CmsAction extends Action{

    public function _initialize() {
		/*
		* 载入各种扩展
		*/
		//import("ORG.Util.Image");	//图像操作类库
		Load('extend');		//Think扩展函数库
    }
    
    /**
     * 公共上传
     */
    public function upload() {
        import("@.Libary.oss.OssClient");
        import('ORG.Net.UploadFile');
        $upload_config = C('upload_config');
        
        $dir_name = empty($_GET['dir']) ? 'image' : trim($_GET['dir']);
        
        $upload_config['savePath'] .= $dir_name.'/';
        $upload = new UploadFile($upload_config);// 实例化上传类
        if(!$upload->upload()) {// 上传错误提示错误信息
            return array('error'=>1,'message'=>$upload->getErrorMsg());
        } else {// 上传成功 获取上传文件信息
            $info =  $upload->getUploadFileInfo();
        }
        
        //上传成功后处理
        $ossConfig = C('yun_oss');
        foreach($info as $k=>$v){
            if($ossConfig['oss_open'] == 1) {
                $oss = new OssClient($ossConfig['access_key_id'], $ossConfig['access_key_secret'], $ossConfig['endpoint']);
                $res = $oss->uploadFile($ossConfig['bucket'], 'Uploads/'.$dir_name.'/' . $v['savename'], $v['savepath'] . $v['savename']);

                if ($res) {
                    $data['oss'] = 1;
                    $data['attach_url'] = $res['info']['url'];
                    if ($ossConfig['is_delete'] == 1) {
                        @unlink($v['savepath'] . $v['savename']);
                    }
                } else {
                    $data['attach_url'] = C('web_url').  'Uploads/' . $dir_name.'/' . $v['savename'];
                    $data['oss'] = 0;
                }
        
                $data['time'] = time();
                $attach_id = M('Attachment')->add($data);
                $attach_array[] = $attach_id;
                $attach_url[] = $data['attach_url'];
                unset($data);
            }else{
                $data['oss'] = 0;
                $data['attach_url'] = C('web_url'). 'Uploads/' . $dir_name.'/' . $v['savename'];
                $data['time'] = time();
                $attach_id = M('Attachment')->add($data);
                $attach_array[] = $attach_id;
                $attach_url[] = $data['attach_url'];
                unset($data);
            }
        }
        
        
        return array('error'=>0,'attach'=>$attach_array,'url'=>$attach_url[0],'url_arr'=>$attach_url);
        
    }
	
}