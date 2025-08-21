<?php

namespace app\basic;


use app\controller\WebUserInfo;
use app\enum\CommonEnum;
use support\exception\BusinessException;

class BaseController
{

    /**
     * 当前登信息
     */
    protected $userInfo;

    /**
     * 当前登陆ID
     */
    protected $userId;

    /**
     * 当前登陆账号
     */
    protected $userName;


    // 新增白名单配置（支持通配符）
    protected $whitelist = [
        '/api/public/*' // 示例：允许/api/public开头的所有路由
    ];

    /**
     * 构造方法
     * @access public
     */
    public function __construct()
    {
        // 控制器初始化
        $this->init();
    }


    protected function init()
    {
        // 检查当前路径是否在白名单
        $currentPath = request()->path();
        if(!empty($this->whitelist))
        {
            foreach ($this->whitelist as $pattern) {
                if (fnmatch($pattern, $currentPath)) {
                    return; // 跳过鉴权
                }
            }
        }

        // 登录用户
        $logic = new WebUserInfo();
        $result = getCurrentInfo();
        if (!$result) {
            throw new BusinessException('用户信息读取失败了,请重新登录', CommonEnum::RETURN_CODE_ERROR_AUTH);
        }

        $this->userId = $result['id'];
        $this->userName = $result['name'] ?? '';
        $type = $result['type'] ?? '';
        $this->userInfo = $logic->read($result['id'], $type);

    }


}