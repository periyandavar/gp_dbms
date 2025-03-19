<?php

namespace Database;

use Exception;

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
            $driver = explode('/', $config['driver']);
            $driverclass = 'Database\\Driver\\' . ucfirst($driver[0]) . 'Driver';
            if (!class_exists($driverclass)) {
                throw new Exception('Invalid Driver');
            }
            $driver = $driver[1] ?? '';
            self::$_db = $driverclass::getInstance(
                $config['host'],
                $config['user'],
                $config['password'],
                $config['database'],
                $driver
            );

            return self::$_db;
        } catch (Exception $exception) {
        }

        return null;
    }
}
