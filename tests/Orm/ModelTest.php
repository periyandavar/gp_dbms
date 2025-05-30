<?php

use Database\Driver\PdoDriver;
use Database\Orm\Record;
use Loader\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class TestModel extends Record
{
    public static function getTableName()
    {
        return 'test_table';
    }

    public static function getUniqueKey()
    {
        return 'id';
    }

    public function rules()
    {
        return [
            ['id', ['required', 'numeric'], ['id' => ['required' => 'value not found']]]
        ];
    }
}

class ModelTest extends TestCase
{
    private $mockDb;

    protected function setUp(): void
    {
        // Mock the DBQuery instance
        $mockDbQuery = m::mock('Database\DBQuery')->makePartial();
        $mockDbQuery->shouldReceive('getSQL')->andReturn('SELECT * FROM test_table');
        $mockDbQuery->shouldReceive('getBindValues')->andReturn([]);
        $mockDbQuery->shouldReceive('reset')->andReturn(true);

        // Mock the database instance using Mockery
        $this->mockDb = m::mock(PdoDriver::class)->makePartial();
        $this->mockDb->shouldReceive('reset')->andReturn(true);
        $this->mockDb->shouldReceive('execute')->andReturn(true);
        $this->mockDb->shouldReceive('update')->andReturnSelf();
        $this->mockDb->shouldReceive('executeQuery')->andReturn(true);
        $this->mockDb->shouldReceive('insert')->andReturnSelf();
        $this->mockDb->shouldReceive('insertId')->andReturn(1);
        $this->mockDb->setDbQuery($mockDbQuery);
        $this->mockDb->shouldReceive('delete')->andReturnSelf();
        $this->mockDb->shouldReceive('getOne')->andReturn((object) ['id' => 1, 'name' => 'Test']);
        $this->mockDb->shouldReceive('getAll')->andReturn([
            (object) ['id' => 1, 'name' => 'Test1'],
            (object) ['id' => 2, 'name' => 'Test2'],
        ]);

        // Mock the container to return the mocked database
        Container::set('db', $this->mockDb);
    }

    protected function tearDown(): void
    {
        // Close Mockery
        m::close();
    }

    public function testSaveInsert()
    {
        $model = new TestModel();
        $model->name = 'Test';
        $result = $model->save();

        $this->assertTrue($result);
    }

    public function testSaveUpdate()
    {
        $model = new TestModel();
        $model->id = 1;
        $model->name = 'Updated Test';
        $model->original_state = ['id' => 1, 'name' => 'Test'];

        $result = $model->save();

        $this->assertTrue($result);
    }

    public function testDelete()
    {
        $model = new TestModel();
        $model->id = 1;

        $result = $model->delete();

        $this->assertTrue($result);
    }

    public function testFind()
    {
        $model = TestModel::find(1);

        $this->assertInstanceOf(TestModel::class, $model);
        $this->assertEquals(1, $model->id);
        $this->assertEquals('Test', $model->name);
    }

    public function testFindAll()
    {
        $models = TestModel::findAll();

        $this->assertCount(2, $models);
        $this->assertInstanceOf(TestModel::class, $models[0]);
        $this->assertEquals('Test1', $models[0]->name);
        $this->assertEquals('Test2', $models[1]->name);
    }

    public function testValidate()
    {
        $model = new TestModel();
        $result = $model->validate();

        $this->assertTrue($result);
    }
}
