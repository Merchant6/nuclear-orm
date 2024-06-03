<?php

//Bootstraping the files

require_once __DIR__ . "/vendor/autoload.php";

use Merchant\NuclearOrm\Core\Nuclear;

$nuclear = new Nuclear([
    'connection' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'test',
    'user' => 'merchant',
    'password' => 'Thealien862!',
    'port' => 3306,
    'persistent' => true,
]);

var_dump($nuclear->getConnection()->status());