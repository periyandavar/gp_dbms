<?php

use Database\DBQuery;
use PHPUnit\Framework\TestCase;

class DBQueryTest extends TestCase
{
    public function testSelectQuery()
    {
        $dbQuery = new DBQuery();
        $query = $dbQuery->select('id', 'age value', 'users.age value1', 'users.name')->from('users')->getQuery();

        $this->assertEquals(
            'SELECT `id`, `age` AS value, `users`.`age` AS value1, `users`.`name` FROM `users`',
            $query
        );
    }

    public function testSelectAs()
    {
        $dbQuery = new DBQuery();
        $query = $dbQuery->selectAs(['value' => 'id', 'value2'])->from('users.user')->getQuery();

        $this->assertEquals(
            'SELECT `id` AS value, `value2` FROM `users`.`user`',
            $query
        );
    }

    public function testAppends()
    {
        $dbQuery = new DBQuery();
        $dbQuery->setBindValue([12])->appendBindValues(['value' => 1]);
        $dbQuery->appendWhere('test = 1');
        $dbQuery->appendWhere('id = 1');
        $this->assertSame(' WHERE test = 1 AND id = 1', $dbQuery->getWhere(), );
        $this->assertSame([12, 1], $dbQuery->getBindValues());
    }

    public function testAddWhere()
    {
        $dbQuery = new DBQuery();

        // Single argument
        $dbQuery->select()->addWhere('AND', ['id = 1']);
        $this->assertEquals(' WHERE id = 1', $dbQuery->getWhere());

        // Two arguments
        $dbQuery->addWhere('AND', 'name = ?', 'John');
        $this->assertEquals(' WHERE id = 1 AND name = ?', $dbQuery->getWhere());
        $this->assertEquals(['John'], $dbQuery->getBindValues());

        // Three arguments
        $dbQuery->addWhere('AND', 'age', '>=', 25);
        $this->assertEquals(' WHERE id = 1 AND name = ? AND `age` >= ?', $dbQuery->getWhere());
        $this->assertEquals(['John', 25], $dbQuery->getBindValues());

        $dbQuery->orWhere('value', '>=', 25);
        $this->assertEquals(' WHERE id = 1 AND name = ? AND `age` >= ? OR `value` >= ?', $dbQuery->getWhere());
        $this->assertEquals(['John', 25, 25], $dbQuery->getBindValues());
        $dbQuery->reset();

        $dbQuery->select()->addWhere('AND', [['id', '=', 1], ['age', '=', 1]]);
        $this->assertEquals(' WHERE `id` = ? AND `age` = ?', $dbQuery->getWhere());
        $this->assertEquals([1, 1], $dbQuery->getBindValues());
        $dbQuery->reset();

        $dbQuery->select()->addWhere('AND', [['id', 1], ['age', 1]]);
        $this->assertEquals(' WHERE `id` = ? AND `age` = ?', $dbQuery->getWhere());
        $this->assertEquals([1, 1], $dbQuery->getBindValues());
        $dbQuery->reset();
    }

    public function testOrderBy()
    {
        $dbQuery = new DBQuery();

        // Single order by
        $dbQuery->select()->from('users')->orderBy('name', 'ASC');
        $this->assertEquals('SELECT * FROM `users` ORDER BY name ASC', $dbQuery->getQuery());

        // Multiple order by
        $dbQuery->orderBy('age', 'DESC');
        $this->assertEquals('SELECT * FROM `users` ORDER BY name ASC, age DESC', $dbQuery->getQuery());
    }

    public function testInnerJoin()
    {
        $dbQuery = new DBQuery();

        $dbQuery->select()->from('posts')->innerJoin('users')->using('user_id');
        $query = $dbQuery->getQuery();
        $this->assertStringContainsString('INNER JOIN `users` USING(`user_id`)', $query);

        $dbQuery->select()->from('posts')->innerJoin('users', 'users.id = posts.user_id');
        $query = $dbQuery->getQuery();

        $this->assertStringContainsString('INNER JOIN `users` ON users.id = posts.user_id', $query);

        $dbQuery->select()->from('posts')->innerJoin('users')->on('users.id = posts.user_id');
        $query = $dbQuery->getQuery();

        $this->assertStringContainsString('INNER JOIN `users` ON users.id = posts.user_id', $query);

        $dbQuery->select()->from('posts')->innerJoin('users')->using('users.user_id');
        $query = $dbQuery->getQuery();
        $this->assertStringContainsString('INNER JOIN `users` USING(`users`.`user_id`)', $query);
    }

    public function testLeftJoin()
    {
        $dbQuery = new DBQuery();

        $dbQuery->select()->from('users')->leftJoin('db.profiles', 'profiles.user_id = users.id');
        $query = $dbQuery->getQuery();

        $this->assertStringContainsString('SELECT * FROM `users` LEFT JOIN `db`.`profiles` ON profiles.user_id = users.id', $query);
    }

    public function testRightJoin()
    {
        $dbQuery = new DBQuery();

        $dbQuery->select()->from('users')->rightJoin('profiles', 'profiles.user_id = users.id');
        $query = $dbQuery->getQuery();

        $this->assertStringContainsString('SELECT * FROM `users` RIGHT JOIN `profiles` ON profiles.user_id = users.id', $query);
    }

    public function testGroupBySingleColumn()
    {
        $dbQuery = new DBQuery();

        // Group by a single column
        $dbQuery->select('id', 'name')->from('users')->groupBy('id');
        $query = $dbQuery->getQuery();

        $this->assertEquals(
            'SELECT `id`, `name` FROM `users` GROUP BY (`id`)',
            $query,
            'Expected query to match with single column grouping'
        );
    }

    public function testGroupByMultipleColumns()
    {
        $dbQuery = new DBQuery();

        // Group by multiple columns
        $dbQuery->select('id', 'name', 'role')->from('users')->groupBy('id', 'role');
        $query = $dbQuery->getQuery();

        $this->assertEquals(
            'SELECT `id`, `name`, `role` FROM `users` GROUP BY (`id`, `role`)',
            $query,
            'Expected query to match with multiple column grouping'
        );
    }

    public function testGroupByWithHavingClause()
    {
        $dbQuery = new DBQuery();

        // Group by with a HAVING clause
        $dbQuery->select('role')->selectWith('COUNT(id) as user_count')
            ->from('users')
            ->groupBy('role')
            ->having('user_count > ?', [5])
            ->limit(10, 10);
        $query = $dbQuery->getQuery();

        $this->assertEquals(
            'SELECT `role`, COUNT(id) as user_count FROM `users` GROUP BY (`role`) HAVING user_count > ? LIMIT 10, 10',
            $query,
            'Expected query to match with GROUP BY and HAVING clause'
        );
        $this->assertEquals(
            [5],
            $dbQuery->getBindValues(),
            'Expected bind values to match for HAVING clause'
        );
    }

    public function testCrossJoin()
    {
        $dbQuery = new DBQuery();

        $dbQuery->crossJoin('categories');
        $query = $dbQuery->getQuery();

        $this->assertStringContainsString('CROSS JOIN `categories`', $query);
    }

    public function testDeleteQuery()
    {
        $dbQuery = new DBQuery();
        $query = $dbQuery->delete('users', ['id' => 1])->getSql();

        $this->assertEquals(
            'DELETE FROM `users` WHERE `id` = ?',
            $query
        );
        $this->assertSame([1], $dbQuery->getBindValues());
    }

    public function testUpdateQuery()
    {
        $dbQuery = new DBQuery();
        $query = $dbQuery->update('users', ['users.name' => 'John', 'value' => 10], 'id = 1')->getQuery();

        $this->assertEquals(
            'UPDATE `users`  SET `users`.`name` = ?, `value` = ? WHERE id = 1',
            $query
        );
        $this->assertSame(['John', 10], $dbQuery->getBindValues());

        $query = $dbQuery->update('users', ['users.name' => 'John', 'value' => 10], ['id' => '1'])->getQuery();

        $this->assertEquals(
            'UPDATE `users`  SET `users`.`name` = ?, `value` = ? WHERE `id` = ?',
            $query
        );
        $this->assertSame('', $dbQuery->getExectedQuery());
        $this->assertSame(['John', 10, '1'], $dbQuery->getBindValues());
    }

    public function testInsertQuery()
    {
        $dbQuery = new DBQuery();
        $query = $dbQuery->insert('users', ['name' => 'John', 'email' => 'john@example.com'], ['created' => 'CURDATE()'])->getQuery();

        $this->assertEquals(
            'INSERT INTO users (`name`, `email`, `created`) VALUES (?, ?, (CURDATE()))',
            $query
        );
    }

    public function testSetQuery()
    {
        $dbQuery = new DBQuery();
        $dbQuery->setQuery('query');
        $this->assertEquals('query', $dbQuery->getSql());
    }

    public function testWhere()
    {
        $query = 'SELECT * FROM `users` WHERE `id` = ? AND `id1` = ?';
        $dbQuery = new DBQuery();
        $dbQuery->selectAll()->from('users')->where(['id' => 1, 'id1' => 1]);
        $this->assertSame($query, $dbQuery->getQuery());
        $dbQuery->selectAll()->from('users')->where('`id` = ? AND `id1` = ?');
        $this->assertSame($query, $dbQuery->getQuery());
        $dbQuery->selectAll()->from('users')->where('id', '=', 1)->where('`id1` = ?', 1);
        $this->assertSame($query, $dbQuery->getQuery());
        $query = 'SELECT * FROM `users` WHERE `users`.`id` = ?';
        $dbQuery->selectAll()->from('users')->where('users.id', '=', 1);
        $this->assertSame($query, $dbQuery->getQuery());
    }

    public function testWhereGroup()
    {
        $query = 'SELECT * FROM `users` WHERE (`id` = ? OR `id1` = ?) OR (`id` = ? AND `id1` = ?)';
        $dbQuery = new DBQuery();
        $dbQuery->selectAll()->from('users')->andWhereGroup('OR', ['id' => 1, 'id1' => 1])->orwhereGroup('AND', '`id` = ? AND `id1` = ?');
        $this->assertSame($query, $dbQuery->getQuery());
        $this->assertSame($query, $dbQuery->__toString());
    }

    public function testInvalidWhereParam()
    {
        $this->expectException(InvalidArgumentException::class);
        $dbQuery = new DBQuery();
        $dbQuery->selectAll()->from('users')->where('id', '=', 11, 1);
    }

    public function testInvalidConditionParam()
    {
        $this->expectException(InvalidArgumentException::class);
        $dbQuery = new DBQuery();
        $dbQuery->selectAll()->from('users')->addWhere('AND', [['id', '=', 11, 1]]);
    }
}
