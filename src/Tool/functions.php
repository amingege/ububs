<?php 
use Ububs\Core\Tool\Config\Config;
function config($data, $default = null)
{
	return Config::get($data, $default);
}