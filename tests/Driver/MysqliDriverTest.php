<?php

use Database\Driver\MysqliDriver;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class MysqliDriverTest extends TestCase
{
    private $mockMysqli;
    private $mockStmt;

    protected function setUp(): void
    {
        // Mock the mysqli_result object
        $mockResult = m::mock(mysqli_result::class);
        $mockResult->shouldReceive('fetch_object')->andReturn((object) ['id' => 1, 'name' => 'Test']);
        $mockResult->shouldReceive('fetch_all')->andReturn([
            (object) ['id' => 1, 'name' => 'Test1'],
            (object) ['id' => 2, 'name' => 'Test2'],
        ]);
        $mockResult->shouldReceive('free')->andReturn(true);

        // Mock the mysqli_stmt object
        $this->mockStmt = m::mock(mysqli_stmt::class);
        $this->mockStmt->shouldReceive('bind_param')->andReturn(true);
        $this->mockStmt->shouldReceive('execute')->andReturn(true);
        $this->mockStmt->shouldReceive('get_result')->andReturn($mockResult); // Return the mocked mysqli_result
        $this->mockStmt->shouldReceive('fetch_object')->andReturn((object) ['id' => 1, 'name' => 'Test']); // Mock fetch_object
        $this->mockStmt->shouldReceive('close')->andReturn(true);

        // Mock the mysqli object
        $this->mockMysqli = m::mock(mysqli::class);
        $this->mockMysqli->shouldReceive('prepare')->andReturn($this->mockStmt);
        $this->mockMysqli->shouldReceive('real_escape_string')->andReturnUsing(function($value) {
            return addslashes($value);
        });
        $this->mockMysqli->shouldReceive('insert_id')->andReturn(1);
        $this->mockMysqli->shouldReceive('begin_transaction')->andReturn(true);
    }

    protected function tearDown(): void
    {
        // Close Mockery
        m::close();
    }

    public function testExecuteQuery()
    {
        // Create a partial mock of MysqliDriver
        $driver = $this->getMockBuilder(MysqliDriver::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();

        // Inject the mocked mysqli connection
        $reflection = new ReflectionClass(MysqliDriver::class);
        $conProperty = $reflection->getProperty('con');
        $conProperty->setAccessible(true);
        $conProperty->setValue($driver, $this->mockMysqli);

        // Set query and bind values
        $driver->setQuery('SELECT * FROM users WHERE id = ?', [1]);

        // Execute the query
        $result = $driver->executeQuery();

        // Assertions
        $this->assertTrue($result);
    }

    public function testFetch()
    {
        // Create a partial mock of MysqliDriver
        $driver = $this->getMockBuilder(MysqliDriver::class)
        ->disableOriginalConstructor()
        ->setMethods(['getConnection'])
        ->getMock();

        // Inject the mocked mysqli connection
        $reflection = new ReflectionClass(MysqliDriver::class);
        $conProperty = $reflection->getProperty('con');
        $conProperty->setAccessible(true);
        $conProperty->setValue($driver, $this->mockMysqli);

        // Use reflection to set the protected 'result' property
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($driver, $this->mockStmt);

        // Fetch the result
        $result = $driver->fetch();

        // Assertions
        $this->assertIsObject($result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test', $result->name);
    }

    public function testRunQuery()
    {
        // Create a partial mock of MysqliDriver
        $driver = $this->getMockBuilder(MysqliDriver::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();

        // Inject the mocked mysqli connection
        $reflection = new ReflectionClass(MysqliDriver::class);
        $conProperty = $reflection->getProperty('con');
        $conProperty->setAccessible(true);
        $conProperty->setValue($driver, $this->mockMysqli);

        // Run a query
        $result = $driver->runQuery('SELECT * FROM users WHERE id = ?', [1]);

        // Assertions
        $this->assertTrue($result);
    }

    public function testEscape()
    {
        // Create a partial mock of MysqliDriver
        $driver = $this->getMockBuilder(MysqliDriver::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();

        // Inject the mocked mysqli connection
        $reflection = new ReflectionClass(MysqliDriver::class);
        $conProperty = $reflection->getProperty('con');
        $conProperty->setAccessible(true);
        $conProperty->setValue($driver, $this->mockMysqli);

        // Escape a value
        $escapedValue = $driver->escape("O'Reilly");

        // Assertions
        $this->assertEquals("O\\'Reilly", $escapedValue);
    }
}
