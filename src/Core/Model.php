<?php
declare(strict_types=1);

namespace Merchant\NuclearOrm\Core;

use Exception;
use Merchant\NuclearOrm\Core\Database\Connection;
use Merchant\NuclearOrm\Core\Database\QueryBuilder;
use PDO;
use PDOStatement;

abstract class Model
{
    protected static Connection $connection;
    protected QueryBuilder $builder;
    protected string $table = '';
    protected array $fillable = [];
    protected array $attributes = [];
    protected array $guarded = [];
    protected array $hidden = [];
    protected array $columns = [];

    public function __construct()
    {
        $this->boot();
    }

    /**
     * Boot the model instance
     *
     * @return void
     */
    public function boot(): void
    {
        $this->builder = new QueryBuilder();
        $this->qualifyColumns();
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
     * Return the QueryBuilder instance with
     * the table
     *
     * @return QueryBuilder
     */
    public function buildWithTable(): QueryBuilder
    {
        return $this->builder
            ->table($this->table());
    }

    /**
     * Get the table columns
     *
     * @return array
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @return void
     */
    public function qualifyColumns(): void
    {
        $this->columns = $this->builder
            ->qualifyColumns($this->table());
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
     * Get the guarded attributes
     *
     * @return array
     */
    public function getGuarded(): array
    {
        return $this->guarded;
    }

    /**
     * Get the hidden attributes
     *
     * @return array
     */
    public function getHidden(): array
    {
        return $this->hidden;
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
     * Check if an attribute is hidden.
     *
     * @param string $attributes
     * @return bool
     */
    public function isHidden(string $attributes): bool
    {
        return in_array($attributes, $this->getHidden());

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

        if(in_array($attribute, $this->hidden)) {
            throw new Exception("Attribute $attribute is hidden.");
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
            throw new Exception("Attribute $attribute is not mass-assignable.");
        }
    }

    /**
     * @param array $columns
     * @return QueryBuilder
     */
    public function select(array $columns = ['*']): QueryBuilder
    {
        if($columns[0] === '*'){
            $columns = $this->columns();
            $diff = array_diff($columns, $this->getHidden());

            return $this->buildWithTable()->select($diff);
        }

        $diff = array_diff($columns, $this->getHidden());
        if ($diff)
        {
            return $this->buildWithTable()->select($diff);
        }

        return $this->buildWithTable()
            ->select();
    }

    public function all()
    {
        return $this->select()->get();
    }

    /**
     * Create a new record in the table
     *
     * @param array $data
     * @return bool|PDOStatement
     * @throws Exception
     */
    public function create(array $data): bool|PDOStatement
    {
        $unfillable = array_diff(array_keys($data), $this->getFillable());
        if($unfillable)
        {
            throw new Exception(sprintf(
                'Column [%s] is not mass-assignable.',
                implode(',', array_keys($unfillable))
            ));
        }

        return $this->buildWithTable()->insert($data);
    }


    /**
     * Add a where clause to the query
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->builder->where($column, $operator, $value);

        return $this;
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
