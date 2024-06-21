<?php

//Bootstraping the files

require_once __DIR__ . "/vendor/autoload.php";

use Merchant\NuclearOrm\Core\Nuclear;

$nuclear = new Nuclear([
    'connection' => 'mysql',
    'host' => 'host',
    'database' => 'nuclear',
    'user' => 'user',
    'password' => 'password',
    'port' => 3306,
    'persistent' => true,
]);

$nuclear->boot();

$builder = new \Merchant\NuclearOrm\Core\Database\QueryBuilder();

var_dump(
    $builder
        ->table('nuclear')
        ->select()
        ->where('name', '=', 'John Doe')
        ->and('id', '=', '1')
        ->get()

);


