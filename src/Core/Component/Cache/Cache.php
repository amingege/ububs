<?php
namespace FwSwoole\Component\Cache;

use Ububs\Core\Component\Factory;
use FwSwoole\Core\Tool\Config;

class Cache extends Factory
{
    private static $cacheInstance = null;

    public static function getCacheInstance()
    {
        $config        = Config::get('app.cache');
        $cacheType     = isset($config['type']) ? strtoupper($config['type']) : 'REDIS';
        $cacheHost     = isset($config['host']) ? strval($config['host']) : '127.0.0.1';
        $cachePort     = isset($config['port']) ? strval($config['port']) : '';
        $cachePassword = isset($config['password']) ? strval($config['password']) : '';
        switch ($cacheType) {
            case 'REDIS':
                self::$cacheInstance = new \Redis();
                self::$cacheInstance->connect($cacheHost, $cachePort);
                break;

            default:
                # code...
                break;
        }
        return self::$cacheInstance;
    }
}
