<?php

class DatabaseExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testDatabaseException()
    {
        $exception = new \Database\Exception\DatabaseException('Test message', 10, null, ['error' => 'test']);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(10, $exception->getCode());
        $this->assertEquals(['error' => 'test'], $exception->getErrorData());
        $exception->setErrorData(['new' => 'data']);
        $this->assertEquals(['new' => 'data'], $exception->getErrorData());
    }
}
