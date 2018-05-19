<?php
namespace FwSwoole\Server\Adapter;

class SwooleWebsocketServer
{

    private static $instance;

    public static function getInstance(): Server
    {
        if (!isset(self::$instance)) {
            self::$instance = new SwooleWebsocketServer();
        }
        return self::$instance;
    }

    public function init()
    {
        if (!\extension_loaded('swoole')) {
            throw new \Exception("no swoole extension. get: https://github.com/swoole/swoole-src");
        }
        self::$server = new \swoole_websocket_server(Config::get('app.server_http_host'), Config::get('app.server_http_port'));
        $this->registerEvents();
    }

    public function start()
    {
        self::$server->start();
    }

    public function registerEvents()
    {
        self::$server->on('open', array(self::$client, 'onOpen'));
        self::$server->on('message', array(self::$client, 'onMessage'));
        self::$server->on('close', array(self::$client, 'onClose'));
    }
}
