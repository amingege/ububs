<?php
namespace Ububs\Core\Http\Interaction;

use Ububs\Core\Http\Interaction\Route;

class Request
{
    private static $request;
    private static $controllerName = '';
    private static $actionName     = '';

    /**
     * request 初始化
     * @param  \swoole_http_request $request
     */
    public static function init(\swoole_http_request $request)
    {
        self::$request = $request;
    }

    /**
     * 获取 request 对象
     * @return object
     */
    public static function getRequest()
    {
        return self::$request;
    }

    /**
     * 获取请求头信息
     * @return array
     */
    public static function getHeader()
    {
        return self::$request->header;
    }

    /**
     * 获取请求类型
     * @return string
     */
    public static function getMethod()
    {
        return strtoupper(self::$request->server['request_method']);
    }

    /**
     * 获取请求 pathInfo
     * @return string
     */
    public static function getPathInfo()
    {
        return self::$request->server['path_info'];
    }

    /**
     * 获取 request 请求的token
     * @return string
     */
    public static function getAuthorization()
    {
        return self::$request->header['x-authrization'] ?? '';
    }

    /**
     * 获取当前用户的fd
     * @return int
     */
    public static function getFd()
    {
        return self::$request->fd;
    }

    public static function getRealIp()
    {
        return self::getHeader()['x-real-ip'];
    }

    /**
     * 获取 get 请求参数
     * @param  string $key 键
     * @return string
     */
    public static function get($key = '')
    {
        $getParams = self::$request->get;
        if ($key === '') {
            return $getParams;
        }
        return $getParams[$key];
    }

    /**
     * 获取 post 请求参数
     * Content-Type 不用有两种post提交方式，都需要取出
     * @param  string $key 键
     * @return string
     */
    public static function post($key = '')
    {
        // Content-Type:application/x-www-form-urlencoded
        $result = self::$request->post ?? [];

        // Content-Type:text/plain;charset=UTF-8
        $postRawContent = self::$request->rawContent();
        if ($postRawContent) {
            $result = array_merge($result, json_decode($postRawContent, true));
        }
        if ($key === '') {
            return $result;
        }
        return $result[$key];
    }

    public static function file($key = '')
    {
        $result = self::$request->files;
        if ($key === '') {
            return $result;
        }
        return $result[$key];
    }

    /**
     * 获取 request 请求参数
     * @param  string $type get | post
     * @return array
     */
    public static function input($type)
    {
        $result = [];
        switch (strtoupper($type)) {
            case 'GET':
                $result = self::get();
                break;

            case 'POST':
                $result = self::post();
                break;

            case 'FILE':
                $result = self::file();
                break;

            case 'ALL':
                $result = self::input(self::getMethod());
                break;

            default:
                # code...
                break;
        }
        return $result;
    }

    public function getActionController()
    {
        return Route::$actionController;
    }

    public function getActionMethod()
    {
        return Route::$actionMethod;
    }

    public function getActionNamespace()
    {
        return Route::$actionNamespace;
    }
}
