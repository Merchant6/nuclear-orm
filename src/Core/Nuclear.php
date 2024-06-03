<?php
declare(strict_types=1);

namespace Merchant\NuclearOrm\Core;

use Merchant\NuclearOrm\Core\Database\Connection;

class Nuclear
{   
    private Connection $connection;

    public function __construct(array $connectionParams)
    {
        $this->connection = $this->createConnectionfromParams($connectionParams);
    }

    /**
     * Takes an array of params and pass it to 
     * the ConnectionManager class
     * 
     * @param array $params
     * @return Connection
     */
    public function createConnectionfromParams(array $params): Connection
    {   
        $connection = new Connection(
            connection: $params['connection'],
            host: $params['host'],
            database: $params['database'],
            user: $params['user'],
            password: $params['password'],
            port: $params['port'] ?? 3306,
            persistentConnection: $params['persistent'],
        );

        return $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
