<?php
/**
 * 系统管理后台入口
 *
 */
class IndexAction extends AdminAction {
    public function _initialize() {


        parent::_initialize();  //RBAC 验证接口初始化
    }

    public function index(){

        $username = session('username');    // 用户名
        $roleid   = session('roleid');      // 角色ID
        if($username == C('SPECIAL_USER')){     //如果是无视权限限制的用户，则获取所有主菜单
            $sql = 'SELECT `id`,`title` FROM `tp_node` WHERE ( `status` =1 AND `display`=1 AND `level`<>1 ) ORDER BY sort DESC';
        }else{  //更具角色权限设置，获取可显示的主菜单
            $sql = "SELECT `tp_node`.`id` as id,`tp_node`.`title` as title FROM `tp_node`,`tp_access` WHERE `tp_node`.id=`tp_access`.node_id AND `tp_access`.role_id=$roleid  AND  `tp_node`.`status` =1 AND `tp_node`.`display`=1 AND (`tp_node`.`level` =0 OR `tp_node`.`level` =2)  ORDER BY `tp_node`.sort DESC";
        }
        $Model = new Model(); // 实例化一个model对象 没有对应任何数据表
        $main_menu = $Model->query($sql);

        $OrdersDb  =  D('Orders');
        $GroupDb = D('Group');
        $paidCount = $OrdersDb->where(array('order_status' => 1))->count();
        $groupCount = $GroupDb->where(array('status' => 0))->count();

        $this->assign('group_ing',$groupCount);
        $this->assign('paid_unconfirmed',$paidCount);
        $this->assign('main_menu',$main_menu);
        $this->display();
    }

    public function left(){
        $pid = $this->_get('id',intval,0);    //选择子菜单
        $NodeDB = D('Node');
        $datas = $this->left_child_menu($pid);
        $parent_info = $NodeDB->getNode(array('id'=>$pid),'title');
        $sub_menu_html = '<dl>';


        foreach($datas as $key => $_value) {
            $sub_array = $this->left_child_menu($_value['id']);

            $sub_menu_html .= "<dt><a target='_self' href='#' onclick=\"showHide('{$key}');\">{$_value[title]}</a></dt><dd><ul id='items{$key}'>";

            if(is_array($sub_array)){

                foreach ($sub_array as $value) {
                    $href = empty($value['data']) ? 'javascript:void(0)' : $value['data'];
                    $sub_menu_html .= "<li><a id='a_{$value[id]}' onClick='sub_title({$value[id]})' href='{$href}'>{$value[title]}</a></li>";
                }
            }
            $sub_menu_html .=  '</ul></dd>';
        }
        $sub_menu_html .= '</dl>';

        $this->assign('sub_menu_title',$parent_info['title']);
        $this->assign('sub_menu_html',$sub_menu_html);
        $this->display();

    }

    /**
     * 按父ID查找菜单子项
     * @param integer $parentid   父菜单ID
     * @param integer $with_self  是否包括他自己
     */
    private function left_child_menu($pid, $with_self = 0) {
        $pid = intval($pid);

        $username = session('username');    // 用户名
        $roleid   = session('roleid');      // 角色ID
        if($username == C('SPECIAL_USER')){     //如果是无视权限限制的用户，则获取所有主菜单
            $sql = "SELECT `id`,`data`,`title` FROM `tp_node` WHERE ( `status` =1 AND `display`=2 AND `level` <>1 AND `pid`=$pid ) ORDER BY sort DESC";
        }else{
            $sql = "SELECT `tp_node`.`id` as `id` , `tp_node`.`data` as `data`, `tp_node`.`title` as `title` FROM `tp_node`,`tp_access` WHERE `tp_node`.id = `tp_access`.node_id AND `tp_access`.role_id = $roleid AND `tp_node`.`pid` =$pid AND `tp_node`.`status` =1 AND `tp_node`.`display` =2 AND `tp_node`.`level` <>1 ORDER BY `tp_node`.sort DESC";
        }
        $Model = new Model(); // 实例化一个model对象 没有对应任何数据表
        $result = $Model->query($sql);

        if($with_self) {
            $NodeDB = D('Node');
            $result2[] = $NodeDB->getNode(array('id'=>$pid),`id`,`data`,`title`);
            $result = array_merge($result2,$result);
        }
        return $result;


    }

    public function top(){
        $this->display();
    }

    public function main(){
        $this->display();
    }

    public function footer(){
        $this->display();
    }

}