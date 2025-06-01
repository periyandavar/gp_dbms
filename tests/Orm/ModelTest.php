<?php

use Database\DBQuery;
use Database\Driver\PdoDriver;
use Database\Orm\Model;
use Database\Orm\Record;
use Loader\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Validator\Field\Field;
use Validator\Field\Fields;

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

    public function testDynamicPropertyAccess()
    {
        $model = $this->getMockForAbstractClass(\Database\Orm\Model::class);
        $model->foo = 'bar';
        $this->assertEquals('bar', $model->foo);
        $this->assertArrayHasKey('foo', $model->getValues());
    }

    public function testSetValuesSetsAttributes()
    {
        $model = $this->getMockForAbstractClass(\Database\Orm\Model::class);
        $model->setValues(['a' => 1, 'b' => 2]);
        $this->assertEquals(1, $model->a);
        $this->assertEquals(2, $model->b);
    }

    public function testGetValuesReturnsAttr()
    {
        $model = $this->getMockForAbstractClass(\Database\Orm\Model::class);
        $model->setValues(['x' => 10, 'y' => 20]);
        $values = $model->getValues();
        $this->assertEquals(['x' => 10, 'y' => 20], $values);
    }

    public function testGetErrorsAndGetErrorDelegation()
    {
        $fields = m::mock('Validator\Field\Fields');
        $fields->shouldReceive('getErrors')->andReturn(['foo' => 'bar']);
        $fields->shouldReceive('getError')->andReturn('first error');

        $model = $this->getMockForAbstractClass(\Database\Orm\Model::class);
        $reflection = new ReflectionClass($model);
        $prop = $reflection->getProperty('fields');
        $prop->setAccessible(true);
        $prop->setValue($model, $fields);

        $this->assertEquals(['foo' => 'bar'], $model->getErrors());
        $this->assertEquals('first error', $model->getError());
    }

    public function testGetRulesReturnsEmptyArray()
    {
        $model = $this->getMockForAbstractClass(\Database\Orm\Model::class);
        $this->assertEquals([], $model->getRules());
    }

    public function testJsonSerializeReturnsSerializedData()
    {
        $model = $this->getMockForAbstractClass(\Database\Orm\Model::class);
        $model->setValues(['a' => 1, 'b' => 2]);
        $result = $model->jsonSerialize();
        $this->assertEquals(['a' => 1, 'b' => 2], $result);
    }

    public function testSerializeHandlesNestedModelAndArrays()
    {
        // Create a nested model
        $parent = $this->getMockForAbstractClass(\Database\Orm\Model::class);
        $child = $this->getMockForAbstractClass(\Database\Orm\Model::class);
        $child->setValues(['foo' => 'bar']);
        $parent->setValues(['child' => $child, 'arr' => [['baz' => 1]]]);

        $result = $parent->serialize($parent->getValues());
        $this->assertEquals(['child' => ['foo' => 'bar'], 'arr' => [['baz' => 1]]], $result);
    }

    public function testToResponseReturnsGetValues()
    {
        $model = $this->getMockForAbstractClass(\Database\Orm\Model::class);
        $model->setValues(['k' => 'v']);
        $this->assertEquals(['k' => 'v'], $model->toResponse());
    }
    public function testSetValuesSetsAttributesModel()
    {
        $model = new DummyModel();
        $model->setValues(['foo' => 'abc', 'bar' => 123]);

        $this->assertEquals('abc', $model->foo);
        $this->assertEquals(123, $model->bar);
        $this->assertEquals(['foo' => 'abc', 'bar' => 123], $model->getValues());
    }

    public function testGetValuesReturnsAttributes()
    {
        $model = new DummyModel();
        $model->foo = 'hello';
        $model->bar = 42;

        $values = $model->getValues();
        $this->assertArrayHasKey('foo', $values);
        $this->assertArrayHasKey('bar', $values);
        $this->assertEquals('hello', $values['foo']);
        $this->assertEquals(42, $values['bar']);
    }

    public function testSetFieldCreatesFieldsFromRules()
    {
        $model = new TestModel2();

        // Use reflection to access the private $fields property
        $refFields = new ReflectionProperty(Model::class, 'fields');
        $refFields->setAccessible(true);
        $fields = $refFields->getValue($model);

        $this->assertInstanceOf(Fields::class, $fields);

        // Check that the fields were added
        $fooField = $fields->getField('foo');
        $bazField = $fields->getField('baz');

        $this->assertInstanceOf(Field::class, $fooField);
        $this->assertInstanceOf(Field::class, $bazField);

        $this->assertEquals('foo', $fooField->getName());
        $this->assertEquals('bar', $fooField->getData());
        $this->assertEquals('baz', $bazField->getName());
        $this->assertEquals(123, $bazField->getData());
    }

    public function testFilterUpdateFields()
    {
        $record = new DummyRecord();
        $fields = ['id' => 1, 'name' => 'foo', 'other' => 'bar'];
        $filtered = $record->filterUpdateFields($fields);
        $this->assertArrayNotHasKey('id', $filtered);
        $this->assertArrayHasKey('name', $filtered);
        $this->assertArrayHasKey('other', $filtered);
    }

    public function testGetTableName()
    {
        $this->assertEquals('DummyRecord', DummyRecord::getTableName());
    }

    public function testSetEventsAndTriggerEvent()
    {
        $record = new DummyRecord();
        $eventCalled = false;
        $refEvents = new ReflectionProperty(Record::class, 'events');
        $refEvents->setAccessible(true);
        $refEvents->setValue($record, [
            'before_save' => function($model) use (&$eventCalled) {
                $eventCalled = true;
            }
        ]);
        $record->setTriggerEvent(true);
        $record->triggerEvent('before_save');
        $this->assertTrue($eventCalled);
    }

    public function testHasOne()
    {
        $record = new DummyRecord();
        $mockHasOne = $this->getMockBuilder(\Database\Orm\Relation\HasOne::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Use Reflection to replace the method with a stub
        $recordClass = new ReflectionClass($record);
        $method = $recordClass->getMethod('hasOne');
        $method->setAccessible(true);
        $result = $method->invoke($record, DummyRecord::class, 'foreign_id', 'id');
        $this->assertInstanceOf(\Database\Orm\Relation\HasOne::class, $result);
    }

    public function testHasMany()
    {
        $record = new DummyRecord();
        $mockHasMany = $this->getMockBuilder(\Database\Orm\Relation\HasMany::class)
            ->disableOriginalConstructor()
            ->getMock();
        $recordClass = new ReflectionClass($record);
        $method = $recordClass->getMethod('hasMany');
        $method->setAccessible(true);
        $result = $method->invoke($record, DummyRecord::class, 'foreign_id', 'id');
        $this->assertInstanceOf(\Database\Orm\Relation\HasMany::class, $result);
    }

    public function testReload()
    {
        $record = $this->getMockBuilder(DummyRecord::class)
            ->onlyMethods(['getUniqueId', 'find'])
            ->getMock();
        $record->expects($this->once())->method('getUniqueId')->willReturn(['id', 1]);
        $record->method('find')->with(1)->willReturn('reloaded');
        $this->assertInstanceOf(DummyRecord::class, $record->reload());
        $this->assertEquals(false, $record->getIsLoadedByOrm());
    }

    public function testLoadWithAndHandleRelation()
    {
        // Prepare models
        $model1 = new DummyRecord();
        $model1->id = 1;
        $model2 = new DummyRecord();
        $model2->id = 2;
        $models = [$model1, $model2];

        // Prepare a reference record with with_models set
        $ref = new DummyRecord();
        $withModels = ['testRelation'];
        $withProp = (new ReflectionClass(\Database\Orm\Record::class))->getProperty('with_models');
        $withProp->setAccessible(true);
        $withProp->setValue($ref, $withModels);

        // Mock the relation and its methods
        $mockRelation = $this->getMockBuilder(\Database\Orm\Relation\HasMany::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRelation->method('getForeignKey')->willReturn('foreign_id');
        $mockRelation->method('getPrimaryKey')->willReturn('id');
        $mockRelation->method('getRelatedModel')->willReturn(DummyRecord::class);
        $mockRelation->method('getDbQuery')->willReturn(new DBQuery());
        $mockRelation->method('getWithModels')->willReturn([]);

        // Add a method to the ref for the relation
        $ref->testRelation = function() use ($mockRelation) {
            return $mockRelation;
        };
        // Use __get to trigger the relation
        $refClass = new ReflectionClass($ref);

        $refClass->getMethod('__get')->invoke($ref, 'getQuery');

        // Now test loadWith
        $loadWithMethod = $refClass->getMethod('loadWith');
        $loadWithMethod->setAccessible(true);
        $result = $loadWithMethod->invoke(null, $models, $ref);
        $this->assertIsArray($result);

        // Now test handleRelation
        $handleRelationMethod = $refClass->getMethod('handleRelation');
        $handleRelationMethod->setAccessible(true);
        $result2 = $handleRelationMethod->invoke(null, $mockRelation, $models, 'testRelation');
        $this->assertIsArray($result2);
    }
}

class DummyModel extends Model
{
    public $foo;
    public $bar;
    // Optionally override getRules if needed for setField
    public function getRules()
    {
        return [];
    }
}

class TestModel2 extends Model
{
    public $foo = 'bar';
    public $baz = 123;

    public function getRules()
    {
        return [
            'foo' => [['required'], ['message for foo']],
            'baz' => [['integer'], ['message for baz']],
        ];
    }
}
