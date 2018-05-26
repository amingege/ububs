<?php
namespace Ububs\Core\Swoole\Event;
use Ububs\Core\Swoole\Server\ServerManager;


class EventManager
{

    private static $instance;
    private $registerEventsLists = [
        'swoole_http_server' => [
            
            'Start' => 'onStart',
            'WorkerStart' => 'onWorkerStart',
            'WorkerError' => 'onWorkerError',
            'Request' => 'onRequest',
            'Task' => 'onTask',
            'Finish' => 'onFinish'
        ],
        'swoole_server' => [],
        'swoole_websocket_server' => []
    ];

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new EventManager();
        }
        return self::$instance;
    }

    public function addEventListener()
    {
        $type = ServerManager::getInstance()->getServerType();
        $server = ServerManager::getInstance()->getServer();
        $events = $this->registerEventsLists[strtolower($type)];
        if (!empty($events)) {
            foreach ($events as $event => $callback) {
                $server->on($event, [ServerManager::getInstance()->getServerInstance(), $callback]);
            }
        }
    }
}
