<?php

use Database\Orm\Record;
use PHPUnit\Framework\TestCase;

class DummyRecord extends Record
{
    public $id = 1;
    public $name = 'foo';
}

class RecordTest extends TestCase
{
    public function setUp(): void
    {
        // Reset static db property before each test
        $ref = new ReflectionProperty(DummyRecord::class, 'db');
        $ref->setAccessible(true);
        $ref->setValue(null, null);
    }

    public function testGetTableName()
    {
        $this->assertEquals('DummyRecord', DummyRecord::getTableName());
    }

    public function testGetUniqueKey()
    {
        $this->assertEquals('id', DummyRecord::getUniqueKey());
    }

    public function testSetTriggerEvent()
    {
        $record = new DummyRecord();
        $record->setTriggerEvent(true);
        $this->assertTrue($record->isTriggerEvent());
    }

    public function testSkipUpdateOn()
    {
        $record = new DummyRecord();
        $this->assertEquals(['id'], $record->skipUpdateOn());
    }

    public function testUpdateAll()
    {
        $mockDb = $this->getMockBuilder(stdClass::class)
            ->addMethods(['setDbQuery', 'execute'])
            ->getMock();
        $mockDb->expects($this->once())->method('setDbQuery')->willReturnSelf();
        $mockDb->expects($this->once())->method('execute')->willReturn(true);

        // Inject mock db
        $ref = new ReflectionProperty(DummyRecord::class, 'db');
        $ref->setAccessible(true);
        $ref->setValue(null, $mockDb);

        $this->assertTrue(DummyRecord::updateAll(['name' => 'bar'], 'id=1'));
    }

    public function testDeleteAll()
    {
        $mockDb = $this->getMockBuilder(stdClass::class)
            ->addMethods(['setDbQuery', 'execute'])
            ->getMock();
        $mockDb->expects($this->once())->method('setDbQuery')->willReturnSelf();
        $mockDb->expects($this->once())->method('execute')->willReturn(true);

        // Inject mock db
        $ref = new ReflectionProperty(DummyRecord::class, 'db');
        $ref->setAccessible(true);
        $ref->setValue(null, $mockDb);

        $this->assertTrue(DummyRecord::deleteAll('id=1'));
    }

    //     public function testSetTriggerEvent()
    // {
    //     $record = new DummyRecord();
    //     $ref = new ReflectionProperty(Record::class, 'is_trigger_event');
    //     $ref->setAccessible(true);
    //     $record->setTriggerEvent(true);
    //     $this->assertTrue($ref->getValue($record));
    // }

    public function testEventFlowAllMethods()
    {
        $record = new DummyRecord();

        // Set a callable event
        $eventCalled = false;
        $refEvents = new ReflectionProperty(Record::class, 'events');
        $refEvents->setAccessible(true);
        $refEvents->setValue($record, [
            'before_save' => function($model) use (&$eventCalled) {
                $eventCalled = true;
            }
        ]);
        // Enable trigger event
        $record->setTriggerEvent(true);
        $record->triggerEvent('before_save');
        $this->assertTrue($eventCalled);

        // Set an Events instance
        $mockEvent = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['handle'])
            ->getMock();
        $mockEvent->method('handle')->with($record);

        $refEvents->setValue($record, ['after_save' => $mockEvent]);
        $record->triggerEvent('after_save');
    }
}
