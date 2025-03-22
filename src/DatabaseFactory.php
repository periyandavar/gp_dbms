<?php

namespace Database;

use Database\Exception\DatabaseException;
use Exception;
use Loader\Config\ConfigLoader;

class DatabaseFactory
{
    private static $db;

    private static $config = [];

    private static $dbs = [];

    /**
     * Creates and returns Database instance
     *
     * @return Database|null
     */
    public static function create($config)
    {
        if (isset(self::$db)) {
            return self::$db;
        }
        try {
            $driver = explode('/', $config['driver'] ?? 'Pdo/Mysql');
            $driverclass = 'Database\\Driver\\' . ucfirst($driver[0]) . 'Driver';
            if (!class_exists($driverclass)) {
                throw new DatabaseException("Driver class: {$driverclass} not found to create the db instance", DatabaseException::DRIVER_NOT_FOUND_ERROR, null, ['driver' => $driverclass]);
            }
            self::$db = $driverclass::getInstance(
                $config['host'],
                $config['user'],
                $config['password'],
                $config['database'],
                $driver
            );

            return self::$db;
        } catch (Exception $e) {
            if ($e instanceof DatabaseException) {
                throw $e;
            }
            throw new DatabaseException($e->getMessage(), DatabaseException::DATABASE_QUERY_ERROR, $e);
        }
    }

    /**
     * Set up config
     *
     * @param ConfigLoader[] $config
     */
    public static function setUpConfig(array $config)
    {
        if (count($config) === 1 && isset($config[0])) {
            $config['default'] = $config[0]->getAll();

            return;
        }
        foreach ($config as $name => $value) {
            self::$config[$name] = $value->getAll();
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
