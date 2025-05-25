<?php

namespace Database;

use Database\Exception\DatabaseException;
use Exception;
use Loader\Config\ConfigLoader;

class DatabaseFactory
{
    private static $config = [];

    private static $dbs = [];

    /**
     * Creates and returns Database instance
     *
     * @return Database|null
     */
    private static function create($config)
    {
        try {
            $driver = explode('/', $config['driver'] ?? 'Pdo/Mysql');
            $driverclass = 'Database\\Driver\\' . ucfirst($driver[0]) . 'Driver';
            if (!class_exists($driverclass)) {
                throw new DatabaseException("Driver class: {$driverclass} not found to create the db instance", DatabaseException::DRIVER_NOT_FOUND_ERROR, null, ['driver' => $driverclass]);
            }
            $db = $driverclass::getInstance(
                $config['host'],
                $config['user'],
                $config['password'],
                $config['database'],
                $driver
            );

            return $db;
        } catch (Exception $e) {
            if ($e instanceof DatabaseException) {
                throw $e;
            }
            throw new DatabaseException($e->getMessage(), DatabaseException::DATABASE_CONNECTION_ERROR, $e);
        }
    }

    /**
     * Set up config
     *
     * @param ConfigLoader[] $config
     */
    public static function setUpConfig(array $configs)
    {
        foreach ($configs as $name => $config) {
            self::$config[$name] = $config instanceof ConfigLoader ? $config->getAll() : $config;
        }

        if (!isset(self::$config['default']) && ! empty(self::$config)) {
            // Set the first config as default if no default is set
            self::$config['default'] = reset(self::$config);
        }
    }

    public static function get($name = 'default')
    {
        if (isset(self::$dbs[$name])) {
            return self::$dbs[$name];
        }

        if (isset(self::$config[$name])) {
            self::$dbs[$name] = self::create(self::$config[$name]);

            return self::$dbs[$name];
        }

        return null;
    }
}
