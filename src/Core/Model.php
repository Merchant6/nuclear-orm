<?php
declare(strict_types=1);

namespace Merchant\NuclearOrm\Core;

use Merchant\NuclearOrm\Core\DatabaseConnection;
use PDO;

abstract class Model
{
    protected PDO $connection;

    protected array $fillable;

    public function __construct()
    {
        $this->setConnection($this->connection);
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getFillable()
    {
        return $this->fillable;
    }

    public function isFillable(string $value)
    {
        return in_array($value, $this->getFillable());
    }
}
