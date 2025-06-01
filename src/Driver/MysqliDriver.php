<?php

namespace Database\Driver;

use Database\Database;
use Database\Exception\DatabaseException;
use mysqli;
use Mysqli_sql_exception;
use Throwable;

class MysqliDriver extends Database
{
    /**
     * Instantiate a MysqliDriver instance
     *
     * @param string $host Host
     * @param string $user Username
     * @param string $pass Password
     * @param string $db   Database Name
     */
    private function __construct(string $host, string $user, string $pass, string $db)
    {
        try {
            parent::__construct();
            $this->con = new mysqli($host, $user, $pass, $db);
        } catch (Mysqli_sql_exception $e) {
            throw new DatabaseException($e->getMessage(), DatabaseException::DATABASE_CONNECTION_ERROR, $e);
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
     * Returns the same instance of the MysqliDriver to performs Singleton
     *
     * @param string $host    Host
     * @param string $user    UserName
     * @param string $pass    Password
     * @param string $db      DatabaseName
     * @param array  $configs Other Configs
     *
     * @return MysqliDriver
     */
    public static function getInstance(
        string $host,
        string $user,
        string $pass,
        string $db,
        array $configs = []
    ): MysqliDriver {
        self::$instance = self::$instance ?? new self($host, $user, $pass, $db);

        return self::$instance;
    }

    /**
     * Executes the query
     *
     * @return bool
     */
    public function executeQuery(): bool
    {
        return $this->run($this->getStmt());
    }

    public function run($stmt)
    {
        try {
            $flag = $stmt->execute();
            if ($flag) {
                $result = $stmt->get_result();
                $this->result = (! $result) ? null : $this->result = $result;
            }
        } catch (Throwable $e) {
            throw new DatabaseException($e->getMessage(), DatabaseException::DATABASE_QUERY_ERROR, $e, [
                'sql' => $this->query,
                'bind values' => $this->bindValues
            ]);
        }

        return $flag;
    }

    protected function getStmt($query = null, $bindValues = null)
    {
        $query = $query ?? $this->query;
        $bindValues = $bindValues ?? $this->bindValues;
        $stmt = $this->con->prepare($query);
        $paramType = '';
        foreach ($bindValues as $bindValue) {
            switch (gettype($bindValue)) {
                case 'integer':
                    $paramType .= 'i';
                    break;
                case 'double':
                    $paramType .= 'd';
                    break;
                default:
                    $paramType .= 's';
                    break;
            }
        }

        if (count($bindValues) != 0) {
            $stmt->bind_param($paramType, ...$bindValues);
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
        return $this->result != null ? $this->result->fetch_object() : null;
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
        return $this->run($this->getStmt($sql, $bindValues));
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
        return $this->con->insert_id;
    }

    /**
     * Begin the transaction
     *
     * @return bool
     */
    public function begin(): bool
    {
        return $this->con->begin_transaction();
    }

    public function escape(string $value): string
    {
        return (string) $this->con->real_escape_string($value);
    }
}
