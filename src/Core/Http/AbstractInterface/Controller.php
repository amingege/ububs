<?php 
namespace Ububs\Core\Http\AbstractInterface;

use Ububs\Core\Component\View\View;

abstract class Controller
{

	public function assign($key, $value)
	{
		return View::assign($key, $value);
	}

	protected function display($path = null)
	{
		return View::display($path);
	}

}