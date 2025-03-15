<?php
/**
 * DatabaseFactory
 * php version 7.3.5
 *
 * @category DatabaseFactory
 * @package  Database
 * @author   Periyandavar <periyandavar@gmail.com>
 * @license  http://license.com license
 * @link     http://url.com
 */

namespace Database;

use Exception;

/**
 * Creates the instance of the database based on the DbConfig
 *
 * @category DatabaseFactory
 * @package  Database
 * @author   Periyandavar <periyandavar@gmail.com>
 * @license  http://license.com license
 * @link     http://url.com
 */
class DatabaseFactory
{
    private static $_db;

    /**
     * Creates and returns Database instance
     *
     * @return Database|null
     */
    public static function create($config)
    {
        if (isset(self::$_db)) {
            return self::$_db;
        }
        try {
            $driver = explode("/", $config['driver']);
            $driverclass = "Database\\" . $driver[0] . 'Driver';
            if (!class_exists($driverclass)) {
                throw new Exception("Invalid Driver");
            }
            $driver = isset($driver[1]) ? $driver[1] : '';
            self::$_db = $driverclass::getInstance(
                $config['host'],
                $config['user'],
                $config['password'],
                $config['database'],
                $driver
            );
            return self::$_db;
        } catch (Exception $exception) {
            //
        }

        return null;
    }
}
