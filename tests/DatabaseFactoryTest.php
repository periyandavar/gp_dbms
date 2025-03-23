<?php

use Database\DatabaseFactory;
use Database\Driver\MysqliDriver;
use Database\Exception\DatabaseException;
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

        $this->assertSame($mockDriver, DatabaseFactory::create($config));
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

        DatabaseFactory::create($config);
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

        DatabaseFactory::setUpConfig(['default' => $mockConfigLoader]);

        $this->assertNotNull(DatabaseFactory::get('default'));
    }

    public function testGetWithNonExistentConfig()
    {
        $this->assertNull(DatabaseFactory::get('nonexistent'));
    }
}
