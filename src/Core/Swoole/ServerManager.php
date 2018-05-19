<?php
namespace Ububs\Core\Http;

class ServerManager
{

    private static $instance;
    private static $serverInstance;

    public static function getInstance(): Server
    {
        if (!isset(self::$instance)) {
            self::$instance = new ServerManager();
        }
        return self::$instance;
    }

    public function start(): void
    {
        $this->init();
        $this->addEventListener();
        $this->getServer()->start();
    }

    private function init(): void
    {
        if (isset(self::$serverInstance)) {
            return self::$serverInstance;
        }
        self::$serverType = Config::get('app.server_type', 'swoole_http_server');
        switch (self::$serverType) {
            case self::SWOOLE_SERVER:
                self::$serverInstance = \Ububs\Core\Swoole\Server\SwooleServer::getInstance();
                break;

            case self::SWOOLE_HTTP_SERVER:
                self::$serverInstance = \Ububs\Core\Swoole\Server\SwooleHttpServer::getInstance();
                break;

            case self::SWOOLE_WEBSOCKET_SERVER:
                self::$serverInstance = \Ububs\Core\Swoole\Server\SwooleWebsocketServer::getInstance();
                break;

            default:
                # code...
                break;
        }
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
