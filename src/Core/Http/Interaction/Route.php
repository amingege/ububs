<?php
namespace Ububs\Core\Http\Interaction;

use Ububs\Core\Component\Middleware\Middleware;
use Ububs\Core\Http\Factory;
use UBubs\Core\Http\Interaction\Response;
use Ububs\Core\Tool\StatusCode\StatusCode;

class Route extends Factory
{
    private static $dispatcher;
    private static $routes;
    public static $actionNamespace;
    public static $actionController;
    public static $actionMethod;

    public function init()
    {
        dir_make(APP_ROOT . 'storage/cache/route');
        self::$dispatcher = \FastRoute\cachedDispatcher(function (\FastRoute\RouteCollector $r) {
            self::$routes = $r;
            require_once APP_ROOT . '/routes/web.php';
        }, [
            'cacheFile' => APP_ROOT . 'storage/cache/route/' . time('YmdHis') . rand() . rand() . '.cache', /* required */
            'cacheDisabled' => false, /* optional, enabled by default */
        ]);
    }

    public function run($routeInfo)
    {
        $routers = isset($routeInfo[1]) ? $routeInfo[1] : [];
        if (empty($routers)) {
            throw new \Exception('routers is error, please check it');
        }
        $middleware = isset($routers['middleware']) && !empty($routers['middleware']) ? $routers['middleware'] : '';
        if ($middleware !== '' && !$mr = Middleware::validate($middleware)) {
            return Response::error(StatusCode::CODE_UNAUTHORIZED);
        }
        $actions         = isset($routers['action']) ? $routers['action'] : '';
        self::$actionNamespace = isset($routers['namespace']) ? $routers['namespace'] : '';
        if ($actions === '') {
            return Response::error(StatusCode::CODE_UNAUTHORIZED);
        }
        if (is_callable($actions)) {
            return call_user_func($actions);
        }
        list(self::$actionController, self::$actionMethod) = explode('@', $actions);
        // 依赖注入等解析
        $diContainer    = Container::getInstance();
        $diContainer->c = self::$actionNamespace . '\\' . self::$actionController;
        $controller     = $diContainer->c;
        $method         = self::$actionMethod;
        $params         = isset($routeInfo[2]) ? array_values($routeInfo[2]) : [];
        try {
            return $controller->$method(...$params);
        } catch (\Exception $e) {
            return Response::error(StatusCode::CODE_UNAUTHORIZED, $e->getMessage());
        }
        // $actionArr = isset($routeInfo[1]) ? $routeInfo[1] : [];
        // if (empty($actionArr)) {
        //     throw new \Exception('routers is error, please check it');
        // }

        // if (isset($actionArr['middleware']) && !empty($actionArr['middleware'])) {
        //     $middlewareResult = \App\Http\Middleware\Kernel::getInstance()->validate($actionArr['middleware']);
        //     if (!$middlewareResult) {
        //         return Response::error(StatusCode::CODE_UNAUTHORIZED);
        //     }
        // }

        // if (is_callable($actionArr['action'])) {
        //     return call_user_func($actionArr['action']);
        // }
        // if (is_string($actionArr['action'])) {
        //     $tempAction       = explode('@', $actionArr['action']);
        //     self::$controller = isset($tempAction[0]) ? strval($tempAction[0]) : '';
        //     self::$method     = isset($tempAction[1]) ? strval($tempAction[1]) : 'index';
        //     self::$namespace  = isset($actionArr['namespace']) ? $actionArr['namespace'] : '';
        // }

        // // 依赖注入等解析
        // $diContainer             = Container::getInstance();
        // $diContainer->controller = self::$namespace . '\\' . self::$controller;
        // $controller              = $diContainer->controller;
        // $method                  = self::$method;
        // $params                  = isset($routeInfo[2]) ? array_values($routeInfo[2]) : [];
        // try {
        //     return $controller->$method(...$params);
        // } catch (\Exception $e) {
        //     return Response::error(StatusCode::CODE_UNAUTHORIZED, $e->getMessage());
        // }
    }

    public function getDispatcher()
    {
        return self::$dispatcher;
    }

    private function addGroup($attributes, $callback)
    {
        $this->prefix     = isset($attributes['prefix']) ? strval($attributes['prefix']) : '';
        $this->namespace  = isset($attributes['namespace']) ? strval($attributes['namespace']) : '';
        $this->middleware = isset($attributes['middleware']) ? (array) $attributes['middleware'] : [];
        call_user_func($callback);
    }

    private function addRoutes($method, $url, $action, $middleware = [])
    {
        $actionLast           = [];
        $actionLast['action'] = $action;
        if ($this->namespace) {
            $actionLast['namespace'] = $this->namespace;
        } else {
            $actionLast['namespace'] = '\App\Http\\';
        }
        if ($this->middleware) {
            $actionLast['middleware'] = $this->middleware;
        }
        if (is_array($middleware) && !empty($middleware)) {
            $actionLast['middleware'] = array_merge($actionLast['middleware'], $middleware);
        }
        self::$routes->addRoute(strtoupper($method), $this->prefix . $url, $actionLast);
    }

    private function addRoute($method, $url, $action, $middleware = [])
    {
        $actionLast = [];
        if (is_string($action)) {
            // 判断命名空间
            $action    = str_replace('/', '\\', $action);
            $firstWord = substr($action, 0, 1);
            $rNum      = strripos($action, '\\');
            if ($rNum) {
                $actionLast['namespace'] = $firstWord === '\\' ? substr($action, 0, $rNum) : '\App\Http\\' . substr($action, 0, $rNum);
                $actionLast['action']    = substr($action, $rNum + 1);
            } else {
                $actionLast['namespace'] = '\App\Http\\';
                $actionLast['action']    = $action;
            }
        } else {
            $actionLast['action'] = $action;
        }
        if (is_array($middleware) && !empty($middleware)) {
            $actionLast['middleware'] = $middleware;
        }
        self::$routes->addRoute(strtoupper($method), $url, $actionLast);
    }

    private function checkCsrfToken()
    {
        $excepts = \App\Http\Middleware\VerifyCsrfToken::getInstance()->except;
        if (!empty($excepts) && in_array(Request::getPathInfo(), $excepts)) {
            return true;
        }

        // 需要进行csrf_token验证
        if (\FwSwoole\Component\Session::get('csrf_token') !== Request::getCsrfToken()) {
            return false;
        }
        return true;
    }

    private function checkRouteMiddleware($middlewares)
    {
        $result = true;
        foreach ($middlewares as $middleware) {
            $routeMiddleware = Kernel::getInstance()->routeMiddleware;
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
}
