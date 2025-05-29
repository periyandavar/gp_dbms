<?php

use Database\Database;
use Database\DBQuery;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private $mockDatabase;

    protected function setUp(): void
    {
        // Create a mock for the abstract Database class
        $this->mockDatabase = $this->getMockForAbstractClass(Database::class);

        // Mock the abstract methods
        $this->mockDatabase->method('runQuery')->willReturn(true);
        $this->mockDatabase->method('executeQuery')->willReturn(true);
        $this->mockDatabase->method('insertId')->willReturn(1);
        $this->mockDatabase->method('escape')->willReturnCallback(function($value) {
            return addslashes($value);
        });
    }

    public function testQuery()
    {
        $result = $this->mockDatabase->query('SELECT * FROM users WHERE id = ?', [1]);
        $this->assertTrue($result);
    }

    public function testExecute()
    {
        $mockDbQuery = $this->createMock(DBQuery::class);
        $mockDbQuery->method('getSQL')->willReturn('SELECT * FROM users');
        $mockDbQuery->method('getBindValues')->willReturn([]);
        $mockDbQuery->expects($this->once())->method('reset');

        $this->mockDatabase->setDbQuery($mockDbQuery);

        $result = $this->mockDatabase->execute();
        $this->assertTrue($result);
    }

    public function testSet()
    {
        $result = $this->mockDatabase->set('autocommit', '0');
        $this->assertTrue($result);
    }

    public function testTransactionMethods()
    {
        $this->assertTrue($this->mockDatabase->begin());
        $this->assertTrue($this->mockDatabase->commit());
        $this->assertTrue($this->mockDatabase->rollback());
    }

    public function testGetOne()
    {
        $this->mockDatabase->method('fetch')->willReturn((object) ['id' => 1, 'name' => 'Test']);
        $result = $this->mockDatabase->getOne();
        $this->assertIsObject($result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test', $result->name);
    }

    public function testGetAll()
    {
        $this->mockDatabase->method('fetch')->willReturnOnConsecutiveCalls(
            (object) ['id' => 1, 'name' => 'Test1'],
            (object) ['id' => 2, 'name' => 'Test2'],
            false
        );

        // $mockDatabase = Mockery::mock(Database::class)->makePartial();
        // // $mockDatabase->shouldReceive('fetch')->andReturnValues([(object) ['id' => 1, 'name' => 'Test1'],
        // (object) ['id' => 2, 'name' => 'Test2'],
        // false]);

        $result = $this->mockDatabase->getAll();
        $this->assertCount(2, $result);
        $this->assertEquals('Test1', $result[0]->name);
        // $this->assertEquals('Test2', $result[1]->name);
    }

    public function testEscape()
    {
        $escapedValue = $this->mockDatabase->escape("O'Reilly");
        $this->assertEquals("O\\'Reilly", $escapedValue);
    }
}
