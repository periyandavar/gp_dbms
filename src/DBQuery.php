<?php

namespace Database;

use InvalidArgumentException;

/**
 * Super class for all DBQuery. All drivers should extend this DBQuery
 * DBQuery class consists of basic level functions for various purposes and
 * query building functionality
 *
 */
class DBQuery
{
    public const CONDITION_AND = 'AND';
    public const CONDITION_OR = 'OR';
    public const SORT_ASC = 'ASC';
    public const SORT_DESC = 'DESC';

    /**
     * This will contains the executed full query after the execute() get executed
     *
     * @var string $query
     */
    private $query;

    /**
     * This will contains the incomplete query generally without where
     *
     * @var string $sql
     */
    private $_sql;

    /**
     * This will contains the values to be bind
     *
     * @var array $bindValues
     */
    private $bindValues = [];

    /**
     * This will has the table name if its select query
     *
     * @var string $table
     */
    private $_table;

    /**
     * This will has columns
     *
     * @var string $columns
     */
    private $_columns;

    /**
     * This will has the limit value
     *
     * @var string $limit
     */
    private $_limit;

    /**
     * This will has order value
     *
     * @var string $orderBy
     */
    private $_orderby;

    /**
     * This will has the where condition
     *
     * @var string $where
     */
    private $_where;

    /**
     * This has join condition
     *
     * @var string $join
     */
    private $_join;

    /**
     * This will have groupby value
     *
     * @var string $groupby
     */
    private $_groupby;

    /**
     * This will have the groupby condition
     *
     * @var string $having
     */
    private $_having;

    /**
     * Resets all the query build values
     *
     * @access private
     *
     * @return void
     */
    private function _resetQuery()
    {
        $this->query = '';
        $this->_table = null;
        $this->_columns = null;
        $this->_sql = null;
        $this->bindValues = [];
        $this->_limit = null;
        $this->_orderby = null;
        $this->_where = '';
        $this->_join = null;
        $this->_groupby = null;
        $this->_having = null;
    }

    public function __construct()
    {
        $this->_resetQuery();
    }

    public function reset()
    {
        $this->_resetQuery();
    }

    /**
     * Delete function used to build delete query
     * we can call this in any one of the following ways
     * delete('table', 'id = 1') or delete('table')->where('id = 1');
     *
     * @param string            $table Table Name
     * @param array|string|null $where Where condition
     *
     * @return DBQuery
     */
    public function delete(string $table, mixed $where = null): DBQuery
    {
        $this->_resetQuery();
        $this->_sql = "DELETE FROM {$this->addBackticks($table)}";
        if (isset($where)) {
            if (is_array($where)) {
                [$where, $this->bindValues] = $this->frameWhere($where);
            }
            $this->_where = " WHERE $where";
        }

        return $this;
    }

    /**
     * Updates function used to build update query
     * we can call this in any one of the following ways
     * update('table', ["name"=>"Raja"] ,'id = 1') or
     * update('table',  ["name"=>"Raja"] )->where('id = 1');
     *
     * @param string            $table  Table Name
     * @param array             $fields Fields
     * @param array|string|null $where  Where condition
     * @param string|null       $join   Join condition
     *
     * @return DBQuery
     */
    public function update(
        string $table,
        array $fields = [],
        mixed $where = null,
        ?string $join = null
    ): DBQuery {
        $this->_resetQuery();

        // Ensure table and join are properly sanitized
        $table = trim($table);
        $join = $join ? trim($join) : '';

        // Construct the SET clause
        $setClauses = [];
        foreach ($fields as $column => $value) {
            $column = trim($column);

            // Handle dot notation for column names
            $setClauses[] = "{$this->addBackticks($column)} = ?";
            $this->bindValues[] = $value;
        }

        $set = $this->frameFields($setClauses, false);
        $this->_sql = "UPDATE {$this->addBackticks($table)} $join SET $set";

        // Handle WHERE clause
        if (!empty($where)) {
            if (is_array($where)) {
                [$whereClause, $bindValues] = $this->frameWhere($where);
                $this->_where = " WHERE $whereClause";
                $this->bindValues = array_merge($this->bindValues, $bindValues);
            } else {
                $this->_where = " WHERE $where";
            }
        }

        return $this;
    }

    public function frameWhere($data, $condition = self::CONDITION_AND)
    {
        $result = [];
        $bindValues = [];
        foreach ($data as $key => $value) {
            $result[] = "{$this->addBackticks($key)} = ?";
            $bindValues[] = $value;
        }

        return [implode(" $condition ", $result), $bindValues];
    }

    /**
     * This function used to build insert query
     * we can call this by the following way
     * insert(table, ['field' => 'value', 'fild1' => 'value1',
     *  'field2' => 'value2'], ['field' => CURDATE()])
     *
     * @param string $table      Table
     * @param array  $fields     Fields
     * @param array  $funcfields Fields with function values
     *
     * @return DBQuery
     */
    public function insert(
        string $table,
        array $fields = [],
        array $funcfields = []
    ): DBQuery {
        $this->_resetQuery();

        // Prepare the keys and values for the `fields` array
        $keys = $this->frameFields(array_keys($fields));
        $values = $this->frameFields(array_fill(0, count($fields), '?'), false);
        $this->bindValues = array_values($fields);

        // Prepare the keys and values for the `funcfields` array
        foreach ($funcfields as $column => $value) {
            if (!empty($keys)) {
                $keys .= ', ';
            }
            $keys .= $this->addBackticks($column);

            if (!empty($values)) {
                $values .= ', ';
            }
            $values .= "($value)";
        }
        // $keys = trim($keys, '`');
        // Construct the SQL query
        $this->_sql = "INSERT INTO $table ($keys) VALUES ($values)";

        return $this;
    }

    /**
     * This function used to build select query
     * we can call this in following way
     * select('field1', 'field2', 'field3');
     *
     * @return DBQuery
     */
    public function select(...$columns): DBQuery
    {
        $this->_resetQuery();

        if (empty($columns)) {
            $this->_columns = '*';

            return $this;
        }

        // $processedColumns = $this->backticksHandler($columns);

        $this->_columns .= $this->frameFields($columns);

        return $this;
    }
    /**
     * SelectAs used to add select fields with as value
     * call this function by
     * selectAs(['field1' => 'as1', 'field2' => 'as2'])
     *
     * @return DBQuery
     */

    public function selectAs($selectData): DBQuery
    {
        $this->_columns = empty($this->_columns) ? '' : $this->_columns . ', ';
        // foreach ($selectData as $key => $value) {
        //     if (is_numeric($key)) {
        //         $this->_columns .= "`$value`, ";
        //     } else {
        //         $this->_columns = "`$key` AS $value, ";
        //     }
        // }
        // $this->_columns = implode(', ', $this->backticksHandler($selectData));
        $this->_columns .= $this->frameFields($selectData);
        // $this->_columns = rtrim($this->_columns, ', ');

        return $this;
    }

    public function selectWith(...$args)
    {
        $this->_columns = empty($this->_columns) ? '' : $this->_columns . ', ';
        $this->_columns .= $this->frameFields($args, false);

        return $this;
    }

    /**
     * This function used to selectAll fields
     *
     * @return DBQuery
     */
    public function selectAll($reset = true): DBQuery
    {
        $reset && $this->_resetQuery();
        $this->_columns = '*';

        return $this;
    }

    /**
     * This function is used to select table in select query
     * use : select('field')->from('table');
     *
     * @param string $tableName Table Name
     *
     * @return DBQuery
     */
    public function from(string $tableName): DBQuery
    {
        $this->_table = $this->addBackticks($tableName);

        return $this;
    }

    /**
     * Appends the string to the where condition
     *
     * @param string $where     Where condition string
     * @param string $condition condtion to be used to concat.
     *
     * @return DBQuery
     */
    public function appendWhere(string $where, string $condition = self::CONDITION_AND): DBQuery
    {
        if ($this->_where === '') {
            $condition = '';
            $this->_where = ' WHERE ';
        }
        if (empty($condition)) {
            $this->_where .= $where;

            return $this;
        }
        $this->_where = rtrim($this->_where, ' ');
        $this->_where .= ' ' . $condition . ' ' . $where ;

        return $this;
    }

    /**
     * Returns where condition
     *
     * @return string
     */
    public function getWhere(): string
    {
        return $this->_where;
    }

    public function addWhere(string $type = self::CONDITION_AND, ...$args): DBQuery
    {
        $where = empty($this->_where) ? ' WHERE ' : $this->_where . " $type ";
        [$temp_where, $temp_param] = $this->frameWhereQuery($type, ...$args);

        $this->_where = $where . trim($temp_where);
        $this->bindValues = array_merge($this->bindValues, $temp_param);

        return $this;
    }

    /**
     * This function to add where condition with AND
     * we can use this in there ways
     * where(str), where(str,bind), where(str,oper,bind)
     * ex:
     * where('id != 1')
     * where('id != ?', 1)
     * where ('id', '!=', 1)
     * $where = ['id != 1']
     * where($where)
     * $where = ['id != ?', 1]
     * where($where)
     * $where = ['id', '!=', 1]
     * where($where)
     *
     * @return DBQuery
     */
    public function where(array|string ...$args): DBQuery
    {
        return $this->addWhere(self::CONDITION_AND, ...$args);
    }

    private function getSingleArgWhere($arg, $type = self::CONDITION_AND): array
    {
        $type = strtoupper($type);
        $where = '';
        $bindValues = [];
        if (! is_array($arg)) {
            return [$arg, []];
        }

        $keys = array_keys($arg);
        if (array_keys($keys) !== $keys) {
            return $this->frameWhere($arg, $type);
        }

        foreach ($arg as $param) {
            [$temp_where, $temp_param] = $this->getCondition((array) $param);
            $where = empty($where) ? $temp_where : $where . " {$type} {$temp_where}";
            $bindValues = array_merge($bindValues, $temp_param);
        }

        return [$where, $bindValues];
    }

    private function getThreeArgWhere(string $field, string $operator, mixed $value): array
    {
        // $type = strtoupper($type);
        $field = $this->addBackticks($field);

        // $this->_where = empty($this->_where) ?  : $this->_where . " {$type} {$field} {$operator} ?";

        return [
            " {$field} {$operator} ?",
            [$value]
        ];
    }

    private function getCondition(array $param): array
    {
        $count = count($param);
        $where = '';
        $bindValues = [];

        if ($count === 1) {
            $where .= $param[0];
        } elseif ($count === 2) {
            $where .= "{$this->addBackticks($param[0])} = ?";
            $bindValues = [$param[1]];
        } elseif ($count === 3) {
            $where .= $this->addBackticks($param[0]) . " {$param[1]} ?";
            $bindValues = [$param[2]];
        } else {
            throw new InvalidArgumentException('Invalid condition format.');
        }

        return [$where, $bindValues];
    }
    /**
     * This function to add where condition with OR
     * we can use this in there ways
     * orWhere(str), orWhere(str,bind), orWhere(str,oper,bind)
     * ex:
     * orWhere('id != 1')
     * orWhere('id != ?', 1)
     * orWhere ('id', '!=', 1)
     * $orWhere = ['id != 1']
     * orWhere($orWhere)
     * $orWhere = ['id != ?', 1]
     * orWhere($orWhere)
     * $orWhere = ['id', '!=', 1]
     * orWhere($orWhere)
     *
     * @return DBQuery
     */
    public function orWhere(...$args): DBQuery
    {
        return $this->addWhere(self::CONDITION_OR, ...$args);
    }

    /**
     * This will sets limit and offset values in select query
     *
     * @param int      $limit  limit
     * @param int|null $offset Offset value
     *
     * @return DBQuery
     */
    public function limit(int $limit, ?int $offset = null): DBQuery
    {
        if ($offset == null) {
            $this->_limit = " LIMIT $limit";
        } else {
            $this->_limit = " LIMIT $offset, $limit";
        }

        return $this;
    }

    /**
     * Sets order by
     *
     * @param string $fieldName Field name
     * @param string $order     order direction
     *
     * @return DBQuery
     */
    public function orderBy(string $fieldName, string $order = self::SORT_ASC): DBQuery
    {
        $fieldName = $fieldName ? trim($fieldName) : '';

        $order = trim(strtoupper($order));

        // validate it's not empty and have a proper valuse
        if (!empty($fieldName) && ($order == self::SORT_ASC || $order == self::SORT_DESC)) {
            $this->_orderby = empty($this->_orderby) ? " ORDER BY $fieldName $order" : $this->_orderby . ", $fieldName $order";
        }

        return $this;
    }
    /**
     * Returns the query value
     *
     * @return string
     */
    public function getExectedQuery(): string
    {
        return $this->query;
    }

    /**
     * Returns build query
     *
     * @return string
     */
    public function getQuery(): string
    {
        $query = ($this->_sql == '')
            ? 'SELECT '
                . $this->_columns
                . ' FROM '
                . $this->_table
                . $this->_join
                . $this->_where
                . $this->_groupby
                . $this->_having
                . $this->_limit
                . $this->_orderby
            : $this->_sql
                . $this->_where;

        return $query;
    }

    /**
     * Returns bindValues
     *
     * @return array
     */
    public function getBindValues(): array
    {
        return $this->bindValues;
    }

    /**
     * Appends new value to bind values array
     *
     * @param array $values values
     *
     * @return DBQuery
     */
    public function appendBindValues(array $values): DBQuery
    {
        foreach ($values as $value) {
            $this->bindValues[] = $value;
        }

        return $this;
    }

    /**
     * This function used to build inner join
     *
     * @param string $tableName Table Name
     * @param string $on        On
     *
     * @return DBQuery
     */
    public function innerJoin(string $tableName, string $on = ''): DBQuery
    {
        return $this->join($tableName, $on, 'INNER');
    }

    public function join(string $tableName, string $on = '', string $type = 'INNER'): DBQuery
    {
        $type = strtoupper($type);
        $this->_join .= " $type JOIN " . $this->addBackticks($tableName);

        if (! empty($on)) {
            return $this->on($on);
        }

        return $this;
    }

    /**
     * This function used to build left join
     *
     * @param string $tableName Table Name
     * @param string $on        On
     *
     * @return DBQuery
     */
    public function leftJoin(string $tableName, string $on = ''): DBQuery
    {
        return $this->join($tableName, $on, 'LEFT');
    }

    /**
     * This function used to build right join
     *
     * @param string $tableName TableName
     * @param string $on        On
     *
     * @return DBQuery
     */
    public function rightJoin(string $tableName, string  $on = ''): DBQuery
    {
        return $this->join($tableName, $on, 'RIGHT');
    }

    /**
     * This function used to build cross join
     *
     * @param string $tableName Table Name
     * @param string $on        On
     *
     * @return DBQuery
     */
    public function crossJoin(string $tableName, string $on = ''): DBQuery
    {
        return $this->join($tableName, $on, 'CROSS');
    }

    /**
     * This function used to set join condition with on
     *
     * @param string $condition On condition
     *
     * @return DBQuery
     */
    public function on(string $condition): DBQuery
    {
        $this->_join .= ' ON ' . $condition;

        return $this;
    }

    /**
     * This function used to set join condition with using
     *
     * @param string $field Field Name
     *
     * @return DBQuery
     */
    public function using(string $field): DBQuery
    {
        $this->_join .= " USING({$this->addBackticks($field)})";

        return $this;
    }

    /**
     * This function is used to perform group by
     *
     * @return DBQuery
     */
    public function groupBy(...$fields): DBQuery
    {
        $fields = $this->frameFields($fields);
        $this->_groupby = " GROUP BY ($fields)";

        return $this;
    }

    public function setBindValue($values)
    {
        $this->bindValues = $values;

        return $this;
    }

    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    public function getSql()
    {
        if (!empty($this->query)) {
            return $this->query;
        } elseif ($this->_sql == '') {
            $this->query = 'SELECT '
                . $this->_columns
                . ' FROM '
                . $this->_table
                . $this->_join
                . (empty($this->_where) ? '' : ' WHERE ' . $this->_where)
                . $this->_groupby
                . $this->_having
                . $this->_orderby
                . $this->_limit;
        } else {
            $this->query = $this->_sql . $this->_where;
        }

        return $this->query;
    }

    public function having(string $having, $bindValues = [])
    {
        $this->_having = ' HAVING ' . $having;
        $this->appendBindValues($bindValues);

        return $this;
    }

    private function addBackticks(string $field)
    {
        $field = trim($field);
        $field = str_replace('`', '', $field);
        if (strpos($field, ' ') && strpos($field, '.')) {
            [$tableColumn, $alias] = explode(' ', $field);
            [$table, $col] = explode('.', $tableColumn);

            return "`$table`.`$col` AS $alias";
        }
        if (strpos($field, '.') !== false) {
            [$table, $field] = explode('.', $field);

            return "`$table`.`$field`";
        }

        if (strpos($field, ' ') !== false) {
            [$col, $alias] = explode(' ', $field);

            return "`$col` AS $alias";
        }

        return "`$field`";
    }

    private function backticksHandler(array $fields): array
    {
        return array_map(function($field, $key) {
            if (is_numeric($key)) {
                return $this->addBackticks($field);
            }

            return $this->addBackticks("$field $key");
        }, $fields, array_keys($fields));
    }

    private function frameFields(array $fields, $add_backtick = true): string
    {
        $fields = $add_backtick ? $this->backticksHandler($fields) :
            array_map('trim', $fields);

        return implode(', ', $fields);
    }

    public function frameWhereQuery($type = self::CONDITION_AND, ...$args)
    {
        $type = strtoupper($type);

        $count = count($args);

        switch ($count) {
            case 1:
                return $this->getSingleArgWhere($args[0], $type);
            case 2:
                return [
                    $args[0],
                    [$args[1]]
                ];

            case 3:
                return $this->getThreeArgWhere($args[0], $args[1], $args[2]);
            default:
                throw new InvalidArgumentException("Invalid number of arguments passed to 'where' method.");
        }
    }

    public function addWhereGroup(string $type = self::CONDITION_AND, string $inner_condition = self::CONDITION_AND, ...$args): DBQuery
    {
        $where = empty($this->_where) ? ' WHERE (' : $this->_where . " $type (";
        [$temp_where, $temp_param] = $this->frameWhereQuery($inner_condition, ...$args);

        $this->_where = $where . trim($temp_where) . ')';
        $this->bindValues = array_merge($this->bindValues, $temp_param);

        return $this;
    }

    public function andWhereGroup($inner_condition = self::CONDITION_AND, ...$args): DBQuery
    {
        return $this->addWhereGroup(self::CONDITION_AND, $inner_condition, ...$args);
    }

    public function orWhereGroup($inner_condition = self::CONDITION_AND, ...$args): DBQuery
    {
        return $this->addWhereGroup(self::CONDITION_OR, $inner_condition, ...$args);
    }

    public function __toString()
    {
        return $this->getQuery();
    }
}
