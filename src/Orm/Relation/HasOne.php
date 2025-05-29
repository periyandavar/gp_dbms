<?php

namespace Database\Orm\Relation;

use Database\DBQuery;
use Database\Orm\Model;

class HasOne extends Relation
{
    public function handle()
    {
        $class = $this->relatedModel;
        $this->query = $this->query ?? (new DBQuery())->selectAll()->from($class::getTableName());
        $primarykey = $this->primaryKey;
        $this->query->where("{$this->foreignKey} = {$this->model->$primarykey}");

        $class = $this->relatedModel;
        /**
         * @var Model
         */
        $targetModel = new $class();

        return $targetModel->select($this->query)->one();
    }
}
