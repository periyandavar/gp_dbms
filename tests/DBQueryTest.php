<?php

use PHPUnit\Framework\TestCase;
use Database\DBQuery;

class DBQueryTest extends TestCase
{
    public function testSelectQuery()
    {
        $dbQuery = new DBQuery();
        $query = $dbQuery->select('id', 'name')->from('users')->getQuery();

        $this->assertEquals(
            'SELECT `id`, `name` FROM `users`',
            $query
        );
    }

    public function testDeleteQuery()
    {
        $dbQuery = new DBQuery();
        $query = $dbQuery->delete('users', 'id = 1')->getQuery();

        $this->assertEquals(
            'DELETE FROM `users` WHERE id = 1',
            $query
        );
    }

    public function testUpdateQuery()
    {
        $dbQuery = new DBQuery();
        $query = $dbQuery->update('users', ['name' => 'John'], 'id = 1')->getQuery();

        $this->assertEquals(
            'UPDATE users  SET `name` = ? WHERE id = 1',
            $query
        );
    }

    public function testInsertQuery()
    {
        $dbQuery = new DBQuery();
        $query = $dbQuery->insert('users', ['name' => 'John', 'email' => 'john@example.com'])->getQuery();

        $this->assertEquals(
            'INSERT INTO users (`name`, `email`) VALUES (?,?)',
            $query
        );
    }
}