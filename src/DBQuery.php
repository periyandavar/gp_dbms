<?php

namespace Database;

/**
 * Super class for all DBQuery. All drivers should extend this DBQuery
 * DBQuery class consists of basic level functions for various purposes and
 * query building functionality
 *
 */
class DBQuery
{
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
    private $bindValues;

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
        $this->_where = null;
        $this->_join = null;
        $this->_groupby = null;
        $this->_having = null;
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
        $this->_sql = "DELETE FROM `$table`";
        if (isset($where)) {
            if (is_array($where)) {
                [$where, $this->bindValues] = $this->frameWhere($where);
            }
            $this->_where = " WHERE $where";
        }

        return $this;
    }

    /**
     * Set the values in update query
     *
     * @return DBQuery
     */
    public function setTo(...$args): DBQuery
    {
        $change = implode(',', $args);
        // $this->_sql .= Utility::endsWith($this->_sql, 'SET ') ? '' : ',';
        $this->_sql .= $change;

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
        $set = '';
        $index = 1;
        foreach ($fields as $column => $field) {
            $column = trim($column);
            if (strpos($column, '.')) {
                $column = explode('.', $column);
                $column = $column[0] . '`.`' . $column[1];
            }
            $set .= "`$column` = ?";
            $this->bindValues[] = $field;
            if ($index < count($fields)) {
                $set .= ', ';
            }
            $index++;
        }
        $this->_sql = "UPDATE $table " . $join . ' SET ' . $set;
        if (isset($where)) {
            if (is_array($where)) {
                [$where, $bindValues] = $this->frameWhere($where);
                $this->bindValues = array_merge($this->bindValues, $bindValues);
            }
            $this->_where = " WHERE $where";
        }

        return $this;
    }

    public function frameWhere($data, $condition = 'AND')
    {
        $result = [];
        $bindValues = [];
        foreach ($data as $key => $value) {
            $result[] = "$key = ?";
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
        $keys = '';
        if (count($fields) > 0) {
            $keys = implode('`, `', array_keys($fields));
        }
        $values = '';
        $index = 1;
        foreach ($fields as $column => $value) {
            $values .= '?';
            $this->bindValues[] = $value;
            if ($index < count($fields)) {
                $values .= ',';
            }
            $index++;
        }
        $values = ($values != '' && count($funcfields) > 0)
            ? $values . ', '
            : $values;
        $index = 1;
        foreach ($funcfields as $column => $value) {
            $values .= "($value)";
            $keys = $keys != ''
                ? $keys . '`, `' . $column
                : $column;
            if ($index < count($funcfields)) {
                $values .= ',';
            }
            $index++;
        }
        $this->_sql = "INSERT INTO $table (`$keys`) VALUES ({$values})";

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
        for ($i = 0; $i < count($columns); $i++) {
            $columns[$i] = trim($columns[$i]);
            if (strpos($columns[$i], ' ') && strpos($columns[$i], '.')) {
                $columns[$i] = explode(' ', $columns[$i]);
                $columns[$i][0] = explode('.', $columns[$i][0]);
                $columns[$i] = '`'
                    . $columns[$i][0][0]
                    . '` .`'
                    . $columns[$i][0][1]
                    . '` '
                    . $columns[$i][1];
            } elseif (strpos($columns[$i], ' ')) {
                $columns[$i] = explode(' ', $columns[$i]);
                $columns[$i] = '`' . $columns[$i][0] . '` ' . $columns[$i][1];
            } elseif (strpos($columns[$i], '.')) {
                $columns[$i] = explode('.', $columns[$i]);
                $columns[$i] = '`' . $columns[$i][0] . '`.`' . $columns[$i][1] . '`';
            } else {
                $columns[$i] = '`' . $columns[$i] . '`';
            }
        }
        $columns = implode(', ', $columns);
        $this->_columns .= "$columns";

        return $this;
    }
    /**
     * SelectAs used to add select fields with as value
     * call this function by
     * selectAs(['field1' => 'as1', 'field2' => 'as2'])
     *
     * @return DBQuery
     */
    public function selectAs(...$selectData): DBQuery
    {
        $selectData = implode(',', $selectData);
        $this->_columns = ($this->_columns != null)
            ? $this->_columns . ', ' . $selectData
            : $selectData;

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
        if (strpos($tableName, ' ')) {
            $tableName = explode(' ', $tableName);
            $tableName = '`' . $tableName[0] . '` ' . $tableName[1];
        } else {
            $tableName = '`' . $tableName . '`';
        }
        $this->_table = $tableName;

        return $this;
    }

    /**
     * Appends the string to the where condition
     *
     * @param string $where Where condition string
     *
     * @return DBQuery
     */
    public function appendWhere(string $where): DBQuery
    {
        $this->_where = $this->_where == null ? '' : $this->_where;
        $this->_where .= $where;

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
    public function where(...$args): DBQuery
    {
        if ($this->_where == null) {
            $this->_where .= ' WHERE ';
        } else {
            $this->_where .= ' AND ';
        }
        $count = count($args);

        if ($count == 1) {
            $arg = $args[0];

            if (is_array($arg)) {
                $keys = array_keys($arg);
                if (array_keys($keys) !== $keys) {
                    [$this->_where, $bindValues] = $this->frameWhere($arg);
                    $this->bindValues = array_merge($this->bindValues, $bindValues);

                    return $this;
                }

                $index = 1;

                foreach ($arg as $param) {
                    if ($index != 1) {
                        $this->_where .= ' AND ';
                    }
                    $parmCount = count($param);
                    if ($parmCount == 1) {
                        $this->_where .= $param;
                    } elseif ($parmCount == 2) {
                        $this->_where .= $param[0];
                        $this->bindValues[] = $param[1];
                    } elseif ($parmCount == 3) {
                        $this->_where .= '`'
                            . trim($param[0])
                            . '`'
                            . $param[1]
                            . ' ?';
                        $this->bindValues[] = $param[2];
                    }
                    $index++;
                }
            } else {
                $this->_where .= $arg;
            }
        } elseif ($count == 2) {
            $this->_where .= $args[0];
            $this->bindValues[] = $args[1];
        } elseif ($count == 3) {
            $field = trim($args[0]);
            if (strpos($field, '.')) {
                $field = explode('.', $field);
                $field = $field[0] . '`.`' . $field[1];
            }
            $this->_where .= '`' . $field . '`' . $args[1] . ' ?';
            $this->bindValues[] = $args[2];
        }

        return $this;
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
        if ($this->_where == null) {
            $this->_where .= ' WHERE ';
        } else {
            $this->_where .= ' OR ';
        }
        $count = count($args);

        if ($count == 1) {
            $arg = $args[0];

            if (is_array($arg)) {
                $index = 1;

                foreach ($arg as $param) {
                    if ($index !== 1) {
                        $this->_where .= ' OR ';
                    }
                    $parmCount = count($param);
                    if ($parmCount == 1) {
                        $this->_where .= $param;
                    } elseif ($parmCount == 2) {
                        $this->_where .= $param[0];
                        $this->bindValues[] = $param[1];
                    } elseif ($parmCount == 3) {
                        $this->_where .= '`' . trim($param[0]) . '`'
                             . $param[1]
                             . ' ?';
                        $this->bindValues[] = $param[2];
                    }
                    $index++;
                }
            } else {
                $this->_where .= $arg;
            }
        } elseif ($count == 2) {
            $this->_where .= $args[0];
            $this->bindValues[] = $args[1];
        } elseif ($count == 3) {
            $field = trim($args[0]);
            if (strpos($field, '.')) {
                $field = explode('.', $field);
                $field = $field[0] . '`.`' . $field[1];
            }
            $this->_where .= '`' . $field . '`' . $args[1] . ' ?';
            $this->bindValues[] = $args[2];
        }

        return $this;
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
            $this->_limit = " LIMIT $offset,$limit";
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
    public function orderBy(string $fieldName, string $order = 'ASC'): DBQuery
    {
        $fieldName = $fieldName ? trim($fieldName) : '';

        $order = trim(strtoupper($order));

        // validate it's not empty and have a proper valuse
        if (!empty($fieldName) && ($order == 'ASC' || $order == 'DESC')) {
            if ($this->_orderby == null) {
                $this->_orderby = " ORDER BY $fieldName $order";
            } else {
                $this->_orderby .= ", $fieldName $order";
            }
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
     *
     * @return DBQuery
     */
    public function innerJoin(string $tableName): DBQuery
    {
        if (strpos($tableName, ' ')) {
            $tableName = explode(' ', $tableName);
            $tableName = '`' . $tableName[0] . '` ' . $tableName[1];
        } else {
            $tableName = '`' . $tableName . '`';
        }
        $this->_join .= ' INNER JOIN ' . $tableName;

        return $this;
    }

    /**
     * This function used to build left join
     *
     * @param string $tableName Table Name
     *
     * @return DBQuery
     */
    public function leftJoin(string $tableName): DBQuery
    {
        if (strpos($tableName, ' ')) {
            $tableName = explode(' ', $tableName);
            $tableName = '`' . $tableName[0] . '` ' . $tableName[1];
        } else {
            $tableName = '`' . $tableName . '`';
        }
        $this->_join .= ' LEFT JOIN ' . $tableName;

        return $this;
    }

    /**
     * This function used to build right join
     *
     * @param string $tableName TableName
     *
     * @return DBQuery
     */
    public function rightJoin(string $tableName): DBQuery
    {
        if (strpos($tableName, ' ')) {
            $tableName = explode(' ', $tableName);
            $tableName = '`' . $tableName[0] . '` ' . $tableName[1];
        } else {
            $tableName = '`' . $tableName . '`';
        }
        $this->_join .= ' Right JOIN ' . $tableName;

        return $this;
    }

    /**
     * This function used to build cross join
     *
     * @param string $tableName Table Name
     *
     * @return DBQuery
     */
    public function crossJoin(string $tableName): DBQuery
    {
        if (strpos($tableName, ' ')) {
            $tableName = explode(' ', $tableName);
            $tableName = '`' . $tableName[0] . '` ' . $tableName[1];
        } else {
            $tableName = '`' . $tableName . '`';
        }
        $this->_join .= ' CROS JOIN ' . $tableName;

        return $this;
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
        $this->_join .= ' USING(' . $field . ')';

        return $this;
    }

    /**
     * This function is used to perform group by
     *
     * @return DBQuery
     */
    public function groupBy(...$fields): DBQuery
    {
        $fields = implode(', ', $fields);
        $this->_groupby = ' GROUP BY ' . $fields;

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
                . $this->_where
                . $this->_groupby
                . $this->_having
                . $this->_orderby
                . $this->_limit;
        } else {
            $this->query = $this->_sql . $this->_where;
        }

        return $this->query;
    }
}
