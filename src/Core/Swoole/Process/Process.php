<?php
namespace Ububs\Core\Swoole\Process;

use Swoole\Process as SwooleProcess;
use Ububs\Core\Swoole\Process\Adapter\ProcessBuild;

class Process
{

    use ProcessBuild;

    private static $processList = [];

    private static $instance;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Process();
        }
        return self::$instance;
    }

    public function create(string $processName = '')
    {
        if ($processName && isset(self::$processList[$processName])) {
            return false;
        }
        list($func, $rs, $pt) = $this->parseBuildParams($processName);
        $process              = new SwooleProcess(function (SwooleProcess $swooleProcess) use ($func) {
            call_user_func($func, $swooleProcess);
        }, $rs, $pt);
        $process->useQueue();
        $key = $process->start();
        if ($processName !== '') {
            $process->name($processName);
            $key = md5($processName);
        }
        self::$processList[$key] = $process;
    }

    public function getProcess($processName = '')
    {
        if ($processName === '') {
            return self::$processList;
        }
        $key = md5($processName);
        if (isset(self::$processList[$key])) {
            return self::$processList[$key];
        } else {
            return null;
        }
    }

    public function getProcessId($processName)
    {
        $process = $this->getProcess($processName);
        $result  = '';
        if ($process) {
            $result = $process->getPid();
        }
        return $result;
    }

    public function killByName(string $processName)
    {
        $pid = $this->getProcessId($processName);
        if ($pid) {
            \swoole_process::kill($pid, SIGTERM);
            return true;
        }
        return false;
    }

    public function kill(int $pid)
    {
        \swoole_process::kill($pid, SIGTERM);
        return true;
    }

    public function wait(bool $blocking = true)
    {
        return SwooleProcess::wait($blocking);
    }

}
