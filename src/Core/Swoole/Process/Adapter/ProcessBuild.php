<?php
namespace Ububs\Core\Swoole\Process\Adapter;

trait ProcessBuild
{

    protected $processParams = [];

    public function parseBuildParams()
    {
        if (!$params = $this->processParams) {
            throw new \Exception("Error Processing Request", 1);

        }
        if (!isset($params['func']) || !isset($params['rs']) || !isset($params['pt'])) {
            throw new \Exception("Error Processing Request", 1);
        }
        return [
            $params['func'],
            $params['rs'],
            $params['pt'],
        ];
    }

    public function build(array $data)
    {
        $func = isset($data['func']) ? $data['func'] : '';
        $rs   = isset($data['rs']) ? $data['rs'] : false;
        $pt   = isset($data['pt']) ? $data['pt'] : 2;
        if (!$func) {
            throw new \Exception("Error Processing Request", 1);
        }
        $this->processParams = [
            'func' => $func,
            'rs'   => $rs,
            'pt'   => $pt,
        ];
        return self::getInstance();
    }
}
