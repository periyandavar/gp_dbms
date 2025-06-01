<?php

use Database\DBQuery;
use Database\Orm\Model;
use Database\Orm\Relation\Relation;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class TestRelation extends Relation
{
    public function handle()
    {
        // Simulate handling logic
        return ['related_data'];
    }
}

class RelationTest extends TestCase
{
    private $mockModel;
    private $mockQuery;

    protected function setUp(): void
    {
        // Mock the Model instance
        $this->mockModel = m::mock(Model::class);

        // Mock the DBQuery instance
        $this->mockQuery = m::mock('Database\DBQuery');
    }

    protected function tearDown(): void
    {
        // Close Mockery
        m::close();
    }

    public function testConstructor()
    {
        $relation = new TestRelation($this->mockModel, 'id', 'RelatedModel', 'foreign_key', $this->mockQuery);

        $this->assertSame($this->mockModel, $relation->getModel());
        $this->assertEquals('id', $relation->getPrimaryKey());
        $this->assertEquals('RelatedModel', $relation->getRelatedModel());
        $this->assertEquals('foreign_key', $relation->getForeignKey());
        $this->assertSame($this->mockQuery, $relation->getDbQuery());
    }

    public function testSettersAndGetters()
    {
        $relation = new TestRelation($this->mockModel, 'id', 'RelatedModel', 'foreign_key', $this->mockQuery);

        $relation->setModel($this->mockModel);
        $relation->setPrimaryKey('new_id');
        $relation->setRelatedModel('NewRelatedModel');
        $relation->setForeignKey('new_foreign_key');
        $relation->setDbQuery($this->mockQuery);

        $this->assertSame($this->mockModel, $relation->getModel());
        $this->assertEquals('new_id', $relation->getPrimaryKey());
        $this->assertEquals('NewRelatedModel', $relation->getRelatedModel());
        $this->assertEquals('new_foreign_key', $relation->getForeignKey());
        $this->assertSame($this->mockQuery, $relation->getDbQuery());
    }

    public function testResolve()
    {
        $relation = new TestRelation($this->mockModel, 'id', 'RelatedModel', 'foreign_key', $this->mockQuery);

        $data = $relation->resolve();
        $this->assertEquals(['related_data'], $data);

        // Test force reload
        $relation->reload();
        $data = $relation->resolve(true);
        $this->assertEquals(['related_data'], $data);
    }

    public function testCallDelegatesToQuery()
    {
        $mockQuery = $this->getMockBuilder(DBQuery::class)
            ->onlyMethods(['select'])
            ->getMock();
        $mockQuery->expects($this->once())
    ->method('select')
    ->with('foo')
    ->willReturn($mockQuery); // Return the mock itself

        // $this->assertSame($mockQuery, $relation->__call('select', ['foo']));

        $mockModel = $this->createMock(Model::class);

        $relation = new DummyRelation($mockModel, 'id', 'RelatedModel', 'foreign_id', $mockQuery);

        $this->assertEquals($mockQuery, $relation->__call('select', ['foo']));
    }

    public function testCallThrowsOnUnknownMethod()
    {
        $mockQuery = $this->createMock(DBQuery::class);
        $mockModel = $this->createMock(Model::class);
        $relation = new DummyRelation($mockModel, 'id', 'RelatedModel', 'foreign_id', $mockQuery);

        $this->expectException(\BadMethodCallException::class);
        $relation->__call('nonexistentMethod', []);
    }

    public function testWithAndGetWithModels()
    {
        $mockQuery = $this->createMock(DBQuery::class);
        $mockModel = $this->createMock(Model::class);
        $relation = new DummyRelation($mockModel, 'id', 'RelatedModel', 'foreign_id', $mockQuery);

        $relation->with('foo');
        $relation->with(['bar', 'baz']);

        $this->assertEquals(['foo', 'bar', 'baz'], $relation->getWithModels());
    }
}

class DummyRelation extends Relation
{
    public function handle()
    {
    }
}
