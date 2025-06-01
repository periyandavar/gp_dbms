<?php

namespace Database\Orm;

use Database\DBQuery;
use Database\Exception\DatabaseException;
use Database\Orm\Relation\HasMany;
use Database\Orm\Relation\HasOne;
use Database\Orm\Relation\Relation;
use JsonSerializable;
use Loader\Container;
use Validator\Field\Fields;

/**
 *
 */
abstract class Record extends Model implements JsonSerializable
{
    /**
     * Original state of the model, will have the db row results.
     *
     * @var array
     */
    protected $original_state = [];
    protected static $db;
    protected DBQuery $dbQuery;

    private $with_models = [];

    /**
     * Events array
     *
     * @var array
     */
    private $events = [];

    /**
     * Flag to enable or disable trigger event.
     *
     * @var bool
     */
    private $is_trigger_event = false;

    /**
     * Flag to check if the model is loaded by ORM.
     *
     * @var bool
     */
    private $is_loaded_by_orm = false;

    private $relations = [];

    /**
     * Set/unset the trigger event flag.
     *
     * @param bool $_is_trigger_event
     */
    public function setTriggerEvent(bool $_is_trigger_event)
    {
        $this->is_trigger_event = $_is_trigger_event;
    }

    public function isTriggerEvent(): bool
    {
        return $this->is_trigger_event;
    }

    /**
     * Save the model, insert the record if it does not exist, update the record if it exists and supports dirty update.
     *
     * @param bool $_is_dirty_update
     */
    public function save($_is_dirty_update = true)
    {
        $field_values = $this->toDbRow();

        [$key, $value] = $this->getUniqueId();

        $db = static::getDb();
        $db->reset();

        if (! $value) {
            return $this->insert($field_values);
        }

        if ($_is_dirty_update) {
            $updated_fields = array_diff($field_values, $this->original_state);
            if (empty($updated_fields)) {
                return true;
            }

            return $this->update($updated_fields, "$key = '$value'");
        }

        return $this->update($field_values, "$key = '$value'");
    }

    /**
     * Return the fields that should be skipped while updating the model.
     *
     * @return string[]
     */
    public function skipUpdateOn()
    {
        return [
            $this->getUniqueKey(),
        ];
    }

    /**
     * Filter the update fields, remove the fields that should be skipped while updating the model.
     *
     * @param array $fields
     *
     * @return array
     */
    public function filterUpdateFields(array $fields)
    {
        $skipUpdateOn = $this->skipUpdateOn();
        foreach ($fields as $key => $value) {
            if (in_array($key, $skipUpdateOn)) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }

    /**
     * Get the unique id of the model.
     *
     * @return array
     */
    public function getUniqueId()
    {
        $unique_id = static::getUniqueKey();

        return [
            $unique_id,
            $this->$unique_id,
        ];
    }

    /**
     * Get the unique key of the model. by default it is 'id'. override this method to set the unique key.
     *
     * @return string
     */
    public static function getUniqueKey()
    {
        return 'id';
    }

    /**
     * Update the model.
     *
     * @param array  $_data
     * @param string $_where
     */
    private function update($_data, $_where)
    {
        $db = static::getDb();
        $db->reset();

        $this->triggerEvent(Events::EVENT_BEFORE_SAVE);
        $this->triggerEvent(Events::EVENT_BEFORE_UPDATE);
        $result = $db->update(static::getTableName(), $_data, $_where)->execute();

        if ($result) {
            $this->triggerEvent(Events::EVENT_AFTER_SAVE);
            $this->triggerEvent(Events::EVENT_AFTER_UPDATE);
        }

        return $result;
    }

    /**
     * Insert the model.
     *
     * @param array $_data
     */
    public function insert($_data)
    {
        $db = static::getDb();
        $db->reset();

        foreach ($_data as $key => $value) {
            if (in_array($key, $this->skipInsertOn())) {
                unset($_data[$key]);
            }
        }

        $this->triggerEvent(Events::EVENT_BEFORE_SAVE);
        $this->triggerEvent(Events::EVENT_BEFORE_INSERT);
        $result = $db->insert($this->getTableName(), $_data)->execute();

        if ($result) {
            $key = static::getUniqueKey();
            $this->$key = $db->insertId();
            $this->triggerEvent(Events::EVENT_AFTER_SAVE);
            $this->triggerEvent(Events::EVENT_AFTER_INSERT);
        }

        return $result;
    }

    /**
     * Get the database instance, for the model.
     *
     * @return \Database\Database
     */
    protected static function getDb()
    {
        static::$db = static::$db ?? Container::get('db');

        return static::$db;
    }

    /**
     * Get the table name of the model. by default it is the class name. override this method to set the table name.
     *
     * @return string
     */
    public static function getTableName()
    {
        return basename(str_replace('\\', '/', static::class));
    }

    /**
     * Delete the model. based on the unique key.
     *
     * @return bool
     */
    public function delete()
    {
        [$key, $value] = $this->getUniqueId();
        $use_delete = $this->useDelete();
        if (!(empty($use_delete))) {
            return $this->update($use_delete, "$key = $value");
        }
        $db = static::getDb();

        if (empty($value)) {
            return false;
        }
        $db->delete(static::getTableName(), "$key = $value");

        return $db->execute();
    }

    /**
     * Find a record.
     *
     * @param mixed $_identifier
     *
     * @return static|null
     */
    public static function find($_identifier)
    {
        $db = static::getDb();
        $query = new DBQuery();
        if ($_identifier instanceof DBQuery) {
            $query = $_identifier;
        } else {
            $key = static::getUniqueKey();
            $query->where($key, '=', $_identifier);
        }

        $query->selectAll(false)->from(static::getTableName());
        $result = $db->setDbQuery($query)->getOne();
        if (! $result) {
            return null;
        }

        $model = self::getModel($result);

        $model->setIsLoadedByOrm(true);

        return $model;
    }

    /**
     * Get the model from the db result array.
     *
     * @param mixed $result
     *
     * @return static
     */
    private static function getModel($result)
    {
        $result = (array) $result;

        return static::loadFromDbRow($result);
    }

    /**
     * Handle with
     *
     * @param static[] $models
     * @param Record   $ref
     *
     * @return array
     */
    private static function loadWith(array $models, Record $ref)
    {
        $updatedModels = $models;

        if (empty($models)) {
            return $updatedModels;
        }

        $withModels = $ref->getWithModels();

        if (empty($withModels)) {
            return $updatedModels;
        }

        foreach ($withModels as $with) {
            if (method_exists($ref, $with)) {
                self::handleRelation($ref->$with(), $updatedModels, $with);
            }
        }

        return $updatedModels;
    }

    /**
     * Handle relations
     *
     * @param \Database\Orm\Relation\Relation $relation
     * @param static[]                        $models
     *
     * @return array
     */
    private static function handleRelation(Relation $relation, $models, string $with)
    {
        $isHasMany = $relation instanceof HasMany;
        $updatedModels = $models;
        $fk = $relation->getForeignKey();
        $pk = $relation->getPrimaryKey();
        $key1 = $isHasMany ? $pk : $fk;
        $key2 = $isHasMany ? $fk : $pk;
        $fkValues = array_map(function($model) use ($key1) {
            return $model->$key1;
        }, $updatedModels);

        $fkValues = implode(',', $fkValues);
        $relationClass = $relation->getRelatedModel();

        $dbQuery = $relation->getDbQuery()->where(" {$key2} IN ($fkValues) ");
        $result = false;
        $result = (new $relationClass())->setDbQuery($dbQuery)->with($relation->getWithModels())->all();

        if (! $result) {
            return $updatedModels;
        }

        return array_map(function($model) use ($result, $with, $key1, $key2, $isHasMany) {
            $res = array_values(array_filter($result, function($m) use ($key2, $key1, $model) {
                return $model->$key1 === $m->$key2;
            }));
            $model->$with = $isHasMany ? $res : reset($res);
        }, $updatedModels);
    }

    /**
     * Load the model from the database row.
     *
     * @param array $_db_row
     *
     * @return static
     */
    public static function loadFromDbRow($_db_row)
    {
        $calledClass = get_called_class();
        /**
         * @var static
         */
        $object = new $calledClass();
        $model = $object->fromDbRow($_db_row);
        $model->original_state = $model->toDbRow();
        $model->triggerEvent(Events::EVENT_AFTER_LOAD);

        return $model;
    }

    /**
     * Define how the model needs to be load, by default it will load the model assuming the fields keys in the db row as the property of the class,
     * override this method to load the model in a different way.
     *
     * @param array $_data
     *
     * @return static
     */
    public function fromDbRow(array $_data)
    {
        foreach ($_data as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    /**
     * Convert the model to the database row. by default it will convert the model to the array of properties of the class,
     * override this method to convert the model in a different way.
     *
     * @return array
     */
    public function toDbRow()
    {
        if (!empty($this->attr)) {
            return $this->attr;
        }
        $result = [];
        // Get the reflection of the current class
        $reflection = new \ReflectionClass($this);

        // Get all properties of the child class
        $childProperties = $reflection->getProperties();

        // Get properties of the parent class
        $parentProperties = $reflection->getParentClass()->getProperties();

        // Create an array of parent property names for quick lookup
        $parentPropertyNames = array_flip(array_map(function($prop) {
            return $prop->getName();
        }, $parentProperties));

        // Filter child properties to exclude those in the parent class
        $properties = array_filter($childProperties, function($property) use ($parentPropertyNames) {
            return ! isset($parentPropertyNames[$property->getName()]);
        });

        foreach ($properties as $property) {
            $property->setAccessible(true); // Make private/protected properties accessible
            $result[$property->getName()] = $property->getValue($this);
        }

        return $result;
    }

    /**
     * @return static
     */
    public static function select(?DBQuery $query = null)
    {
        $calledClass = get_called_class();
        $dbQuery = $query ?? (new DBQuery());
        $dbQuery->selectAll(false)->from(static::getTableName());

        $model = new $calledClass();
        $model->setDbQuery($dbQuery);

        return $model;
    }

    /**
     * Get the first record based on the db query.
     *
     * @return static|null
     */
    public function one()
    {
        $db = static::getDb();

        $result = $db->setDbQuery($this->dbQuery)->getOne();
        if (! $result) {
            return null;
        }
        $model = self::getModel($result);
        $model->setIsLoadedByOrm(true);

        self::loadWith([$model], $this);

        return $model;
    }

    /**
     * Get all records based on the db query.
     *
     * @return static[]
     */
    public function all()
    {
        $db = static::getDb();
        $result = $db->setDbQuery($this->dbQuery)->getAll();
        if (! $result) {
            return [];
        }

        $models = [];
        foreach ($result as $row) {
            $row = (array) $row;
            $model = static::loadFromDbRow($row);
            $model->setIsLoadedByOrm(true);
            $models[] = $model;
        }

        self::loadWith($models, $this);

        return $models;
    }

    /**
     * Update all records based on the fields and where condition.
     *
     * @param array $fields
     * @param mixed $where
     * @param mixed $join
     *
     * @return bool
     */
    public static function updateAll(array $fields, $where = null, $join = null)
    {
        $db = static::getDb();
        $dbQuery = new DBQuery();
        $dbQuery->update(static::getTableName(), $fields, $where, $join);

        return $db->setDbQuery($dbQuery)->execute();
    }

    /**
     * Delete all records based on the where condition.
     *
     * @param mixed $where
     *
     * @return bool
     */
    public static function deleteAll($where = null)
    {
        $db = static::getDb();
        $dbQuery = new DBQuery();
        $dbQuery->delete(static::getTableName(), $where);

        return $db->setDbQuery($dbQuery)->execute();
    }

    /**
     * Set the db query for the model.
     *
     * @param DBQuery $query
     *
     * @return $this
     */
    public function setDbQuery(DBQuery $query)
    {
        $this->dbQuery = $query;

        return $this;
    }

    /**
     * Find all records.
     *
     * @param mixed $_query
     *
     * @return static[]
     */
    public static function findAll($_query = null)
    {
        $db = static::getDb();
        $query = new DBQuery();
        if ($_query instanceof DBQuery) {
            $query = $_query;
        }
        $query->selectAll(false)->from(static::getTableName())->getQuery();
        $db->setDbQuery($query);
        $result = $db->getAll();

        if (!$result) {
            return [];
        }

        if (is_object($result)) {
            $result = (array) $result;
        }

        $models = [];
        foreach ($result as $row) {
            $row = (array) $row;
            $model = static::loadFromDbRow($row);
            $model->setIsLoadedByOrm(true);
            $models[] = $model;
        }

        return $models;
    }

    /**
     * Set the events.
     *
     * @param array $_events
     */
    protected function setEvents(array $_events)
    {
        $this->events = $_events;
    }

    /**
     * Get the event.
     *
     * @param string $_event
     *
     * @return mixed
     */
    public function getEvent(string $_event)
    {
        return $this->events[$_event] ?? null;
    }

    /**
     * Trigger the event.
     *
     * @param string $_event
     */
    public function triggerEvent(string $_event)
    {
        // check if the event is trigger is enabled.
        if (! $this->is_trigger_event) {
            return;
        }

        $event = $this->getEvent($_event);

        // check if the event is set.
        if (empty($event)) {
            return;
        }

        // check if the event is callable, if yes call the event.
        if (is_callable($event)) {
            $event($this);

            return;
        }

        // check if the event is Event child class, call the handle method.
        if ($event instanceof Events) {
            $event->handle($this);

            return;
        }

        // check if the event is string (the event class name), we need to resolve that with container and call the handle method.
        if (is_string($event)) {
            $event = Container::resolve($event);
            $this->events[$_event] = $event;
            $event->handle($this);

            return;
        }
    }

    /**
     * Get the model is loaded by orm or not.
     *
     * @return bool
     */
    public function getIsLoadedByOrm()
    {
        return $this->is_loaded_by_orm;
    }

    /**
     * Set the model is loaded by orm or not.
     *
     * @param bool $_is_loaded_by_orm
     */
    public function setIsLoadedByOrm(bool $_is_loaded_by_orm)
    {
        $this->is_loaded_by_orm = $_is_loaded_by_orm;
    }

    /**
     * Magic method to handle dynamic property access.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (method_exists($this, $name)) {
            $this->relations[$name] = $this->$name()->handle();
        }

        return $this->attr[$name] ?? $this->relations[$name] ?? null;
    }

    /**
     * Returns the relation object by name.
     *
     * @param mixed $name
     *
     * @return HasMany|HasOne|mixed|null
     */
    public function getRelation($name)
    {
        return $this->relations[$name] ?? null;
    }

    /**
     * Check if the relation exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isRelation($name)
    {
        return $this->getRelation($name) != null;
    }

    /**
     * Magic method to handle dynamic property setting.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        if (method_exists($this, $name)) {
            $this->relations[$name] = $value;

            return;
        }

        $this->attr[$name] = $value;
    }

    /**
     * Magic method to handle dynamic method calls.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     * @throws DatabaseException
     */
    public function __call($method, $arguments)
    {
        $db = static::getDb();
        if (method_exists($db, $method)) {
            // Delegate the method call to the $b instance
            $result = call_user_func_array([$db, $method], $arguments);
            if ($result instanceof DBQuery) {
                return $this;
            }

            return $result;
        } else {
            throw new DatabaseException("Method $method not found", DatabaseException::UNKNOWN_METHOD_CALL_ERROR, null, ['method' => $method, 'class' => self::class]);
        }
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param string       $relatedModelClass
     * @param string       $foreignKey
     * @param string       $primaryKey
     * @param DBQuery|null $query
     *
     * @return HasOne
     */
    public function hasOne(string $relatedModelClass, string $foreignKey, string $primaryKey, ?DBQuery $query = null)
    {
        $backtrace = debug_backtrace();
        $name = $backtrace[1]['function'] ?? null;

        $hasOne = new HasOne($this, $primaryKey, $relatedModelClass, $foreignKey, $query);
        $this->relations[$name] = $hasOne;

        return $hasOne;
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param string       $relatedModelClass
     * @param string       $foreignKey
     * @param string       $primaryKey
     * @param DBQuery|null $query
     *
     * @return HasMany
     */
    public function hasMany(string $relatedModelClass, string $foreignKey, string $primaryKey, ?DBQuery $query = null)
    {
        $hasMany = new HasMany($this, $primaryKey, $relatedModelClass, $foreignKey, $query);
        $backtrace = debug_backtrace();
        $name = $backtrace[1]['function'] ?? null;

        $this->relations[$name] = $hasMany;

        return $hasMany;
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

    /**
     * Get the eager loading models.
     *
     * @return array
     */
    public function getWithModels()
    {
        return $this->with_models;
    }

    /**
     * Get the relations of the model.
     *
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Reload the model from the database.
     *
     * @return static|null
     */
    public function reload()
    {
        [$key, $value] = $this->getUniqueId();

        return self::find($value);
    }

    /**
     * Return the fields that should be skipped while inserting the model.
     *
     * @return string[]
     */
    public function skipInsertOn()
    {
        return [];
    }

    /**
     * Set the values of the model.
     *
     * @param array $values
     *
     * @return void
     */
    public function setValues(array $values)
    {
        foreach ($values as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Return the values of the model's attributes.
     *
     * @return array
     */
    public function getValues()
    {
        return $this->toDbRow();
    }

    /**
     * Return the fields that should be used for soft delete.
     * by default it returns an empty array, meaning it performs a hard delete.
     *
     * @return array
     */
    public function useDelete()
    {
        return [];
    }
}
