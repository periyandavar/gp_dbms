<?php

use Database\DatabaseFactory;
use Database\Driver\MysqliDriver;
use Database\Exception\DatabaseException;
use Loader\Config\ConfigLoader;
use PHPUnit\Framework\TestCase;

class DatabaseFactoryTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateWithValidDriver()
    {
        $config = [
            'driver' => 'Mysqli',
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
            'database' => 'test_db',
        ];
        $mockDriver = Mockery::mock('overload:' . MysqliDriver::class);
        $mockDriver->shouldReceive('getInstance')
            ->with(
                $config['host'],
                $config['user'],
                $config['password'],
                $config['database'],
                ['Mysqli']
            )
            ->andReturn($mockDriver);
        DatabaseFactory::setUpConfig([$config]);
        $this->assertSame($mockDriver, DatabaseFactory::get());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateWithError()
    {
        $config = [
            'driver' => 'Mysqli',
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
            'database' => 'test_db',
        ];
        $mockDriver = Mockery::mock('overload:' . MysqliDriver::class);
        $mockDriver->shouldReceive('getInstance')
            ->with(
                $config['host'],
                $config['user'],
                $config['password'],
                $config['database'],
                ['Mysqli']
            )
            ->andThrow(new Exception('Connection error'));
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Connection error');
        $this->expectExceptionCode(DatabaseException::DATABASE_CONNECTION_ERROR);
        DatabaseFactory::setUpConfig([$config]);
        DatabaseFactory::get();
    }

    public function testCreateWithInvalidDriver()
    {
        $this->expectException(DatabaseException::class);

        $config = [
            'driver' => 'InvalidDriver',
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
            'database' => 'test_db',
        ];

        DatabaseFactory::setUpConfig([$config]);
        DatabaseFactory::get();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetUpConfigAndGet()
    {
        $mockConfigLoader = $this->createMock(Loader\Config\ConfigLoader::class);
        $mockConfigLoader->method('getAll')->willReturn([
            'driver' => 'Mysqli',
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
            'database' => 'test_db',
        ]);
        $mockDriver = Mockery::mock('overload:' . MysqliDriver::class)->shouldReceive('getInstance')->andReturn(true);

        DatabaseFactory::setUpConfig(['default1' => $mockConfigLoader, 'other' => ConfigLoader::getInstance(ConfigLoader::VALUE_LOADER)]);

        $this->assertNotNull(DatabaseFactory::get('default'));
        $this->assertNotNull(DatabaseFactory::get('default'));
    }

    public function testGetWithNonExistentConfig()
    {
        $this->assertNull(DatabaseFactory::get('nonexistent'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetStoresAndReturnsDatabaseInstance()
    {
        $mockDb = $this->createMock(\Database\Database::class);
        DatabaseFactory::set('custom', $mockDb);
        $this->assertSame($mockDb, DatabaseFactory::get('custom'));
    }
}
