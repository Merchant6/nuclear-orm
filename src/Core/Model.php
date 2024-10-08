<?php
declare(strict_types=1);

namespace Merchant\NuclearOrm\Core;

use Merchant\NuclearOrm\Core\Database\Connection;
use Merchant\NuclearOrm\Core\Database\QueryBuilder;
use PDO;
use PDOStatement;
use Exception;
use Merchant\NuclearOrm\Core\Exceptions\AttributeNotFoundException;
use Merchant\NuclearOrm\Core\Exceptions\HiddenAttributeException;
use Merchant\NuclearOrm\Core\Exceptions\MassAssignmentException;

abstract class Model
{
    protected static Connection $connection;
    protected QueryBuilder $builder;

    /**
     * @var string
     */
    protected string $table = '';

    /**
     * @var string[]
     */
    protected array $fillable = [];

    /**
     * @var string[]
     */
    protected array $attributes = [];

    /**
     * @var string[]
     */
    protected array $guarded = [];

    /**
     * @var string[]
     */
    protected array $hidden = [];

    /**
     * @var string[]
     */
    protected array $columns = [];

    /**
     * @var bool
     */
    protected bool $exists = false;

    /**
     * @var string[]
     */
    protected array $hold = [];

    /**
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * @var mixed
     */
    protected mixed $primaryKeyValue;

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
    public static function setConnection(Connection $connection): void
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
     * Return the primary key name
     *
     * @return string
     */
    public function getKeyName(): string
    {
        return $this->primaryKey;
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
     * @return string[]
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
     * @return string[]
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * Get the guarded attributes
     *
     * @return string[]
     */
    public function getGuarded(): array
    {
        return $this->guarded;
    }

    /**
     * Get the hidden attributes
     *
     * @return string[]
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
     * @throws AttributeNotFoundException | HiddenAttributeException
     */
    public function getAttribute(string $attribute): mixed
    {
        if (!array_key_exists($attribute, $this->attributes)) {
            throw new AttributeNotFoundException("Attribute $attribute does not exist.");
        }

        if(in_array($attribute, $this->hidden)) {
            throw new HiddenAttributeException("Attribute $attribute is hidden.");
        }

        return $this->attributes[$attribute];
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return void
     * @throws MassAssignmentException
     */
    public function setAttribute(string $attribute, mixed $value): void
    {
        if ($this->isFillable($attribute)) {
            $this->attributes[$attribute] = $value;
        } else {
            throw new MassAssignmentException("Attribute $attribute is not mass-assignable.");
        }
    }

    /**
     * @param string[] $columns
     * @return QueryBuilder
     */
    public function select(array $columns = ['*']): QueryBuilder
    {
        if ($columns[0] === '*') {
            $columns = $this->columns();
        }
    
        $diff = diff($columns, $this->getHidden());
    
        return $this->buildWithTable()->select($diff);
    }

    /**
     * @return mixed
     */
    public function all(): mixed
    {
        return $this->select()->get();
    }

    /**
     * Create a new record in the table
     *
     * @param array<string, string> $data
     * @return bool|PDOStatement
     * @throws MassAssignmentException
     */
    public function create(array $data): bool|PDOStatement
    {
        $unfillable = diff(array_keys($data), $this->getFillable());
        if($unfillable)
        {
            throw new MassAssignmentException(sprintf(
                'Cannot INSERT into column [%s] that is not mass-assignable.',
                implode(',', array_keys($unfillable))
            ));
        }

        return $this->buildWithTable()->insert($data);
    }

    /**
     * @param array<string, string> $data
     * @return bool|PDOStatement
     * @throws MassAssignmentException
     */
    public function update(array $data): bool|PDOStatement
    {
        $unfillable = diff(array_keys($data), $this->getFillable());
        if($unfillable)
        {
            throw new MassAssignmentException(sprintf(
                'Cannot UPDATE column [%s] that is not mass-assignable.',
                implode(',', array_keys($unfillable))
            ));
        }
        
        return $this->buildWithTable()
            ->update($data)
            ->where($this->getKeyName(), '=', $this->primaryKeyValue)
            ->run();
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
     * Find a record using a column, defaults
     * to id
     *
     * @param mixed $primaryKeyValue
     * @param string[] $columns
     * @return mixed
     */
    public function find(mixed $primaryKeyValue, array $columns = ['*']): mixed
    {
        $found = $this->buildWithTable()
            ->select($columns)
            ->where($this->getKeyName(), '=', $primaryKeyValue)
            ->get();

        unset($this->buildWithTable()->where);
        unset($this->buildWithTable()->values);

        if($found){
            $this->exists = true;
            $this->primaryKeyValue = $found[0][$this->getKeyName()];
            return $found[0];
        }

        return $found;
    }

    /**
     * @return bool|PDOStatement
     */
    public function save(): bool|PDOStatement
    {   
        if($this->exists){
            return $this->update(array_reverse($this->attributes));
        }

        return $this->buildWithTable()
            ->insert($this->attributes);
    }

    /**
     * @param string $attribute
     * @return mixed
     */
    public function __get(string $attribute): mixed
    {
        return $this->getAttribute($attribute);
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return void
     */
    public function __set(string $attribute, mixed $value): void
    {
        $this->setAttribute($attribute, $value);
    }
}
