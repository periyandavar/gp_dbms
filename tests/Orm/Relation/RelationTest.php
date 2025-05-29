<?php

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
}
