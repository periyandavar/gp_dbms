<?php
/**
 * MysqliDriver
 * php version 7.3.5
 *
 * @category   Database
 * @package    Database
 * @subpackage DatabaseDriver
 * @author     Periyandavar <periyandavar@gmail.com>
 * @license    http://license.com license
 * @link       http://url.com
 */

namespace Database\Driver;

use Database\Database;
use mysqli;
use Mysqli_sql_exception;
use Error;

defined('VALID_REQ') or exit('Invalid request');
/**
 * MysqliDriver Class performs database operations with mysqli connection
 *
 * @category   Database
 * @package    Database
 * @subpackage DatabaseDriver
 * @author     Periyandavar <periyandavar@gmail.com>
 * @license    http://license.com license
 * @link       http://url.com
 */
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
            die();
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
     * @param string $host   Host
     * @param string $user   UserName
     * @param string $pass   Password
     * @param string $db     DatabaseName
     * @param string $driver DriverName
     *
     * @return MysqliDriver
     */
    public static function getInstance(
        string $host,
        string $user,
        string $pass,
        string $db,
        string $driver
    ): MysqliDriver {
        self::$instance = self::$instance ?? new static($host, $user, $pass, $db);
        return self::$instance;
    }

    /**
     * Executes the query
     *
     * @return bool
     */
    public function executeQuery(): bool
    {
        $flag = false;
        try {
            $stmt = $this->con->prepare($this->query);
            $paramType = "";
            if (is_array($this->bindValues)) {
                foreach ($this->bindValues as $bindValue) {
                    switch (gettype($bindValue)) {
                    case 'integer':
                        $paramType .= "i";
                        break;
                    case 'double':
                        $paramType .= "d";
                        break;
                    default:
                        $paramType .= "s";
                        break;
                    }
                }
                $stmt->bind_param($paramType, ...$this->bindValues);
            }
            $flag = $stmt->execute();
            if ($flag) {
                $result = $stmt->get_result();
                $this->result = ($result == false) ? null : $this->result = $result;
            }
        } catch (Mysqli_sql_exception $e) {
            // Log::getInstance()->error(
            //     "Exception: ".$e->getMessage(),
            //     [
            //         "sql" => $this->query,
            //         "bind values" => $this->bindValues
            //     ]
            // );
        } catch (Error $e) {
            // Log::getInstance()->error(
            //     "Exception: ".$e->getMessage(),
            //     [
            //         "sql" => $this->query,
            //         "bind values" => $this->bindValues
            //     ]
            // );
        }
        return $flag;
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
    public function runQuery(string $sql, array $bindValues=[]): bool
    {
        $flag = false;
        try {
            $stmt = $this->con->prepare($sql);
            $paramType = "";
            foreach ($bindValues as $bindValue) {
                switch (gettype($bindValue)) {
                case 'integer':
                    $paramType .= "i";
                    break;
                case 'double':
                    $paramType .= "d";
                    break;
                default:
                    $paramType .= "s";
                    break;
                }
            }
            if (count($bindValues) != 0) {
                $stmt->bind_param($paramType, ...$bindValues);
            }
            $flag = $stmt->execute();
            if ($flag == true) {
                $result = $stmt->get_result();
                $this->result = ($result == false) ? null : $this->result = $result;
            }
        } catch (Mysqli_sql_exception $e) {
            // Log::getInstance()->error(
            //     "Exception: ".$e->getMessage(),
            //     [
            //         "sql" => $this->query,
            //         "bind values" => $this->bindValues
            //     ]
            // );
        } catch (Error $e) {
            // Log::getInstance()->error(
            //     "Error: ".$e->getMessage(),
            //     [
            //         "sql" => $this->query,
            //         "bind values" => $this->bindValues
            //     ]
            // );
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
        if (is_resource($this->con)
            && get_resource_type($this->con)==='mysql link'
        ) {
            $this->con->close();
        }
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
