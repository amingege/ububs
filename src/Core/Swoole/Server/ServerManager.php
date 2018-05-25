<?php
namespace Ububs\Core\Http;

class ServerManager
{

    private static $instance;
    private static $serverInstance;
    private static $isStart = false;
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

    public function start(): void
    {
        $this->serverInit();
        $this->addEventListener();
        $this->getServer()->start();
    }

    public function stop()
    {
        if (!self::$isStart) {
            return true;
        }
        
    }

    public function restart()
    {
        $this->stop();
        $this->start();
    }

    private function serverInit(): void
    {
        if (isset(self::$serverInstance)) {
            return self::$serverInstance;
        }
        self::$serverType = Config::get('app.server_type', 'SWOOLE_HTTP_SERVER');
        switch (strtoupper(self::$serverType)) {
            case self::SWOOLE_HTTP_SERVER:
                self::$serverInstance = \Ububs\Core\Swoole\Server\Http::getInstance();
                break;

            case self::SWOOLE_WEBSOCKET_SERVER:
                self::$serverInstance = \Ububs\Core\Swoole\Server\Websocket::getInstance();
                break;

            default:
                # code...
                break;
        }
        self::$isStart = true;
        self::$serverInstance->init();
    }

    private function addEventListener(): void
    {
        self::$serverInstance->addEventListener();
    }

    private function getServer():  ? \swoole_server
    {
        return self::$serverInstance->getServer();
    }
}
