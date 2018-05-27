<?php
namespace Ububs\Core\Component\View;

use Ububs\Core\Http\Interaction\Request;
use Ububs\Core\Http\Interaction\Container;


class View
{
    private static $view      = '';
    private static $variables = [];

    public static function assign($key, $value)
    {
        self::$variables[$key] = $value;
    }

    public static function display($path = null)
    {
        $viewPath = self::getViewPath($path);
        if (is_file($viewPath)) {
            extract(self::$variables);
            include $viewPath;
        } else {
            throw new \Exception("Error View Path, is not founted");
            return false;
        }
    }

    private static function getViewPath($path)
    {
        
        $viewPrefix = config('app.view_path', APP_ROOT . 'resources/views');
        // 如果路径为空，自动获取当前执行控制器命名空间为视图前缀
        if ($path === null) {
            $viewPath = lcfirst(str_replace('App\Http\Controllers', '', Request::getActionNamespace()))  . DS . Request::getActionMethod() . ".php";
        } else {
            $viewPath = str_replace('.', '/', $path) . ".php";
        }
        return $viewPrefix . DS . $viewPath;
    }
}
