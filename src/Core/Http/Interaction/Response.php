<?php
namespace Ububs\Core\Http\Interaction;

use Ububs\Core\Tool\StatusCode\StatusCode;

class Response
{

    const CODE_REDIRECT = 302;
    private static $response;
    private static $isEnd = false;

    public static function init(\swoole_http_response $response)
    {
        self::$isEnd    = false;
        self::$response = $response;
    }
    public static function getResponse()
    {
        return self::$response;
    }

    /**
     * 返回 string 数据给客户端
     * @param  string $data 返回内容
     * @return string
     */
    public static function end(string $data)
    {
        self::setEnd();
        return self::$response->end($data);
    }

    /**
     * 返回 json 数据给客户端
     * @param  array $data 返回内容
     * @return json
     */
    public static function json(array $data)
    {
        self::setEnd();
        return self::$response->end(json_encode($data));
    }

    /**
     * 判断 response 对象已经返回客户端
     * @return boolean
     */
    public static function isEnd()
    {
        return self::$isEnd;
    }

    /**
     * 设置返回标识
     */
    public static function setEnd()
    {
        self::$isEnd = true;
        return self::$isEnd;
    }

    public static function redirect($route)
    {

        // ajax 请求
        // $header = Request::getHeader();
        // if (isset($header['x-requested-with']) && strtoupper($header['x-requested-with']) === 'XMLHTTPREQUEST') {
        //     return self::$response->end(json_encode([
        //         'code' => self::CODE_REDIRECT,
        //         'path'   => $route,
        //     ]));
        // }
        self::$response->header("Location", $route);
        self::$response->status(302);
        return true;
    }

    /**
     * 返回错误信息
     * @param  int $code    错误码
     * @param  string $message 提示信息
     * @return json
     */
    public static function error($code, string $message = '')
    {
        if ($message === '') {
            $message = StatusCode::getCodeMessage($code);
        }
        return self::json([
            'code'    => $code,
            'status'  => 0,
            'message' => $message,
        ]);
    }

    public function write($data)
    {
        self::setEnd();
        return self::$response->write(json_encode($data));
    }
}
