<?php

namespace Database\Orm\Relation;

use Database\DBQuery;
use Database\Orm\Model;

class HasMany extends Relation
{
    public function handle()
    {
        // Get the related model class
        $class = $this->relatedModel;
        $this->query = $this->query ?? (new DBQuery())->selectAll()->from($class::getTableName());
        $primarykey = $this->primaryKey;
        $this->query->where("{$this->foreignKey} = {$this->model->$primarykey}");

        /**
         * @var Model
         */
        $targetModel = new $class();

        return $targetModel->select($this->query)->with($this->with_models)->all();
    }
}
