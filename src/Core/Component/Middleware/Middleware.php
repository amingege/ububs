<?php
namespace FwSwoole\Middleware;

use FwSwoole\Core\Factory;

abstract class Middleware extends Factory
{

	abstract public function handle();
}
