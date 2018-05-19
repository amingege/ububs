<?php 
namespace Ububs\Core;

class Ububs
{
	public function __construct()
	{
		define('DS', DIRECTORY_SEPARATOR);
		define('UBUBS_ROOT', realpath(getcwd()));
	}

	public function init()
	{
		
	}
}
