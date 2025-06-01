<?php

namespace Database\Driver;

use Database\Database;
use Database\Exception\DatabaseException;
use Pdo;
use PdoException;

class PdoDriver extends Database
{
    /**
     * Instantiate a new PdoDriver instance
     *
     * @param string $host   Host Name
     * @param string $user   User Name
     * @param string $pass   Password
     * @param string $db     Database Name
     * @param array  $config Other Configs
     */
    private function __construct(
        string $host,
        string $user,
        string $pass,
        string $db,
        array $config = []
    ) {
        try {
            parent::__construct();
            $driver = $config['driver'] ?? 'mysql';
            $this->con = new PDO("$driver:host=$host;dbname=$db;", $user, $pass);
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NUM);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), DatabaseException::DATABASE_CONNECTION_ERROR);
        }
    }

    /**
     * Disabling cloning the object from outside the class
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Return same PdoDriver instance to perform singletone
     *
     * @param string $host    Host Name
     * @param string $user    User Name
     * @param string $pass    Password
     * @param string $db      Database Name
     * @param array  $configs Other Configs
     *
     * @return PdoDriver
     */
    public static function getInstance(
        string $host,
        string $user,
        string $pass,
        string $db,
        array $configs = []
    ) {
        self::$instance = self::$instance
            ?? new self($host, $user, $pass, $db, $configs);

        return self::$instance;
    }

    /**
     * Executes the query
     *
     * @return bool
     */
    public function executeQuery(): bool
    {
        $stmt = $this->getStmt();

        return $this->run($stmt);
    }

    protected function getStmt($query = null, $bindValues = null)
    {
        $query = $query ?? $this->query;
        $bindValues = $bindValues ?? $this->bindValues;
        $stmt = $this->con->prepare($query);
        $index = 1;
        foreach ((array) $bindValues as $bindValue) {
            $paramType = gettype($bindValue) == 'integer'
                ? PDO::PARAM_INT
                : PDO::PARAM_STR;
            $stmt->bindValue($index, $bindValue, $paramType);
            $index++;
        }

        return $stmt;
    }

    /**
     * Fetch the records
     *
     * @return mixed
     */
    public function fetch()
    {
        return $this->result != null ? $this->result->fetch(PDO::FETCH_OBJ) : null;
    }

    /**
     * Directly run the passed query value
     *
     * @param string $sql        Query
     * @param array  $bindValues Values to be bind
     *
     * @return bool
     */
    public function runQuery(string $sql, array $bindValues = []): bool
    {
        $stmt = $this->getStmt($sql, $bindValues);

        return $this->run($stmt);
    }

    /**
     * Run the given query stament.
     *
     * @param \mysqli_stmt|\PDOStatement $stmt
     *
     * @return bool
     */
    private function run($stmt)
    {
        $flag = false;
        try {
            $flag = $stmt->execute();
            if ($flag) {
                $this->result = $stmt;
            }
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), DatabaseException::DATABASE_QUERY_ERROR, $e, [
                'sql' => $this->query,
                'bind values' => $this->bindValues
            ]);
        }

        return $flag;
    }

    /**
     * Close the Database Connection
     *
     * @return void
     */
    public function close()
    {
        $this->con = null;
    }

    /**
     * Returns the last insert Id
     *
     * @return int
     */
    public function insertId(): int
    {
        return $this->con->lastInsertId();
    }

    /**
     * Begin the transaction
     *
     * @return bool
     */
    public function begin(): bool
    {
        return $this->con->beginTransaction();
    }

    public function escape(string $value): string
    {
        return (string) $this->con->quote($value);
    }
}
