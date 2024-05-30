<?php

//Bootstraping the files

require_once __DIR__ . "/vendor/autoload.php";

use Dotenv\Dotenv;
use Merchant\NuclearOrm\Core\DatabaseConnection;


$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo json_encode((new DatabaseConnection)->connect()->getAttribute(PDO::ATTR_CONNECTION_STATUS));