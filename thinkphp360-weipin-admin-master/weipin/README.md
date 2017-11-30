WeiPIN.3.x - 小程序拼团管理系统
======

[ 介绍 ]

     微拼小商城，是一套基于最新微信小程序独一无二的拼团系统。
     采用 官方think.cms 开源框架开发。
     目标: 完成拼团整套订单流程,后台数据交互。
     拼团/发货/购买/发布/升级，微信登录，微信支付，微信分享，微信通知。

[ 安装方法 ]

    1 创建MYSQL数据库，导入 pin.sql
    2 把根目录的config.php.bak文件名改成config.php
    3 根据你的数据库，配置config.php “db_host db_name db_user db_pwd db_port”
    4 后台入口 https://domain/admin/
    5 后台帐号密码 admin admin
    6 后台系统设置方法请参考已有的那些设置

[ 目录结构 ]

    |-admin     后台入口跳转路径
    |-Core      系统核心
    |  ├Common      项目公共函数文件目录
    |  ├Conf        项目配置目录
    |  | ├Admin/config.php      项目后台配置文件
    |  | └Home/config.php       项目前台配置文件
    |  |
    |  ├Lang        项目多语言包目录
    |  | ├en-us         英文言包目录
    |  | └zh-cn         中文言包目录
    |  |
    |  ├Lib         项目类库目录
    |  | ├Action        控制器
    |  | └Model         模型
    |  |
    |  ├config.php      项目配置文件
    |  ├define.php      项目路径常量配置文件
    |  └tags.php        项目扩展行为调用配置文件
    |
    |-Public    公共静态文件目录
    |  ├Admin       后台公共静态目录
    |  ├Home        前台公共静态目录
    |  ├js          公共JS目录
    |  └tips        信息提示跳转页面
    |
    |-Temp      系统缓存目录
    |-Template  项目模板目录
    |  ├Admin/default       后台模板目录
    |  └Home/default        前台模板目录
    |
    |-config.php    网站配置文件
    └-index.php     系统入口文件

[ 协议 ]

    本系统除ThinkPHP框架外，遵循MIT开源许可协议发布
    具体参考LICENSE.txt内容