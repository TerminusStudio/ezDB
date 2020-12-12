<?php

namespace TS\ezDB\Models;

use TS\ezDB\Connections;
use TS\ezDB\Query\Builder;

abstract class Model
{
    /**
     * @var string The connection to use. Default name is default ;)
     */
    protected $connection = "default";

    /**
     * @var string The table name
     */
    protected $table = '';

    /**
     * @var string|array The primary key of the table
     * TODO: Support for composite keys
     */
    protected $primaryKey = 'id';

    /**
     * @var bool Is auto increment enabled?
     */
    protected $autoIncrement = false;

    /**
     * @var bool Timestamps will automatically be managed. Create two extra columns: created_at and updated_at
     */
    protected $timestamps = false;

    /**
     * Column name for created_at
     */
    public const CREATED_AT = "created_at";

    /**
     * Column name for updated_at
     */
    public const UPDATED_AT = "updated_at";


    /**
     * Magic method linking to a new builder and sets the table name.
     * @param $method
     * @param $parameters
     * @return mixed
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function __call($method, $parameters)
    {
        return (new Builder(Connections::connection($this->connection)))->setModel($this)->$method(...$parameters);
    }

    /**
     * Magic method linking to a new builder and sets the table name.
     * @param $method
     * @param $parameters
     * @return mixed
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static();
        return (new Builder(Connections::connection($instance->connection)))
            ->setModel($instance)
            ->$method(...$parameters);
    }

    /**
     * Get Table Name
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Timestamps
     */
    public function hasTimestamps()
    {
        return $this->timestamps;
    }

    public function getCreatedAt()
    {
        return self::CREATED_AT;
    }

    /**
     * Find model by using primary key
     * @param $id
     * @return mixed
     */
    public static function find($id)
    {
        $instance = new static();
        return $instance->where($instance->getPrimaryKey(), '=', $id)->first();
    }
}
