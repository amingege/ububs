<?php
namespace Ububs\Core\Swoole\Task;

use Ububs\Core\Swoole\Factory;
use Ububs\Core\Swoole\Server\ServerManager;

class TaskManager extends Factory
{

    public function task($name, $callback, $taskId = -1)
    {
        return ServerManager::getInstance()->getServer()->task($name, $taskId, $callback);
    }

}
