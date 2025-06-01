<?php

namespace Database\Orm;

use JsonSerializable;
use Validator\Field\Field;
use Validator\Field\Fields;

/**
 *
 */
abstract class Model implements JsonSerializable
{
    protected $attr = [];
    protected $fields;

    /**
     * Constructor initializes the model and sets up the fields
     */
    public function __construct()
    {
        $this->setField();
    }

    /**
     * Magic method to handle dynamic property access
     *
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->attr[$name] ?? null;
    }

    /**
     * Magic method to handle dynamic property setting
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $this->attr[$name] = $value;
    }

    /**
     * Set values for the model's attributes
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
     * Validate the model's fields
     *
     * @return bool
     */
    public function validate()
    {
        $this->fields->setValues($this->getValues());

        return $this->fields->validate();
    }

    /**
     * Returns the values of the model's attributes
     *
     * @return array
     */
    public function getValues()
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
     * Returns the errors on the model's fields
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->fields->getErrors();
    }

    /**
     * Returns the first error on the model's fields
     *
     * @return string|null
     */
    public function getError()
    {
        return $this->fields->getError();
    }

    /**
     * Returns the rules for validation
     *
     * @return array
     */
    public function getRules()
    {
        return [];
    }

    /**
     * set the attributes of the model as fields
     *
     * @return void
     */
    private function setField()
    {
        $rules = $this->getRules();
        $this->fields = new Fields();
        foreach ($rules as $name => $rule) {
            $rules = array_shift($rule);
            $messages = array_shift($rule) ?? [];
            $this->fields->addField(new Field($name, $this->$name, $rules, $messages));
        }
    }

    /**
     * serialize the model to an array
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = $this->toResponse();
        $data = $this->serialize($data);

        return $data;
    }

    /**
     * Serialize the model's data recursively
     *
     * @param array $data
     *
     * @return array
     */
    public function serialize(array $data)
    {
        foreach ($data as $key => $value) {
            if ($value instanceof Model) {
                $data[$key] = $this->serialize($value->toResponse());
            }
            if (is_array($value)) {
                $data[$key] = $this->serialize($value);
            }
        }

        return $data;
    }

    /**
     * Get the values of the model for response
     *
     * @return array
     */
    public function toResponse()
    {
        return $this->getValues();
    }
}
