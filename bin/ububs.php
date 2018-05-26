<?php

/**
 * 支持的命令
 *
 * // 框架安装，一件部署
 * php ububs install
 *
 * // server 相关命令
 * php ububs server:start
 * php ububs server:stop
 * php ububs server:restart
 *
 * // 数据库迁移
 * php ububs db:migration
 * php ububs db:migration refresh
 * php ububs db:migration --目录名
 *
 * // 填充数据
 * php ububs db:seed
 * php ububs db:seed refresh
 * php ububs db:seed --目录名
 */

foreach ([__DIR__ . '/../../../autoload.php', __DIR__ . '/../vendor/autoload.php'] as $file) {
    if (file_exists($file)) {
        require $file;
        break;
    }
}

use Ububs\Component\Command\Adapter\Server;
use Ububs\Core\Swoole\Server\ServerManager;
use Ububs\Core\Swoole\Server\DbManager;
use Ububs\Ububs;

class UbubsCommand
{

    const INSTALL_FRAMEWORK = 'INSTALL';

    const TYPE_SERVER = 'SERVER';
    const TYPE_DB     = 'DB';

    const SERVER_START   = 'SERVER_START';
    const SERVER_STOP    = 'SERVER_STOP';
    const SERVER_RESTART = 'SERVER_RESTART';

    const DB_SEED      = 'DB_SEED';
    const DB_MIGRATION = 'DB_MIGRATION';

    private static $codeMessage = [
        'ERROR_INPUT'            => '请输入正确的命令',
        'INIT_FRAMEWORK_SUCCESS' => '框架初始化成功',
        'SERVER_START_SUCCESS'   => '服务器开启成功',
    ];

    private static $serverCommand = ['start' => 'start', 'restart' => 'restart', 'stop' => 'stop'];
    private static $dbCommand     = ['seed' => 'seed', 'migration' => 'migration'];

    public function __construct()
    {
        // 全局变量初始化
        // define('DS', DIRECTORY_SEPARATOR);
        // define('UBUBS_ROOT', realpath(getcwd()));
        // // 配置文件初始化
        // Config::load(UBUBS_ROOT . '/config');
        // \date_default_timezone_set(Config::get('timezone', 'Asia/Shanghai'));
    }

    /**
     * 运行文件
     * @return bool
     */
    public function run()
    {
        $this->checkEnvironment();
        list($command, $params) = $this->parseCommand();
        if (strpos(':', $command) === false) {
            switch (strtoupper($command)) {
                case self::INSTALL_FRAMEWORK:
                    $this->installFramework();
                    break;

                default:
                    die(self::$codeMessage['ERROR_INPUT']);
                    break;
            }
        } else {
            list($type, $action) = explode(':', $command);
            switch (strtoupper($type)) {
                case self::TYPE_SERVER:
                    $action = $this->serverCommandAssemble($action);
                    if (!$action) {
                        die(self::$codeMessage['ERROR_INPUT']);
                    }
                    ServerManager::getInstance()->$action();
                    break;

                case self::TYPE_DB:
                    $action = $this->dbCommandAssemble($action);
                    if (!$action) {
                        die(self::$codeMessage['ERROR_INPUT']);
                    }
                    DbManager::getInstance()->$action($params);
                    break;

                default:
                    die(self::$codeMessage['ERROR_INPUT']);
                    break;
            }
        }
    }

    private function parseCommand()
    {
        global $argv;
        if (!isset($argv[1])) {
            die(self::$codeMessage['ERROR_INPUT']);
        }
        $command = $argv[1];
        $params  = isset($argv[2]) ? $argv[2] : '';
        return [$command, $params];
    }

    private function checkEnvironment()
    {
        if (version_compare(phpversion(), '7.1', '<')) {
            die("PHP version\e[31m must >= 7.1\e[0m\n");
        }
        if (version_compare(phpversion('swoole'), '1.9.5', '<')) {
            die("Swoole extension version\e[31m must >= 1.9.5\e[0m\n");
        }
        if (!class_exists('EasySwoole\Core\Core')) {
            die("Autoload fail!\nPlease try to run\e[31m composer install\e[0m in " . EASYSWOOLE_ROOT . "\n");
        }
    }

    private function installFramework()
    {
        
    }

    private function serverCommandAssemble($action)
    {
        return isset($this->serverCommand[$action]) ? $this->serverCommand[$action] : '';
    }

    private function dbCommandAssemble($action)
    {
        return isset($this->dbCommand[$action]) ? $this->dbCommand[$action] : '';
    }
}

$ububs = new UbubsCommand();
$ububs->run();
