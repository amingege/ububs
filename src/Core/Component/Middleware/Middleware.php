<?php
namespace Ububs\Core\Component\Middleware;

use Ububs\Core\Component\Factory;

class Middleware extends Factory
{

    public static function validate($middlewares = [])
    {
        if (!empty($middlewares)) {
            $mis = app('\App\Http\Middleware\Kernel')->routeMiddleware;
            if (empty($mis)) {
                throw new \Exception("Never exists Middleware", 1);
            }
            foreach ($middlewares as $middleware) {
                if (!isset($mis[$middleware])) {
                    if (empty($mis)) {
                        throw new \Exception("Never exists {$Middleware} Middleware", 1);
                    }
                }
                if (!$rs = (new $mis[$middleware])->handle()) {
                    return false;
                }
            }
        }
        return true;
    }
}
