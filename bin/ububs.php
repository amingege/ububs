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
// 全局变量初始化
define('DS', DIRECTORY_SEPARATOR);
define('UBUBS_ROOT', __DIR__ . '/../src/');
// define('APP_ROOT', realpath(getcwd()));
define('APP_ROOT', __DIR__ . '/../../../../');

// composer 自动加载类
foreach ([__DIR__ . '/../../../autoload.php', __DIR__ . '/../vendor/autoload.php'] as $file) {
    if (file_exists($file)) {
        require $file;
        break;
    }
}

// 公共方法引入
foreach (getToolFilePaths(UBUBS_ROOT . 'Tool') as $file) {
    require $file;
}

use Ububs\Component\Command\Adapter\Server;
use Ububs\Core\Http\Interaction\Route;
use Ububs\Core\Swoole\Event\EventManager;
use Ububs\Core\Swoole\Server\ServerManager;
use Ububs\Core\Tool\Config\Config;
use Ububs\Core\Ububs;

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

    private $codeMessage = [
        'ERROR_INPUT'            => '请输入正确的命令',
        'INIT_FRAMEWORK_SUCCESS' => '框架初始化成功',
        'SERVER_START_SUCCESS'   => '服务器开启成功',
    ];

    private $serverCommand = ['start' => 'serverStart', 'restart', 'stop'];
    private $dbCommand     = ['seed', 'migration'];

    public function __construct()
    {
        $this->checkEnvironment();
        // 配置文件初始化
        Config::load([UBUBS_ROOT . 'Config', APP_ROOT . 'config']);
        \date_default_timezone_set(Config::get('timezone', 'Asia/Shanghai'));
    }

    /**
     * 运行文件
     * @return bool
     */
    public function run()
    {
        list($command, $params) = $this->parseCommand();
        if (strpos($command, ':') === false) {
            switch (strtoupper($command)) {
                case self::INSTALL_FRAMEWORK:
                    $this->installFramework();
                    break;

                default:
                    die($this->codeMessage['ERROR_INPUT']);
                    break;
            }
        } else {
            list($type, $action) = explode(':', $command);
            $commandFunc         = $this->commandAssemble($type, $action);
            $this->$commandFunc($params);
        }
    }

    private function parseCommand()
    {
        global $argv;
        if (!isset($argv[1])) {
            die($this->codeMessage['ERROR_INPUT']);
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
    }

    private function installFramework()
    {
        dir_make(APP_ROOT . 'config');
        dir_make(APP_ROOT . 'app/Http/Controllers');
        dir_make(APP_ROOT . 'routes');
        $routePath = APP_ROOT . 'routes/web.php';
        if (!is_file($routePath)) {
            file_put_contents($routePath, $this->routerWebContent());
        }
        dir_make(APP_ROOT . 'resources/assets');
        dir_make(APP_ROOT . 'resources/images');
        dir_make(APP_ROOT . 'resources/views');
    }

    private function commandAssemble($type, $action)
    {
        $commandLists = [];
        switch ($type = strtoupper($type)) {
            case self::TYPE_SERVER:
                $commandLists = $this->serverCommand;
                break;

            case self::TYPE_DB:
                $commandLists = $this->dbCommand;
                break;
        }
        $commandFunc = isset($commandLists[$action]) ? $commandLists[$action] : (in_array($action, $commandLists) ? $action : '');
        if (!$commandFunc) {
            die($this->codeMessage['ERROR_INPUT']);
        }
        return $commandFunc;
    }

    private function serverStart()
    {
        ServerManager::getInstance()->initServer();
        // 路由初始化
        Route::getInstance()->init();
        EventManager::getInstance()->addEventListener();
        ServerManager::getInstance()->getServer()->start();
    }

    private function serverStop($params)
    {
        ServerManager::getInstance()->stop();
    }

    private function serverReload($params)
    {
        ServerManager::getInstance()->reload();
    }

    private function routerWebContent()
    {
        return <<<'EOF'
<?php
$this->addRoute('GET', '/frontend', function() {
    echo 'success';
});
EOF;
    }
}

function getToolFilePaths($dir)
{
    $result = [];
    $files  = new \DirectoryIterator($dir);
    foreach ($files as $file) {
        if ($file->isDot()) {
            continue;
        }
        $result[] = $file->getPathName();
    }
    return $result;
}

$ububs = new UbubsCommand();
$ububs->run();
