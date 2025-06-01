<?php

namespace Database\Orm\Relation;

use Database\Orm\Record;

class HasOne extends Relation
{
    public function handle()
    {
        $primarykey = $this->primaryKey;
        $this->query->where("{$this->foreignKey} = {$this->model->$primarykey}");

        $class = $this->relatedModel;
        /**
         * @var Record
         */
        $targetModel = new $class();

        return $targetModel->select($this->query)->with($this->with_models)->one();
    }
}
