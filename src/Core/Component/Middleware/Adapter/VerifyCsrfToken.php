<?php
namespace Ububs\Core\Component\Middleware\Adapter;

use Ububs\Core\Component\Middleware\Kernel;
use Ububs\Core\Http\Interaction\Request;

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
    public function checkCsrf()
    {
        // 手动配置无需验证的 token
        $except = $this->getExcept();
        if (!empty($except)) {
            $pathInfo = Request::getPathInfo();
            if (\in_array($pathInfo, $except)) {
                return true;
            }
            // 正则匹配
            // foreach ($this->except as $routeInfo) {
            //     if (\preg_match($routeInfo, $pathInfo)) {
            //         return true;
            //     }
            // }
        }
        return JWTAuth::getInstance()->attempt(Request::getAuthorization());
    }

    private function getExcept()
    {
        if (class_exists('App\Http\Middleware\VerifyCsrfToken')) {
            return app('App\Http\Middleware\VerifyCsrfToken')->except;
        }
        return [];
    }
}
