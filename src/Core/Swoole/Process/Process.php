<?php
namespace Ububs\Core\Swoole\Process;

use Swoole\Process as SwooleProcess;
use Ububs\Core\Swoole\Process\Adapter\ProcessBuild;

class Process
{

    use ProcessBuild;

    private static $processList     = [];
    private static $processNameList = [];

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
        list($func, $rs, $pt) = $this->parseBuildParams();
        $process              = new SwooleProcess(function (SwooleProcess $swooleProcess) use ($func) {
            call_user_func($func, $swooleProcess);
        }, $rs, $pt);
        $process->useQueue();
        $pid = $process->start();
        if ($processName !== '') {
            $process->name($processName);
            self::$processNameList[$processName] = $pid;
        }
        self::$processList[$pid] = $process;
        $this->afterProcess();
    }

    public function getProcess($processName = '')
    {
        if ($processName === '') {
            return self::$processList;
        }
        if (isset(self::$processNameList[$processName]) && isset(self::$processList[self::$processNameList[$processName]])) {
            return self::$processList[self::$processNameList[$processName]];
        } else {
            return null;
        }
    }

    public function getProcessId($processName)
    {
        $process = $this->getProcess($processName);
        $result  = '';
        if ($process) {
            $result = $process->pid;
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

    public function deleteProcess($pid)
    {
        if (!isset(self::$processList[$pid])) {
            return false;
        }
        unset(self::$processList[$pid]);
        $name = array_search($pid, self::$processNameList);
        if ($name) {
            unset(self::$processNameList[$name]);
        }
        return true;
    }

    public function afterProcess()
    {

    }

}
