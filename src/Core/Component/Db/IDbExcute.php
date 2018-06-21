<?php
namespace Ububs\Core\Component\Db;

interface IDbExcute
{

    public function get();

    public function first();

    public function all();

    public function count();

    public function find();

    public function value();
    
    public function exist();
}
