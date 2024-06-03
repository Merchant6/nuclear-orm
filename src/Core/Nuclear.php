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
    private function createConnectionFromParams(array $params): Connection
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

    /**
     * Undocumented function
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
