<?php
namespace Ububs\Core\Swoole\Server;



class ServerManager
{

    private static $instance;
    private static $serverInstance;

    const SWOOLE_SERVER           = 'SWOOLE_SERVER';
    const SWOOLE_HTTP_SERVER      = 'SWOOLE_HTTP_SERVER';
    const SWOOLE_WEBSOCKET_SERVER = 'SWOOLE_WEBSOCKET_SERVER';

    public static function getInstance(): Server
    {
        if (!isset(self::$instance)) {
            self::$instance = new ServerManager();
        }
        return self::$instance;
    }

    private function initServer()
    {
        if (isset(self::$serverInstance)) {
            return self::$serverInstance;
        }
        self::$serverType = Config::get('server_type');
        switch (strtoupper(self::$serverType)) {
            case self::SWOOLE_HTTP_SERVER:
                self::$serverInstance = \Ububs\Core\Swoole\Server\Http::getInstance();
                break;

            case self::SWOOLE_WEBSOCKET_SERVER:
                self::$serverInstance = \Ububs\Core\Swoole\Server\Websocket::getInstance();
                break;
        }
        self::$serverInstance->init();
        EventManager::getInstance()->registerEvents();
    }

    private function getServer()
    {
        return self::$serverInstance->getServer();
    }
}
