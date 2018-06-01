<?php
use Ububs\Core\Tool\Config\Config;
use Ububs\Core\Http\Interaction\Response;
use Ububs\Core\Swoole\Server\ServerManager;
use Ububs\Core\Tool\StatusCode\StatusCode;

/**
 * 获取config配置
 * @param  string $key    变量的key
 * @param  string $default 默认值
 * @return value
 */
function config($key, $default = null)
{
    return Config::get($key, $default);
}

/**
 * 调试打印内容
 * @param  array | string | object $data 
 * @return value       
 */
function write_response($data)
{
	return Response::write($data);
}

/**
 * 获取server对象
 * @return object swoole_server
 */
function getServ()
{
	return ServerManager::getServer();
}

function errorMessage($message)
{
	return Response::error(500, $message);
}