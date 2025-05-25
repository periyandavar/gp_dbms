<?php

namespace Database\Orm;

abstract class Events
{
    public const EVENT_BEFORE_SAVE = 'before_save';
    public const EVENT_AFTER_SAVE = 'after_save';
    public const EVENT_BEFORE_DELETE = 'before_delete';
    public const EVENT_AFTER_DELETE = 'after_delete';
    public const EVENT_BEFORE_INSERT = 'before_insert';
    public const EVENT_AFTER_INSERT = 'after_insert';
    public const EVENT_BEFORE_UPDATE = 'before_update';
    public const EVENT_AFTER_UPDATE = 'after_update';
    public const EVENT_BEFORE_LOAD = 'before_load';
    public const EVENT_AFTER_LOAD = 'after_load';

    abstract public function handle(Model $_model);
}
