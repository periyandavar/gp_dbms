<?php

namespace Database\Orm\Relation;

use Database\DBQuery;
use Database\Orm\Model;

class HasMany extends Relation
{
    public function handle()
    {
        $query = $this->query ?? new DBQuery();
        $foriegnKey = $this->foreignKey;
        $query->where([$this->primaryKey = $this->model->$foriegnKey]);

        if (get_parent_class($this->relatedModel) != Model::class) {
            return [];
        }

        /**
         * @var Model
         */
        $targetModel = new ${$this->relatedModel}();

        return $targetModel->select($this->query)->all();
    }
}
