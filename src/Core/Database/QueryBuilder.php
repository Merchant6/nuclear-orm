<?php

namespace Merchant\NuclearOrm\Core\Database;

use Exception;
use InvalidArgumentException;
use PDO;
use PDOStatement;

class QueryBuilder
{
    protected static Connection $connection;

    /**
     * @var array<mixed>
     */
    public array $where = [];

    /**
     * @var array<mixed>
     */
    public array $whereAnd = [];

    /**
     * @var array<mixed>
     */
    public array $values = [];

    /**
     * @var string
     */
    private string $table = '';

    /**
     * @var string
     */
    private string $sql = '';

    public function __construct()
    {

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
     * @param string[] $columns
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
            $this->values[] = $clauses[2];
            $clauses[2] = "?";
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
            $this->values[] = $clauses[2];
            $clauses[2] = "?";
            $this->sql .= implode(" ", $clauses);
        }

        return $this;
    }

    /**
     * Add limit to the sql statement
     *
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->sql .= " LIMIT ";
        $this->sql .= $limit;
        return $this;
    }

    /**
     * Add offset to the sql statement
     *
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->sql .= " OFFSET ";
        $this->sql .= $offset;
        return $this;
    }

    /**
     * Insert a record to the database
     *
     * @param array<string, mixed> $data
     * @return bool|PDOStatement
     * @throws InvalidArgumentException
     */
    public function insert(array $data): bool|PDOStatement
    {
        if($data == null){
            throw new InvalidArgumentException('Array cannot be null');
        }

        $this->sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', array_keys($data)),
            implode(', ', array_map(function ($value) {
                    $this->values[] = $value;
                    return $value = "?";
                }, $data ))
        );

        return $this->prepareAndExecute();
    }

    /**
     * Update a existing record in the database
     *
     * @param array<string, mixed> $data
     * @return $this
     * @throws InvalidArgumentException
     */
    public function update(array $data): self
    {
        if($data == null){
            throw new InvalidArgumentException('Array cannot be null');
        }
        
        $this->sql = "UPDATE " . $this->table . " SET " . implode(', ', array_map(function ($key, $value) {
                    $this->values[] = $value;
                    return "$key = ?";
                }, array_keys($data), array_values($data))
            );

        return $this;
    }

    /**
     * Delete an existing record from database
     *
     * @return $this
     */
    public function delete(): self
    {
        $this->sql = sprintf(
            "DELETE FROM %s",
            $this->table
        );

        return $this;
    }

    /**
     * @param string $table
     * @return string[]
     */
    public function qualifyColumns(string $table): array
    {
        $sql = "SELECT COLUMN_NAME FROM information_schema.columns WHERE table_name = :table_name";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':table_name' => $table]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_column($columns, 'COLUMN_NAME');
    }

    /**
     * Get the sql statement with values
     *
     * @return string
     */
    public function statement(): string
    {   
        $values = array_values($this->values);
        $this->sql = str_replace('?', '%s', $this->sql);

        return sprintf($this->sql, ...$values);
    }

    /**
     * Get the sql query
     *
     * @return string
     */
    public function query(): string
    {
        return $this->sql;
    }

    /**
     * Returns a prepared statement
     *
     * @return bool|PDOStatement
     */
    public function prepareAndExecute(): bool|PDOStatement
    {
        $stmt = $this->getConnection()
            ->prepare($this->query());

        $stmt->execute($this->values);

        return $stmt;
    }

    /**
     * Placeholder method for prepareAndExecute
     *
     * @return bool|PDOStatement
     */
    public function run(): bool|PDOStatement
    {
        return $this->prepareAndExecute();
    }

    /**
     * Get the result when running a specified query
     *
     * @param int $mode
     * @return mixed
     */
    public function get(int $mode = PDO::FETCH_ASSOC): mixed
    {   
        /**
         * @phpstan-ignore method.nonObject
         */
        return $this->prepareAndExecute()
            ->fetchAll($mode);
    }
}