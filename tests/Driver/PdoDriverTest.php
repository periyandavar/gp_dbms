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

    public function testGetStmtBindsParametersCorrectly()
    {
        $mockStmt = $this->getMockBuilder(PDOStatement::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['bindValue'])
        ->getMock();

        $mockStmt->expects($this->exactly(3))
            ->method('bindValue')
            ->withConsecutive(
                [1, 42, PDO::PARAM_INT],
                [2, 'foo', PDO::PARAM_STR],
                [3, 3.14, PDO::PARAM_STR]
            );

        $mockPdo = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepare'])
            ->getMock();

        $mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM test WHERE a=? AND b=? AND c=?')
            ->willReturn($mockStmt);

        $ref = new ReflectionClass(PdoDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $refCon = new ReflectionProperty(PdoDriver::class, 'con');
        $refCon->setAccessible(true);
        $refCon->setValue($driver, $mockPdo);

        // Use reflection to call protected getStmt
        $refMethod = $ref->getMethod('getStmt');
        $refMethod->setAccessible(true);
        $stmt = $refMethod->invoke($driver, 'SELECT * FROM test WHERE a=? AND b=? AND c=?', [42, 'foo', 3.14]);

        $this->assertNotNull($stmt, 'getStmt() returned null');
        $this->assertSame($mockStmt, $stmt);
    }

    public function testInsertIdReturnsValue()
    {
        $mockPdo = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['lastInsertId'])
            ->getMock();
        $mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('123'); // Return string

        $ref = new ReflectionClass(PdoDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $refCon = new ReflectionProperty(PdoDriver::class, 'con');
        $refCon->setAccessible(true);
        $refCon->setValue($driver, $mockPdo);

        $this->assertEquals('123', $driver->insertId());
    }

    public function testBeginReturnsTrue()
    {
        $mockPdo = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['beginTransaction'])
            ->getMock();
        $mockPdo->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $ref = new ReflectionClass(PdoDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $refCon = new ReflectionProperty(PdoDriver::class, 'con');
        $refCon->setAccessible(true);
        $refCon->setValue($driver, $mockPdo);

        $this->assertTrue($driver->begin());
    }

    public function testEscapeReturnsEscapedString()
    {
        $mockPdo = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['quote'])
            ->getMock();
        $mockPdo->expects($this->once())
            ->method('quote')
            ->with('foo')
            ->willReturn("'foo'");

        $ref = new ReflectionClass(PdoDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $refCon = new ReflectionProperty(PdoDriver::class, 'con');
        $refCon->setAccessible(true);
        $refCon->setValue($driver, $mockPdo);

        $this->assertEquals("'foo'", $driver->escape('foo'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetInstanceReturnsSingleton()
    {
        // Reset singleton instance
        $ref = new ReflectionClass(PdoDriver::class);
        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue(null, null);

        // Create a mock instance and set as singleton
        $mock = $ref->newInstanceWithoutConstructor();
        $instanceProp->setValue(null, $mock);

        $this->assertSame($mock, PdoDriver::getInstance('host', 'user', 'pass', 'db'));
    }

    public function testRunReturnsTrueAndSetsResult()
    {
        $mockStmt = $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->getMock();
        $mockStmt->expects($this->once())->method('execute')->willReturn(true);

        $ref = new ReflectionClass(PdoDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $flag = $ref->getMethod('run')->invoke($driver, $mockStmt);

        $this->assertTrue($flag);

        $refResult = new ReflectionProperty(PdoDriver::class, 'result');
        $refResult->setAccessible(true);
        $this->assertSame($mockStmt, $refResult->getValue($driver));
    }

    public function testRunThrowsDatabaseException()
    {
        $mockStmt = $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->getMock();
        $mockStmt->expects($this->once())
            ->method('execute')
            ->will($this->throwException(new PDOException('fail')));

        $ref = new ReflectionClass(PdoDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $this->expectException(Database\Exception\DatabaseException::class);
        $ref->getMethod('run')->invoke($driver, $mockStmt);
    }

    public function testFetchReturnsObjectOrNull()
    {
        $mockResult = $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetch'])
            ->getMock();
        $mockResult->expects($this->once())
            ->method('fetch')
            ->willReturn((object) ['foo' => 'bar']);

        $ref = new ReflectionClass(PdoDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $refResult = new ReflectionProperty(PdoDriver::class, 'result');
        $refResult->setAccessible(true);
        $refResult->setValue($driver, $mockResult);

        $this->assertEquals((object) ['foo' => 'bar'], $driver->fetch());

        // Test null case
        $refResult->setValue($driver, null);
        $this->assertNull($driver->fetch());
    }

    public function testCloseSetsConToNull()
    {
        $ref = new ReflectionClass(PdoDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $refCon = new ReflectionProperty(PdoDriver::class, 'con');
        $refCon->setAccessible(true);
        $refCon->setValue($driver, 'not_null');

        $driver->close();

        $this->assertNull($refCon->getValue($driver));
    }
}
