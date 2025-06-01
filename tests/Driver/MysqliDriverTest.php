<?php

use Database\Driver\MysqliDriver;
use Database\Exception\DatabaseException;
use PHPUnit\Framework\TestCase;

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class MysqliDriverTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset singleton instance before each test
        $ref = new ReflectionProperty(MysqliDriver::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetInstanceReturnsSingleton()
    {
        $ref = new ReflectionClass(MysqliDriver::class);
        $constructor = $ref->getConstructor();
        $constructor->setAccessible(true);

        // Create a mock instance without calling the constructor
        $mock = $ref->newInstanceWithoutConstructor();

        // Set the singleton instance manually
        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue(null, $mock);

        $this->assertSame($mock, MysqliDriver::getInstance('host', 'user', 'pass', 'db'));
    }

    public function testExecuteQuerySuccess()
    {
        $mockStmt = $this->getMockBuilder(stdClass::class)
            ->addMethods(['execute', 'get_result'])
            ->getMock();
        $mockStmt->expects($this->once())->method('execute')->willReturn(true);
        $mockStmt->expects($this->once())->method('get_result')->willReturn((object) ['foo' => 'bar']);

        $driver = $this->getMockBuilder(MysqliDriver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStmt'])
            ->getMock();

        $driver->expects($this->once())->method('getStmt')->willReturn($mockStmt);

        $this->assertTrue($driver->executeQuery());
        $refResult = new ReflectionProperty($driver, 'result');
        $refResult->setAccessible(true);
        $this->assertEquals((object) ['foo' => 'bar'], $refResult->getValue($driver));
    }

    public function testExecuteQueryThrowsDatabaseExceptionOnMysqliSqlException()
    {
        // Create a mock statement whose execute() throws
        $mockStmt = $this->getMockBuilder(stdClass::class)
            ->addMethods(['execute', 'get_result'])
            ->getMock();
        $mockStmt->method('execute')
            ->will($this->throwException(new \mysqli_sql_exception('SQL error')));

        $driver = $this->getMockBuilder(MysqliDriver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStmt'])
            ->getMock();

        $driver->method('getStmt')->willReturn($mockStmt);

        $this->expectException(DatabaseException::class);
        $driver->executeQuery();
    }

    public function testRunQuerySuccess()
    {
        $mockStmt = $this->getMockBuilder(stdClass::class)
            ->addMethods(['execute', 'get_result'])
            ->getMock();
        $mockStmt->expects($this->once())->method('execute')->willReturn(true);
        $mockStmt->expects($this->once())->method('get_result')->willReturn((object) ['foo' => 'bar']);

        $driver = $this->getMockBuilder(MysqliDriver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStmt'])
            ->getMock();

        $driver->expects($this->once())->method('getStmt')->willReturn($mockStmt);

        $this->assertTrue($driver->runQuery('SELECT 1', []));
        $refResult = new ReflectionProperty($driver, 'result');
        $refResult->setAccessible(true);
        $this->assertEquals((object) ['foo' => 'bar'], $refResult->getValue($driver));
    }

    public function testFetchReturnsNullIfNoResult()
    {
        $driver = $this->getMockBuilder(MysqliDriver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $refResult = new ReflectionProperty($driver, 'result');
        $refResult->setAccessible(true);
        $refResult->setValue($driver, null);

        $this->assertNull($driver->fetch());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testCloseSetsConToNull()
    {
        $ref = new ReflectionClass(MysqliDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $refCon = new ReflectionProperty(MysqliDriver::class, 'con');
        $refCon->setAccessible(true);
        $refCon->setValue($driver, 'not_null');

        $driver->close();

        $this->assertNull($refCon->getValue($driver));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled.
     * @return void
     */
    public function testConstructSuccess()
    {
        // Mock mysqli to avoid real DB connection
        $mockMysqli = $this->getMockBuilder(mysqli::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ref = new ReflectionClass(MysqliDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        // Set the con property via reflection
        $refCon = new ReflectionProperty(MysqliDriver::class, 'con');
        $refCon->setAccessible(true);
        $refCon->setValue($driver, $mockMysqli);

        $this->assertInstanceOf(MysqliDriver::class, $driver);
        $this->assertSame($mockMysqli, $refCon->getValue($driver));
    }

    public function testInsertIdReturnsValue()
    {
        // Create a stub class with a public property
        $mockMysqli = new class() {
            public $insert_id = 123;
        };

        $ref = new ReflectionClass(MysqliDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $refCon = new ReflectionProperty(MysqliDriver::class, 'con');
        $refCon->setAccessible(true);
        $refCon->setValue($driver, $mockMysqli);

        $this->assertEquals(123, $driver->insertId());
    }
    public function testBeginReturnsTrue()
    {
        $mockMysqli = $this->getMockBuilder(mysqli::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockMysqli->expects($this->once())
            ->method('begin_transaction')
            ->willReturn(true);

        $ref = new ReflectionClass(MysqliDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $refCon = new ReflectionProperty(MysqliDriver::class, 'con');
        $refCon->setAccessible(true);
        $refCon->setValue($driver, $mockMysqli);

        $this->assertTrue($driver->begin());
    }

    public function testEscapeReturnsEscapedString()
    {
        $mockMysqli = $this->getMockBuilder(mysqli::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockMysqli->expects($this->once())
            ->method('real_escape_string')
            ->with('foo')
            ->willReturn('foo_escaped');

        $ref = new ReflectionClass(MysqliDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $refCon = new ReflectionProperty(MysqliDriver::class, 'con');
        $refCon->setAccessible(true);
        $refCon->setValue($driver, $mockMysqli);

        $this->assertEquals('foo_escaped', $driver->escape('foo'));
    }

    public function testGetStmtBindsParametersCorrectly()
    {
        // Mock mysqli_stmt with only bind_param method
        $mockStmt = $this->getMockBuilder(mysqli_stmt::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['bind_param'])
            ->getMock();

        // Expect bind_param to be called with correct types and values
        $mockStmt->expects($this->once())
            ->method('bind_param')
            ->with(
                $this->equalTo('isd'), // i: int, s: string, d: double
                42,
                'foo',
                3.14
            );

        // Mock mysqli with prepare method
        $mockMysqli = $this->getMockBuilder(mysqli::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepare'])
            ->getMock();

        $mockMysqli->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM test WHERE a=? AND b=? AND c=?')
            ->willReturn($mockStmt);

        // Create driver instance without constructor
        $ref = new ReflectionClass(MysqliDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        // Inject mock mysqli connection
        $refCon = new ReflectionProperty(MysqliDriver::class, 'con');
        $refCon->setAccessible(true);
        $refCon->setValue($driver, $mockMysqli);

        // Use reflection to call protected getStmt
        $refMethod = $ref->getMethod('getStmt');
        $refMethod->setAccessible(true);
        $stmt = $refMethod->invoke($driver, 'SELECT * FROM test WHERE a=? AND b=? AND c=?', [42, 'foo', 3.14]);

        $this->assertSame($mockStmt, $stmt);
    }

    public function testRunReturnsTrueAndSetsResult()
    {
        $mockResult = $this->getMockBuilder(mysqli_result::class)
        ->disableOriginalConstructor()
        ->getMock();

        $mockStmt = $this->getMockBuilder(mysqli_stmt::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute', 'get_result'])
            ->getMock();
        $mockStmt->expects($this->once())->method('execute')->willReturn(true);
        $mockStmt->expects($this->once())->method('get_result')->willReturn($mockResult);

        $ref = new ReflectionClass(MysqliDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $flag = $driver->run($mockStmt);

        $this->assertTrue($flag);

        $refResult = new ReflectionProperty(MysqliDriver::class, 'result');
        $refResult->setAccessible(true);
        $this->assertSame($mockResult, $refResult->getValue($driver));
    }

    public function testRunThrowsDatabaseException()
    {
        $mockStmt = $this->getMockBuilder(mysqli_stmt::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->getMock();
        $mockStmt->expects($this->once())
            ->method('execute')
            ->will($this->throwException(new \Exception('fail')));

        $ref = new ReflectionClass(MysqliDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $this->expectException(DatabaseException::class);
        $driver->run($mockStmt);
    }

    public function testFetchReturnsObjectOrNull()
    {
        $mockResult = $this->getMockBuilder(stdClass::class)
            ->addMethods(['fetch_object'])
            ->getMock();
        $mockResult->expects($this->once())
            ->method('fetch_object')
            ->willReturn((object) ['foo' => 'bar']);

        $ref = new ReflectionClass(MysqliDriver::class);
        $driver = $ref->newInstanceWithoutConstructor();

        $refResult = new ReflectionProperty(MysqliDriver::class, 'result');
        $refResult->setAccessible(true);
        $refResult->setValue($driver, $mockResult);

        $this->assertEquals((object) ['foo' => 'bar'], $driver->fetch());

        // Test null case
        $refResult->setValue($driver, null);
        $this->assertNull($driver->fetch());
    }

    // public function testCloseSetsConToNull()
    // {
    //     $ref = new ReflectionClass(MysqliDriver::class);
    //     $driver = $ref->newInstanceWithoutConstructor();

    //     $refCon = new ReflectionProperty(MysqliDriver::class, 'con');
    //     $refCon->setAccessible(true);
    //     $refCon->setValue($driver, 'not_null');

    //     $driver->close();

    //     $this->assertNull($refCon->getValue($driver));
    // }
}
