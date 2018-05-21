<?php
namespace FwSwoole\Core;

class Container
{
    private $s = [];
    protected static $instance;

    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(self::$instance[$class])) {
            self::$instance[$class] = new $class;
        }
        return self::$instance[$class];
    }

    /**
     * 魔术方法，设置不存在的变量时调用
     */
    public function __set($k, $c)
    {
        $this->s[$k] = $c;
    }

    /**
     * 魔术方法，获取不存在的变量时调用
     */
    public function __get($k)
    {
        return $this->build($this->s[$k]);
    }

    /**
     * 自动绑定（Autowiring）自动解析（Automatic Resolution）
     *
     * @param string $className
     * @return object
     * @throws Exception
     */
    public function build($className, $flag = false)
    {
        // 如果是匿名函数（Anonymous functions），也叫闭包函数（closures）
        if ($className instanceof Closure) {
            // 执行闭包函数，并将结果
            return $className($this);
        }
        $reflector = new \ReflectionClass($className);
        // 检查类是否可实例化, 排除抽象类abstract和对象接口interface
        if (!$reflector->isInstantiable()) {
            throw new Exception("Can't instantiate this.");
        }
        /** @var ReflectionMethod $constructor 获取类的构造函数 */
        $constructor = $reflector->getConstructor();
        // 若无构造函数，直接实例化并返回
        if (is_null($constructor)) {
            $o = new $className;
            return $o;
        }
        // 取构造函数参数,通过 ReflectionParameter 数组返回参数列表
        $parameters = $constructor->getParameters();
        // 递归解析构造函数的参数
        $dependencies = $this->getDependencies($parameters);
        // 创建一个类的新实例，给出的参数将传递到类的构造函数。
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * 解析构造函数的参数
     * @param array $parameters
     * @return array
     * @throws Exception
     */
    public function getDependencies($parameters)
    {
        $dependencies = [];
        /** @var ReflectionParameter $parameter */
        foreach ($parameters as $parameter) {
            /** @var ReflectionClass $dependency */
            $dependency = $parameter->getClass();
            if (is_null($dependency)) {
                // 是变量,有默认值则设置默认值
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                // 是一个类，递归解析
                $dependencies[] = $this->build($dependency->name, true);
            }
        }
        return $dependencies;
    }

    /**
     * 变量解析
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws Exception
     */
    public function resolveNonClass($parameter)
    {
        // 有默认值则返回默认值
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        throw new Exception('I have no idea what to do here.');
    }

    public function parseRules($routeInfo, $request, $response)
    {
        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                break;
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                break;
            case FastRoute\Dispatcher::FOUND:
                $handler = isset($routeInfo[1]) ? $routeInfo[1] : '';
                $params  = isset($routeInfo[2]) ? $routeInfo[2] : '';
                // 只有控制器，默认调用index方法
                if (strpos($handler, '@') == -1) {

                }
                $action_arr     = explode('@', $handler);
                $controllerName = isset($action_arr[0]) ? $action_arr[0] : '';
                $actionName     = isset($action_arr[1]) ? $action_arr[1] : '';
                if (!$controllerName || !$actionName) {
                    echo '错误';
                    exit;
                }
                $this->controller = 'App\Http\\' . $controllerName;
                $controller       = $this->controller;
                $params_arr       = array_values($params);
                $param            = isset($params_arr[0]) ? $params_arr[0] : '';
                if ($param !== '') {
                    $controller->$actionName($request, $response);
                } else {
                    $controller->$actionName($request, $response);
                }

                break;
        }
    }
}
