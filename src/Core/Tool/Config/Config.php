<?php
namespace Ububs\Core\Tool\Config;
use Ububs\Core\Tool\Factory;

class Config extends Factory
{

    public static $config;

    /**
     * 获取配置
     * @param  string  $data    键app.SERVER_TYPE
     * @param  string  $default 默认值
     * @param  boolean $strict  是否严格匹配
     * @return string | array
     */
    public static function get($data, $default = null, $strict = false)
    {
        $fileName = $key = '';
        if (strpos($data, '.') > -1) {
            list($fileName, $key) = \explode('.', $data);
        } else {
            $fileName = $data;
        }
        $result = isset(self::$config[$fileName]) ? self::$config[$fileName] : $default;
        if ($key !== '' && isset(self::$config[$fileName])) {
            $result = isset($result[$key]) ? $result[$key] : $default;
        }
        if ($strict && is_null($result)) {
            throw new \Exception("{data} config empty");
        }
        return $result;
    }

    /**
     * 设置参数值
     * @param  string  $data   键app.SERVER_TYPE
     * @param  string  $value  值
     * @param  boolean $strict 是否覆盖
     * @return bool
     */
    public static function set($data, $value, $cover = true)
    {
        $fileName = $key = '';
        if (strpos($data, '.') > -1) {
            list($fileName, $key) = explode('.', $data);
        } else {
            $fileName = $data;
        }
        if ($key === '') {
            if (isset(self::$config[$fileName])) return false;
            self::$config[$fileName] = $value;
        } else {
            $fileExist = isset(self::$config[$fileName]);
            if (!$fileExist) {
                self::$config[$fileName] = [];
            }
            $keyExist = isset(self::$config[$fileName][$key]);
            if (!$keyExist || $cover) {
                self::$config[$fileName][$key] = $value;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * 加载配置文件
     * @param  string $configPath 目录
     * @return bool
     */
    public static function load($configPath)
    {
        $config = [];
        $fileArr = Dir::tree($configPath, "/.php$/");
        if (!empty($fileArr)) {
            foreach ($fileArr as $dir => $filenameArr) {
                array_map(function($filename) use (&$config, $dir) {
                    $filePath = $dir . DS . $filename;
                    if (function_exists("opcache_invalidate")) {
                        \opcache_invalidate($filePath);
                    }
                    $config[basename($filename, ".php")] = include "{$filePath}";
                }, $filenameArr);
            }
        }
        self::$config = $config;
        return true;
    }
}