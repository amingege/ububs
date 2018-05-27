<?php
use Ububs\Core\Tool\Config\Config;

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


function view()
{
	
}