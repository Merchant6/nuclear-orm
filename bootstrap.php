<?php

//Bootstraping the files

require_once __DIR__ . "/vendor/autoload.php";

use Dotenv\Dotenv;
use Merchant\NuclearOrm\Models\Test;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo json_encode((new Test())->isFillable('name'));