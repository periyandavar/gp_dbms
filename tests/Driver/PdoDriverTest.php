<?php

use Database\Driver\PdoDriver;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class PdoDriverTest extends TestCase
{
    private $mockPdo;
    private $mockStmt;

    protected function setUp(): void
    {
        // Mock the PDOStatement
        $this->mockStmt = m::mock(PDOStatement::class);
        $this->mockStmt->shouldReceive('execute')->andReturn(true);
        $this->mockStmt->shouldReceive('fetch')->andReturn((object) ['id' => 1, 'name' => 'Test']);
        $this->mockStmt->shouldReceive('fetchAll')->andReturn([
            (object) ['id' => 1, 'name' => 'Test1'],
            (object) ['id' => 2, 'name' => 'Test2'],
        ]);
        $this->mockStmt->shouldReceive('bindValue')->andReturn(true);

        // Mock the PDO object
        $this->mockPdo = m::mock(PDO::class);
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt);
        $this->mockPdo->shouldReceive('setAttribute')->andReturn(true);
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn(1);
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true);
        $this->mockPdo->shouldReceive('quote')->andReturnUsing(function($value) {
            return "'$value'";
        });
    }

    protected function tearDown(): void
    {
        // Close Mockery
        m::close();
    }

    public function testExecuteQuery()
    {
        // Create a partial mock of PdoDriver
        $driver = $this->getMockBuilder(PdoDriver::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();

        // Inject the mocked PDO connection
        $reflection = new ReflectionClass(PdoDriver::class);
        $conProperty = $reflection->getProperty('con');
        $conProperty->setAccessible(true);
        $conProperty->setValue($driver, $this->mockPdo);

        // Set query and bind values
        $driver->setquery('SELECT * FROM users WHERE id = ?', [1]);

        // Execute the query
        $result = $driver->executeQuery();

        // Assertions
        $this->assertTrue($result);
    }

    public function testRunQuery()
    {
        // Create a partial mock of PdoDriver
        $driver = $this->getMockBuilder(PdoDriver::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();

        // Inject the mocked PDO connection
        $reflection = new ReflectionClass(PdoDriver::class);
        $conProperty = $reflection->getProperty('con');
        $conProperty->setAccessible(true);
        $conProperty->setValue($driver, $this->mockPdo);

        // Run a query
        $result = $driver->runQuery('SELECT * FROM users WHERE id = ?', [1]);

        // Assertions
        $this->assertTrue($result);
    }
}
