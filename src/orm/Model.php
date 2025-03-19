<?php

namespace Database\Orm;

use Database\DatabaseFactory;
use Database\DBQuery;
use Loader\Container;

abstract class Model
{
    /**
     * Original state of the model, will have the db row results.
     *
     * @var array
     */
    protected $original_state = [];
    protected static $db;
    protected $attr = [];

    protected DBQuery $query;

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

    /**
     * Set/unset the trigger event flag.
     *
     * @param bool $_is_trigger_event
     */
    public function setTriggerEvent(bool $_is_trigger_event)
    {
        $this->is_trigger_event = $_is_trigger_event;
    }

    /**
     * Save the model, insert the record if it does not exist, update the record if it exists and supports dirty update.
     *
     * @todo Currently the model support save based on the unique key, the model should be updated to support the update based on db query.
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
            // var_export($field_values);exit;
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
     * @param array $_data
     * @param array $_where
     */
    public function update($_data, $_where)
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

        $this->triggerEvent(Events::EVENT_BEFORE_SAVE);
        $this->triggerEvent(Events::EVENT_BEFORE_INSERT);

        $result = $db->insert($this->getTableName(), $_data)->execute();

        if ($result) {
            $this->triggerEvent(Events::EVENT_AFTER_SAVE);
            $this->triggerEvent(Events::EVENT_AFTER_INSERT);
        }

        return $result;
    }

    /**
     * Get the database instance, for the model.
     *
     * @return \System\Database\Database
     */
    protected static function getDb()
    {
        if (! static::$db) {
            global $dbConfig;
            self::$db = DatabaseFactory::create($dbConfig);
        }

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
        // return class_basename(static::class);
    }

    /**
     * Delete the model. based on the unique key.
     */
    public function delete()
    {
        $db = static::getDb();
        [$key, $value] = $this->getUniqueId();

        if (empty($value)) {
            return;
        }
        $table_name = static::getTableName();
        $db->delete(static::getTableName(), "$key = $value");
        // $sql = "DELETE FROM {} WHERE {$db->escape($key)} = '{$db->escape($value)}'";

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

        $query->selectAll(false)->from(self::getTableName());
        $result = $db->setDbQuery($query)->getOne();
        if (! $result) {
            return null;
        }

        $model = static::loadFromDbRow($result);
        $model->setIsLoadedByOrm(true);

        return $model;
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
         * @var Model
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
    public static function fromDbRow($_data)
    {
        $calledClass = get_called_class();
        /**
         * @var Model
         */
        $model = new $calledClass();

        foreach ($_data as $key => $value) {
            $model->$key = $value;
        }

        return $model;
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
            var_export($this->attr);

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
        $query->selectAll(false)->from(self::getTableName())->getQuery();
        $db->setDbQuery($query);
        $result = $db->getAll();

        if (!$result) {
            return [];
        }

        // if (is_object($result)) {
        // $result = (array) $result;
        // }

        $models = [];
        foreach ($result as $row) {
            $row = (array) $row;
            $model = static::loadFromDbRow($row);
            // var_export($model->id);exit;
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

    public function __get($name)
    {
        // var_export(['get' => $name]);
        return $this->attr[$name] ?? null;
    }

    public function __set($name, $value)
    {
        // var_export(['set' =>[ $name, $value]]);
        $this->attr[$name] = $value;
    }
}
