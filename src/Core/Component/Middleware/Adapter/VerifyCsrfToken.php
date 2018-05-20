<?php
namespace FwSwoole\Middleware\Adapter;

use FwSwoole\Core\Request;
use FwSwoole\Middleware\Kernel;
use FwSwoole\Log\Log;

class VerifyCsrfToken extends Kernel
{
    // token 有效时间
    private $tokenExpire = 3600;
    // 不需要token验证的路由
    protected $except = [];
    // 需要token验证的路由
    protected $include = [];

    // 获取 token
    public function getCsrfToken()
    {
        return JWTAuth::getInstance()->getJWTAuthToken();
    }

    // 校验 token
    public function check($token)
    {
        // 手动配置无需验证的 token
        if (!empty($this->except) && is_array($this->except)) {
            $pathInfo = Request::getPathInfo();
            if (\in_array($pathInfo, $this->except)) {
                return true;
            }
            // 正则匹配
            // foreach ($this->except as $routeInfo) {
            //     if (\preg_match($routeInfo, $pathInfo)) {
            //         return true;
            //     }
            // }
        }
        $primaryKeyValue = JWTAuth::getInstance()->attempt($token);
        if (!$primaryKeyValue) {
            Log::info('token error');
            return false;
        }
        return true;
    }
}
