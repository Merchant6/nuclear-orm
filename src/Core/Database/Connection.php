<?php
declare(strict_types=1);

namespace Merchant\NuclearOrm\Core\Database;
use PDO;

/**
 * A database connection class
 */
class Connection
{   
    public function __construct(
        private string $connection,
        private string $host,
        private string $database,
        private string $user,
        private string $password,
        private int $port = 3306,
        private bool $persistentConnection = true,
    )
    {
        $this->connect();
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

    /**
     * Get the current connection status
     *
     * @return string
     */
    public function status(): string
    {
        return $this->connect()->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    }
}
