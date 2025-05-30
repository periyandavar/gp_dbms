<?php

use Database\DBQuery;
use Database\Driver\PdoDriver;
use Database\Orm\Model;
use Database\Orm\Record;
use Database\Orm\Relation\HasMany;
use Loader\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RelatedModel extends Record
{
    public static function getTableName()
    {
        return 'related_table';
    }
}

class HasManyTest extends TestCase
{
    private $mockModel;
    private $mockQuery;

    protected function setUp(): void
    {
        // Mock the parent model
        $this->mockModel = m::mock(Model::class);
        $this->mockModel->shouldReceive('getPrimaryKey')->andReturn('id');
        $this->mockModel->id = 1;

        // Mock the DBQuery instance
        $this->mockQuery = m::mock(DBQuery::class)->makePartial();
        $this->mockQuery->shouldReceive('selectAll')->andReturnSelf();
        $this->mockQuery->shouldReceive('from')->andReturnSelf();
        $this->mockQuery->shouldReceive('where')->andReturnSelf();
    }

    protected function tearDown(): void
    {
        // Close Mockery
        m::close();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHandle()
    {
        // Mock the related model
        $relatedModel = m::mock(RelatedModel::class)->makePartial();
        $relatedModel->shouldReceive('select')->andReturnSelf();

        // Create the HasMany relation
        $hasMany = new HasMany($this->mockModel, 'id', RelatedModel::class, 'foreign_key', $this->mockQuery);
        $mockDb = m::mock(PdoDriver::class)->makePartial();
        $mockDb->shouldReceive('getall')->andReturn([
            (object) ['id' => 1, 'name' => 'Related1'],
            (object) ['id' => 2, 'name' => 'Related2'],
        ]);

        Container::set('db', $mockDb);

        // Call the handle method
        $result = $hasMany->handle();

        // Assertions
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Related1', $result[0]->name);
        $this->assertEquals('Related2', $result[1]->name);
    }
}
