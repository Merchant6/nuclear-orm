<?php

namespace Merchant\NuclearOrm\Core\Database;

use PDO;

class QueryBuilder
{
    protected static Connection $connection;
    public string $from;
    public array $where = [];
    public array $whereAnd = [];
    public string $limit;
    private string $table;
    private array $columns;
    private string $sql;

    public function __construct()
    {

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
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return self::$connection->connect();
    }

    /**
     * Set the table name
     *
     * @param string $table
     * @return $this
     */
    public function table(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Build a select statement
     *
     * @param array $columns
     * @return $this
     */
    public function select(array $columns = ['*']): self
    {
        $this->sql = sprintf("SELECT %s FROM %s", implode(', ', $columns), $this->table);
        return $this;
    }

    /**
     * Add a where clause to the sql statement
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->where[] = [$column, $operator, $value];
        $this->sql .= " WHERE ";
        foreach ($this->where as $clauses){
            $clauses[2] = '"' . $clauses[2] . '"';
            $this->sql .= implode(" ", $clauses);
        }

        return $this;
    }

    /**
     * Add a AND operator to the sql statement
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function and(string $column, string $operator, mixed $value): self
    {
        $this->whereAnd[] = [$column, $operator, $value];
        $this->sql .= " AND ";
        foreach ($this->whereAnd as $clauses){
            $clauses[2] = '"' . $clauses[2] . '"';
            $this->sql .= implode(" ", $clauses);
        }

        return $this;
    }

    /**
     * Get the sql statement
     *
     * @return string
     */
    public function query(): string
    {
        return $this->sql;
    }

    /**
     * Get the result when running a specified query
     *
     * @return bool|array
     */
    public function get(): bool|array
    {
        return $this->getConnection()
            ->query($this->query())
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}