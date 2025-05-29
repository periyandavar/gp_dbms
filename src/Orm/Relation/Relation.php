<?php

namespace Database\Orm\Relation;

use Database\DBQuery;
use Database\Orm\Model;

abstract class Relation
{
    protected $model;
    protected string $primaryKey;
    protected string $relatedModel;
    protected string $foreignKey;
    protected ?DBQuery $query;
    protected $data = null;

    public function __construct(Model $model, string $primaryKey, string $relatedModel, string $foreignKey, $query = null)
    {
        $this->model = $model;
        $this->primaryKey = $primaryKey;
        $this->relatedModel = $relatedModel;
        $this->foreignKey = $foreignKey;
        $this->query = $query;
    }

    // public function hasMany($id)
    // {
    //     $query = $this->query ?? new DBQuery();
    //     $query->where([$this->foreignKey = $this->model->$id]);

    //     if (! get_parent_class($this->relatedModel) == Model::class) {
    //         return [];
    //     }

    //     /**
    //      * @var Model
    //      */
    //     $targetModel = new ${$this->relatedModel}();

    //     return $targetModel->select($this->query)->all();
    // }

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
    public function getQuery(): DBQuery
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
    public function setQuery(DBQuery $query): self
    {
        $this->query = $query;

        return $this;
    }
}
