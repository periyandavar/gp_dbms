<?php
/**
 * Database
 * php version 7.3.5
 *
 * @category Database
 * @package  Database
 * @author   Periyandavar <periyandavar@gmail.com>
 * @license  http://license.com license
 * @link     http://url.com
 */

use Exception;

/**
 * Super class for all Database. All drivers should extend this Database
 * Database class consists of basic level functions for various purposes and
 * query building functionality
 *
 * @category Database
 * @package  Database
 * @author   Periyandavar <periyandavar@gmail.com>
 * @license  http://license.com license
 * @link     http://url.com
 */
abstract class Database
{
    /**
     * Database connection object
     */
    protected $con;
    /**
     * This will have the result set of the select query
     */
    protected $result;

    protected $dbQuery;

    public function __construct()
    {
        $this->dbQuery = new DBQuery();
    }

    public function __call($method, $arguments) {
        if (method_exists($this->dbQuery, $method)) {
            // Delegate the method call to the $b instance
            $result = call_user_func_array([$this->dbQuery, $method], $arguments);
            if ($result instanceof DBQuery) {
                return $this;
            }
            return $result;
        } else {
            throw new Exception("Method $method not found");
        }
    }

    /**
     * Abstract function which should implemented in the handler class
     * to close db connection
     *
     * @return void
     */
    abstract public function close();

    /**
     * This abstract function should implemented on the handlers to
     * directly run the Query
     *
     * @param string $sql        sql
     * @param array  $bindValues bind values
     *
     * @return bool
     */
    abstract public function runQuery(string $sql, array $bindValues = []): bool;

    /**
     * This abstract function should implemented on the handlers to run the Query
     * called by execute() function
     * It should return true on success and false on failure
     * if the executed query has the result set the set should be
     * stored in the $this->result
     *
     * @return bool
     */
    abstract protected function executeQuery(): bool;

    /**
     * This abstract function should implemented on the handlers to fetch the
     * result set called directly from the object
     * It should return a single row result as object on success and null on failure
     *
     * @return :object|bool|null
     */
    abstract public function fetch(); //:object|bool|null;

    /**
     * Disabling cloning the object from outside the class
     * 
     * @return void
     */
    private function __clone()
    {

    }

    /**
     * This abstract function should implemented on the handlers to get the
     * instance of the class in
     * singleton approch
     *
     * @param string $host   host name
     * @param string $user   User name
     * @param string $pass   Password
     * @param string $db     database
     * @param string $driver Driver
     *
     * @return Database
     */
    abstract public static function getInstance(
        string $host,
        string $user,
        string $pass,
        string $db,
        string $driver
    );

    /**
     * Current instance of the class
     */
    protected static $instance = null;

    /**
     * This will contains the executed full query after the execute() get executed
     *
     * @var string $query
     */
    protected $query;


    /**
     * This will contains the values to be bind
     *
     * @var array $bindValues
     */
    protected $bindValues;


    /**
     * Query function to run directly raw query with or without bind values
     *
     * @param string $query sql
     * @param array  $bindValues  bind values
     *
     * @return bool
     */
    public function query(string $query, array $bindValues = []): bool
    {
        // $this->_resetQuery();
        $query = trim($query);
        $this->query = $query;
        $this->bindValues = $bindValues;
        $result = $this->runQuery($this->query, $this->bindValues);
        return $result;
    }

    /**
     * Returns the last insert id
     *
     * @return int
     */
    abstract public function insertId(): int;

    /**
     * Execute the function that will execute the earlier build query
     *
     * @return bool
     */
    final public function execute(): bool
    {
        $result = true;

        $this->query = $this->dbQuery->getSQL();
        $this->bindValues = $this->dbQuery->getBindValues();

        try {
            $result = $this->executeQuery();
        } catch (Exception $e) {
            return false;
        }
        $this->dbQuery->reset();
        return $result;
    }

    /**
     * This is used to set the variable in database
     *
     * @param string $name  Variable Name
     * @param string $value Variable Value
     *
     * @return bool
     */
    public function set(string $name, string $value): bool
    {
        $this->query = "SET " . $name . " = " . $value;
        return $this->executeQuery();
    }

    /**
     * Starts the transactions
     *
     * @return bool
     */
    public function begin(): bool
    {
        $this->query = "START TRANSACTION";
        return $this->executeQuery();
    }

    /**
     * Commits the transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->runQuery("COMMIT");
    }

    /**
     * Rollbacks the transaction
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->runQuery("ROLLBACK");
    }

    abstract public function escape(string $value): string;

    public function getOne()
    {
        $this->dbQuery->limit(1);
        $this->execute();
        return $this->fetch();
    }

    public function getAll()
    {
        $data = [];
        $this->execute();
        while ($row = $this->fetch()) {
            $data[] = $row;
        }
        return $data;
    }

    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    public function setDbQuery($dbQuery)
    {
        $this->dbQuery = $dbQuery;

        return $this;
    }
}
