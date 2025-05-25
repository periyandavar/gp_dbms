<?php

namespace Database\Orm\Relation;

use Database\Database;
use Database\DBQuery;
use Database\Orm\Model;

/**
 * Class Relation
 * @package Database\Orm\Relation
 *
 * @property Model   $model
 * @property string  $primaryKey
 * @property string  $relatedModel
 * @property string  $foreignKey
 * @property DBQuery $query
 *
  * DBQuery Methods
 * @method Database delete(string $table, array|string|null $where = null)
 * @method Database update(string $table, array $fields = [], array|string|null $where = null, ?string $join = null)
 * @method Database insert(string $table, array $fields = [], array $funcfields = [])
 * @method Database select($columns)
 * @method Database selectAs($selectData)
 * @method Database selectAll($reset)
 * @method Database from(string $tableName)
 * @method Database appendWhere(string $where)
 * @method string   getWhere()
 * @method Database where(...$args)
 * @method Database orWhere($args)
 * @method Database limit(int $limit, ?int $offset = 0)
 * @method Database orderBy(string $fieldName, string $order)
 * @method string   getExectedQuery()
 * @method array    getBindValues()
 * @method Database appendBindValues(array $values)
 * @method Database innerJoin(string $tableName)
 * @method Database leftJoin(string $tableName)
 * @method Database rightJoin(string $tableName)
 * @method Database crossJoin(string $tableName)
 * @method Database on(string $condition)
 * @method Database using(string $field)
 * @method Database groupBy($fields)
 * @method          setBindValue($values)
 * @method          setDbQuery($query)
 * @method          getSql()
 * @method Database reset()
 *
 */
abstract class Relation
{
    protected $model;
    protected string $primaryKey;
    protected string $relatedModel;
    protected string $foreignKey;
    protected DBQuery $query;
    protected $data = null;

    protected array $with_models = [];

    public function __construct(Model $model, string $primaryKey, string $relatedModel, string $foreignKey, $query = null)
    {
        $this->model = $model;
        $this->primaryKey = $primaryKey;
        $this->relatedModel = $relatedModel;
        $this->foreignKey = $foreignKey;
        $this->query = $query ?? (new DBQuery())->selectAll()->from($relatedModel::getTableName());
    }

    abstract public function handle();

    public function reload()
    {
        $this->data = null;
    }

    public function resolve($force_load = false)
    {
        if (is_null($this->data) || $force_load) {
            $this->data = $this->handle();
        }

        return $this->data;
    }

    /**
     * Get the value of model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set the value of model
     */
    public function setModel($model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the value of primaryKey
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Set the value of primaryKey
     *
     * @param string $primaryKey
     *
     * @return self
     */
    public function setPrimaryKey(string $primaryKey): self
    {
        $this->primaryKey = $primaryKey;

        return $this;
    }

    /**
     * Get the value of relatedModel
     *
     * @return string
     */
    public function getRelatedModel(): string
    {
        return $this->relatedModel;
    }

    /**
     * Set the value of relatedModel
     *
     * @param string $relatedModel
     *
     * @return self
     */
    public function setRelatedModel(string $relatedModel): self
    {
        $this->relatedModel = $relatedModel;

        return $this;
    }

    /**
     * Get the value of foreignKey
     *
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * Set the value of foreignKey
     *
     * @param string $foreignKey
     *
     * @return self
     */
    public function setForeignKey(string $foreignKey): self
    {
        $this->foreignKey = $foreignKey;

        return $this;
    }

    /**
     * Get the value of query
     *
     * @return DBQuery
     */
    public function getDbQuery(): DBQuery
    {
        return $this->query;
    }

    /**
     * Set the value of query
     *
     * @param DBQuery $query
     *
     * @return self
     */
    public function setDbQuery(DBQuery $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function __call($method, $args)
    {
        if (method_exists($this->query, $method)) {
            return call_user_func_array([$this->query, $method], $args);
        }

        throw new \BadMethodCallException("Method {$method} does not exist on " . get_class($this));
    }

    /**
     * Add eager loading objects
     *
     * @param string|array $with
     *
     * @return static
     */
    public function with($with)
    {
        if (is_array($with)) {
            $this->with_models = array_merge($this->with_models, $with);

            return $this;
        }
        $this->with_models[] = $with;

        return $this;
    }

    public function getWithModels()
    {
        return $this->with_models;
    }
}
