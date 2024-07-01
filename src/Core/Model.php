<?php
declare(strict_types=1);

namespace Merchant\NuclearOrm\Core;

use Exception;
use Merchant\NuclearOrm\Core\Database\Connection;
use Merchant\NuclearOrm\Core\Database\QueryBuilder;
use PDO;

abstract class Model
{
    protected static Connection $connection;
    protected QueryBuilder $builder;
    protected string $table = '';
    protected array $fillable = [];
    protected array $attributes = [];

    public function __construct()
    {
        $this->builder = new QueryBuilder();
    }


    /**
     * Set the database connection
     *
     * @param $connection
     * @return void
     */
    public static function setConnection($connection): void
    {
        self::$connection = $connection;
    }

    /**
     * Get the current database connection
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return self::$connection;
    }

    /**
     * Get the table name associated with the model
     *
     * @return string
     */
    public function table(): string
    {
        if(property_exists(get_class($this), 'table') && !empty($this->table)){
            return $this->table;
        }

        $className = explode("\\", get_called_class());
        return lcfirst(end($className)) . "s";
    }

    /**
     * Get the mass-assignable attributes
     *
     * @return array
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * Check if an attribute is mass-assignable
     *
     * @param string $value
     * @return bool
     */
    public function isFillable(string $value): bool
    {
        return in_array($value, $this->getFillable());
    }

    /**
     * @param string $attribute
     * @return mixed
     * @throws Exception
     */
    public function getAttribute(string $attribute): mixed
    {
        if (!array_key_exists($attribute, $this->attributes)) {
            throw new Exception("Attribute $attribute does not exist.");
        }
        return $this->attributes[$attribute];
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return void
     * @throws Exception
     */
    public function setAttribute(string $attribute, mixed $value): void
    {
        if ($this->isFillable($attribute)) {
            $this->attributes[$attribute] = $value;
        } else {
            throw new Exception("Attribute $attribute is not fillable.");
        }
    }

    /**
     * @throws Exception
     */
    public function __get(string $attribute)
    {
        return $this->getAttribute($attribute);
    }

    /**
     * @throws Exception
     */
    public function __set(string $attribute, mixed $value): void
    {
        $this->setAttribute($attribute, $value);
    }
}
