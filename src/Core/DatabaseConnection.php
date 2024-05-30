<?php
declare(strict_types=1);

namespace Merchant\NuclearOrm\Core;
use PDO;

/**
 * A database connection class
 */
class DatabaseConnection
{   
    private string $connection;
    private string $host;
    private string $port;
    private string $database;
    private string $user;
    private string $password;
    private string $persistentConnection;

    public function __construct()
    {
        $this->connection = env('DB_CONNECTION');
        $this->host = env('DB_HOST');
        $this->port = env('DB_PORT');
        $this->database = env('DB_DATABASE');
        $this->user = env('DB_USERNAME');
        $this->password = env('DB_PASSWORD');
        $this->persistentConnection = env('DB_PERSISTENT_CONNECTION');
    }

    /**
     * Create a new database connection
     *
     * @return PDO
     */
    public function connect(): PDO
    {
        $conn = new PDO(
            "$this->connection:host=$this->host;port=$this->port;dbname=$this->database",
            $this->user,
            $this->password,
            [
                PDO::ATTR_PERSISTENT => $this->persistentConnection,
            ]
        );

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn;
    }
}
