<?php
declare(strict_types=1);

namespace Merchant\NuclearOrm\Core;

use Merchant\NuclearOrm\Core\Database\Connection;
use Merchant\NuclearOrm\Core\Database\QueryBuilder;

class Nuclear
{   
    private Connection $connection;

    /**
     * @param array<string, mixed> $connectionParams
     */
    public function __construct(array $connectionParams)
    {
        $this->connection = $this->createConnectionfromParams($connectionParams);
    }

    /**
     * Takes an array of params and pass it to 
     * the ConnectionManager class
     * 
     * @param array<string, mixed> $params
     * @return Connection
     */
    private function createConnectionFromParams(array $params): Connection
    {
        return new Connection(
            connection: $params['connection'],
            host: $params['host'],
            database: $params['database'],
            user: $params['user'],
            password: $params['password'],
            port: $params['port'] ?? 3306,
            persistentConnection: $params['persistent'],
        );
    }

    /**
     * Undocumented function
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function boot(): void
    {
        QueryBuilder::setConnection($this->getConnection());
        Model::setConnection($this->getConnection());
    }
}
