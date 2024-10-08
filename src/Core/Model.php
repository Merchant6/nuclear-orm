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
    protected string $table = '';
    protected array $fillable = [];
    protected array $attributes = [];
    protected array $guarded = [];
    protected array $hidden = [];
    protected array $columns = [];
    protected bool $exists = false;
    protected array $hold = [];
    protected string $primaryKey = 'id';
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
     * @param array $columns
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

    public function all()
    {
        return $this->select()->get();
    }

    /**
     * Create a new record in the table
     *
     * @param array $data
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
     * @param array $data
    //  * @return bool|PDOStatement
     * @throws MassAssignmentException
     */
    public function update(array $data)
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
     * @param mixed $value
     * @param array $columns
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

    public function save()
    {   
        if($this->exists){
            return $this->update(array_reverse($this->attributes));
        }

        return $this->buildWithTable()
            ->insert($this->attributes);
    }

    
    public function __get(string $attribute)
    {
        return $this->getAttribute($attribute);
    }

    public function __set(string $attribute, mixed $value): void
    {
        $this->setAttribute($attribute, $value);
    }
}
