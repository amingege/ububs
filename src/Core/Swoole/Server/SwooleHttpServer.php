<?php
namespace Ububs\Core\Swoole\Server;

use Ububs\Core\Request;
use Ububs\Core\Response;
use Ububs\Core\Tool\Config;
use Ububs\DB\DB;
use Ububs\Route\Route;

class SwooleHttpServer
{

    private static $instance;
    private static $server;

    public static function getInstance(): Server
    {
        if (!isset(self::$instance)) {
            self::$instance = new SwooleHttpServer();
        }
        return self::$instance;
    }

    /**
     * swoole_http_server服务初始化
     * @return void
     */
    public function init(): void
    {
        if (!\extension_loaded('swoole')) {
            throw new \Exception("no swoole extension. get: https://github.com/swoole/swoole-src");
        }
        $config                   = Config::get('app.swoole_http_server');
        $host                     = isset($config['host']) ? strval($config['host']) : '0.0.0.0';
        $port                     = isset($config['port']) ? strval($config['port']) : '9501';
        $worker_num               = isset($config['worker_num']) ? intval($config['worker_num']) : 4;
        $daemonize                = isset($config['daemonize']) ? intval($config['daemonize']) : false;
        $max_request              = isset($config['max_request']) ? intval($config['max_request']) : 10000;
        $dispatch_mode            = isset($config['dispatch_mode']) ? intval($config['dispatch_mode']) : 3;
        $debug_mode               = isset($config['debug_mode']) ? intval($config['debug_mode']) : 1;
        $task_worker_num          = isset($config['task_worker_num']) ? intval($config['task_worker_num']) : 4;
        $heartbeat_check_interval = isset($config['heartbeat_check_interval']) ? intval($config['heartbeat_check_interval']) : 60;
        $heartbeat_idle_time      = isset($config['heartbeat_idle_time']) ? intval($config['heartbeat_idle_time']) : 600;
        self::$server             = new \Swoole_http_server($host, $port);
        // 初始化配置
        self::$server->set(
            array(
                'worker_num'               => $worker_num,
                'daemonize'                => $daemonize,
                'max_request'              => $max_request,
                'dispatch_mode'            => $dispatch_mode,
                'debug_mode'               => $debug_mode,
                'task_worker_num'          => $task_worker_num,
                'heartbeat_check_interval' => $heartbeat_check_interval,
                'heartbeat_idle_time'      => $heartbeat_idle_time,
            )
        );
    }

    /**
     * 设置客户端回调对象
     * @param object $client 客户端类
     */
    public function setClient($client)
    {
        if (!is_object($client)) {
            throw new \Exception('client must object');
        }
        if (!($client instanceof self)) {
            throw new \Exception('client must extends swoole server');
        }
        self::$client = $client;
    }

    /**
     * 注册时间
     * @return void
     */
    public function registerEvents()
    {
        self::$server->on('Start', array($this, 'doStart'));
        self::$server->on('WorkerStart', array($this, 'doWorkerStart'));
        self::$server->on('WorkerError', array($this, 'doWorkerError'));
        self::$server->on('request', array($this, 'doRequest'));
        self::$server->on('Task', array($this, 'doTask'));
        self::$server->on('Finish', array($this, 'doFinish'));
    }

    /**
     * 服务端开启动作回调
     * @param  object $serv server对象
     * @return void
     */
    public function doStart($serv)
    {
        self::$client->onStart($serv);
    }

    /**
     * 服务端进程开启动作回调
     * @param  object $serv      server对象
     * @param  int    $worker_id 进程id
     * @return void
     */
    public function doWorkerStart($serv, $worker_id)
    {
        // 判定是否为Task Worker进程
        if ($worker_id >= self::$server->setting['worker_num']) {

        }
        if ($worker_id == 0) {
            // 定时器
            // self::$server->tick(1000, function() {
            //     file_put_contents(Ububs_ROOT . '/time2.txt', 1, FILE_APPEND);
            // });
            // swoole_timer_tick(1000, function() {
            //     file_put_contents(Ububs_ROOT . '/time.txt', 1, FILE_APPEND);
            // });
        }
        DB::getInstance()->connect();
        self::$client->onWorkerStart($serv, $worker_id);
    }

    public function doWorkerError(swoole_server $serv, int $worker_id, int $worker_pid, int $exit_code, int $signal)
    {
        echo 11111;
        return 11111;
    }

    /**
     * request动作回调
     * @param  \swoole_http_request  $request  request对象
     * @param  \swoole_http_response $response response对象
     * @return void
     */
    public function doRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
            return $response->end();
        }
        $httpMethod = $request->server['request_method'];
        $pathInfo   = rawurldecode($request->server['path_info']);
        // 匹配路由
        $route     = Route::getInstance();
        $routeInfo = $route->getDispatcher()->dispatch($httpMethod, $pathInfo);
        $result    = '';
        // 解析路由
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                $result = json_encode([
                    'status'  => ERROR_STATUS,
                    'message' => '404 NOT FOUND!',
                ]);
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $result         = json_encode([
                    'status'  => ERROR_STATUS,
                    'message' => "405 Method Not Allowed!",
                ]);
                break;
            case \FastRoute\Dispatcher::FOUND:
                Request::init($request);
                Response::init($response);
                // $actionArr = $route->parseRouteInfo($routeInfo);
                \ob_start();
                $result = $route->run($routeInfo);
                if (is_array($result)) {
                    $result = json_encode($result);
                }
                $result = \ob_get_contents() . $result;
                \ob_end_clean();

                break;
        }

        if (!Response::isEnd()) {
            $response->end($result);
        }
    }

    /**
     * task 动作回调
     * @return void
     */
    public function doTask($serv, $task_id, $from_id, $data)
    {
        $data     = \json_decode($data, true);
        $taskType = $data['__TASK_TYPE__'];
        unset($data['__TASK_TYPE__']);
        self::$client->onTask($serv, $task_id, $from_id, $taskType, $data);
    }

    /**
     * finish 动作回调
     * @return void
     */
    public function doFinish($serv, $task_id, $data)
    {
        self::$client->onTaskFinish($serv, $task_id, $data);
    }

    private function registerExceptionHandler(): void
    {
        \set_exception_handler(__CLASS__ . '::exceptionHandler');
        \register_shutdown_function(__CLASS__ . '::shutdownHandler');
        \set_error_handler(__CLASS__ . '::errorHandler');
    }

    /**
     * @param $exception
     * @return mixed
     * @desc 默认的异常处理
     */
    final public static function exceptionHandler($exception)
    {
        $errorData = [
            'status'  => ERROR_STATUS,
            'message' => $exception,
        ];
        Log::info($errorData);
        return Response::json($errorData);
    }

    /**
     * @desc 默认的fatal处理
     */
    final public static function shutdownHandler()
    {
        $error     = \error_get_last();
        $errorData = [
            'status'  => ERROR_STATUS,
            'message' => $error['message'],
            'file'    => $error['file'],
            'line'    => $error['line'],
        ];
        Log::info($errorData);
        return Response::json($errorData);
    }

    /**
     * @desc 默认的fatal处理
     */
    final public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $errorData = [
            'status'  => ERROR_STATUS,
            'message' => $errstr,
            'file'    => $errfile,
            'line'    => $errline,
        ];
        Log::info($errorData);
        return Response::json($errorData);
    }

}
