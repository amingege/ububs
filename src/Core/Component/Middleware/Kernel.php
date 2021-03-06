<?php
namespace Ububs\Core\Component\Middleware;

use Ububs\Core\Component\Factory;

class Kernel extends Factory
{

    // 路由中间件
    public $routeMiddleware = [];

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
