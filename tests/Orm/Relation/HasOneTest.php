<?php

use Database\DBQuery;
use Database\Driver\PdoDriver;
use Database\Orm\Model;
use Database\Orm\Relation\HasOne;
use Loader\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RelatedModel2 extends Model
{
    public static function getTableName()
    {
        return 'related_table';
    }
}

class HasOneTest extends TestCase
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
        $relatedModel = m::mock(RelatedModel2::class)->makePartial();
        $relatedModel->shouldReceive('select')->andReturnSelf();
        $relatedModel->shouldReceive('one')->andReturn((object) ['id' => 1, 'name' => 'Related1']);

        // Create the HasOne relation
        $hasOne = new HasOne($this->mockModel, 'id', RelatedModel2::class, 'foreign_key', $this->mockQuery);
        $mockDb = m::mock(PdoDriver::class)->makePartial();
        $mockDb->shouldReceive('getOne')->andReturn(
            (object) ['id' => 1, 'name' => 'Related1'],
        );

        Container::set('db', $mockDb);
        // Call the handle method
        $result = $hasOne->handle();

        // Assertions
        $this->assertIsObject($result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Related1', $result->name);
    }
}
