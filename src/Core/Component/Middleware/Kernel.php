<?php
namespace FwSwoole\Middleware;

use FwSwoole\Core\Factory;

class Kernel extends Factory
{

    // 路由中间件
    protected $routeMiddleware = [];

    // 中间件校验
    final public function validate($middlewares)
    {
        $result = true;
        foreach ($middlewares as $middleware) {
            $routeMiddleware = $this->routeMiddleware;
            if (!isset($routeMiddleware[$middleware])) {
                throw new \Exception("Error not exist {$middleware}");
            }
            $result = (new $routeMiddleware[$middleware])->handle();
            if ($result !== true) {
                break;
            }
        }
        return $result;
    }

    // 获取一个token
    public function getToken()
    {

    }
}
