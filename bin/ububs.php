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

use Ububs\Component\Command\Adapter\Database;
use Ububs\Component\Command\Adapter\Server;
use Ububs\Core\Tool\Config;
use Ububs\Core\Tool\Dir;
use Ububs\Ububs;
use Ububs\DB\Schema;

class UbubsCommand
{

    const TYPE_INSTALL = 'install';
    const TYPE_SERVER  = 'server';
    const TYPE_DB      = 'db';

    const SERVER_START   = 'start';
    const SERVER_STOP    = 'stop';
    const SERVER_RESTART = 'restart';

    const DB_MIGRATION = 'migration';
    const DB_SEED      = 'seed';
    const DB_REFRESH   = 'refresh';

    private static $codeMessage = [
        'ERROR_INPUT'            => '请输入正确的命令',
        'INIT_FRAMEWORK_SUCCESS' => '框架初始化成功',
        'SERVER_START_SUCCESS'   => '服务器开启成功',
    ];

    public function __construct()
    {
        // 全局变量初始化
        define('DS', DIRECTORY_SEPARATOR);
        define('UBUBS_ROOT', realpath(getcwd()));
        // 配置文件初始化
        Config::load(UBUBS_ROOT . '/config');
        \date_default_timezone_set(Config::get('timezone', 'Asia/Shanghai'));
    }

    /**
     * 运行文件
     * @return bool
     */
    public function run()
    {
        global $argv;
        if (!isset($argv[1])) {
            exit(self::$codeMessage['ERROR_INPUT']);
        }
        $result = '';
        try {
            if (strpos($argv[1], ':') === false) {
                $result = $this->parseSimpleCommand();
            } else {
                // server:start 等复杂命令
                $result = $this->parseComplexCommand();
            }
        } catch (Exception $e) {
            $result = $e->getMessage();
        }
        return $result;
    }

    private function parseSimpleCommand()
    {
        global $argv;
        $result = '';
        switch (strtolower($argv[1])) {
            case self::TYPE_INSTALL:
                // 框架部署
                $result = $this->initFramework();
                break;

            default:
                # code...
                break;
        }
        return $result;
    }

    private function parseComplexCommand()
    {
        global $argv;
        $result              = '';
        list($type, $action) = explode(':', strtolower($argv[1]));
        switch (strtolower($type)) {
            case self::TYPE_SERVER:
                $result = $this->serverAction($action);
                break;

            case self::TYPE_DB:
                $result = $this->dbAction($action);
                break;

            default:
                # code...
                break;
        }
        return $result;
    }

    private function serverAction($action)
    {
        $result = '';
        switch ($action) {
            case self::SERVER_START:
                $result = Ububs::start();
                if ($result) {
                    $result = self::$codeMessage['SERVER_START_SUCCESS'];
                }
                break;

            case self::SERVER_STOP:
                $result = Ububs::stop();
                break;

            case self::SERVER_RESTART:
                $result = Ububs::restart();
                break;

            default:
                # code...
                break;
        }
        return $result;
    }

    private function dbAction($action)
    {
        global $argv;
        $commandParamsCount = count($argv);
        $argv2              = isset($argv[2]) ? strtolower($argv[2]) : '';
        $result             = '';
        switch ($action) {
            case self::DB_MIGRATION:
                // 执行 /database/migrations 目录下的所有文件
                $targetDir = UBUBS_ROOT . DS . 'databases' . DS . 'Migrations';
                if (strpos($argv2, '--')) {
                    $targetDir .= DS . mb_substr($argv2, 2);
                    if (isset($argv[3]) && strtolower($argv[3]) == self::DB_REFRESH) {
                        Schema::setRefreshFlag();
                    }
                } else if ($argv2 === self::DB_REFRESH) {
                    Schema::setRefreshFlag();
                }
                $result = $this->runDirFunction($targetDir);
                break;

            case self::DB_SEED:
                // 执行 /database/seeds 目录下的所有文件
                $targetDir = UBUBS_ROOT . DS . 'databases' . DS . 'Seeds';
                if (strpos($argv2, '--')) {
                    $targetDir .= DS . mb_substr($argv2, 2);
                    if (isset($argv[3]) && strtolower($argv[3]) == self::DB_REFRESH) {
                        Schema::setRefreshFlag();
                    }
                } else if ($argv2 === self::DB_REFRESH) {
                    Schema::setRefreshFlag();
                }
                $result = $this->runDirFunction($targetDir);
                break;

            default:
                # code...
                break;
        }
    }
}

$ububs = new UbubsCommand();
$ububs->run();