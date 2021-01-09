<?php

namespace TS\ezDB\Models;

use TS\ezDB\Connections;
use TS\ezDB\Exceptions\ModelMethodException;
use TS\ezDB\Query\Builder;

abstract class Model
{
    use Relationship;

    /**
     * @var string The connection to use. Default name is default ;)
     */
    protected $connection = "default";

    /**
     * @var string The table name
     */
    protected $table = '';

    /**
     * @var string The primary key of the table
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
     * @var array Contains the row fetched from db
     */
    protected $data;

    /**
     * @var array Contains the original row fetched from db. This will only be updated when db is updated.
     */
    protected $original;

    /**
     * Column name for created_at
     */
    public const CREATED_AT = 'created_at';

    /**
     * Column name for updated_at
     */
    public const UPDATED_AT = 'updated_at';

    /**
     * Create a new instance of the model. Each instance of the model is a single row.
     *
     * @param array $data An associative array containing the values of the row.
     */
    public function __construct($data = [])
    {
        $this->data = $data;
    }

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
     * Magic method for accessing attributes and also relations
     * @param string $column The column name
     * @return mixed
     */
    public function __get($column)
    {
        //Check if key exists in data array.
        if (array_key_exists($column, $this->data)) {
            return $this->data[$column];
        }

        //Next check the with array which contains all data that was loaded first.
        if (!empty($this->with) && array_key_exists($column, $this->with)) {
            return $this->with[$column];
        }

        //Finally check the relations array to see if the data was already loaded.
        if (!empty($this->relations) && array_key_exists($column, $this->relations)) {
            return $this->relations[$column];
        }

        //If the data wasn't loaded then check if the method for the column exists and load it.
        if (method_exists($this, $column)) {
            $this->relations[$column] = $this->$column()->get();
            return $this->relations[$column];
        }

        return null;
    }

    /**
     * Magic method for setting attributes
     * @param string $column The column name
     * @param string $value The value
     * @return mixed
     */
    public function __set($column, $value)
    {
        $this->data[$column] = $value;
    }

    /**
     * Get Table Name
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the primary key of a model.
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Get the foreign key of this model.
     */
    public function getForeignKey()
    {
        return strtolower(get_class($this)) . '_' . $this->getPrimaryKey();
    }

    /**
     * Return whether model has timestamps
     * @return bool
     */
    public function hasTimestamps()
    {
        return $this->timestamps;
    }

    /**
     * Get created at column name
     * @return string
     */
    public function getCreatedAt()
    {
        return self::CREATED_AT;
    }

    /**
     * Get updated at column name
     * @return string
     */
    public function getUpdatedAt()
    {
        return self::UPDATED_AT;
    }

    /**
     * Does the row exist in the database or is it newly instantiated.
     * @return bool
     */
    public function exists()
    {
        //Since original can only be set when creating the model, it means the row exists on the db.
        return isset($this->original);
    }

    /**
     * Get the data array. This only contains information about the row and nothing on the relations.
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the data attributes. This replaces the data array.
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get all the attributes which have been modified.
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];

        if (!isset($this->data)) {
            return $dirty;
        }

        foreach ($this->data as $column => $value) {
            if (!isset($this->original) ||
                !array_key_exists($column, $this->original) ||
                $this->original[$column] !== $value) {
                $dirty[] = $column;
            }
        }
        return $dirty;
    }

    /**
     * Check whether any value has been modified.
     *
     * @param null $column Check a specific column, if null checks all columns.
     * @return bool
     */
    public function isDirty($column = null)
    {
        if (isset($column)) {
            return ($this->data[$column] != $this->original[$column]);
        }
        return (count($this->getDirty()) > 0);
    }

    /**
     * Generate model array from result
     * @param mixed $results
     * @return array
     */
    public static function createFromResult($results)
    {
        $r = [];
        if (!empty($results)) {
            foreach ($results as $result) {
                $i = new static();
                $i->setResult((array)$result);
                $r[] = $i;
            }
        }
        return $r;
    }

    /**
     * Set result fetched from database. The method also sets the $original variable.
     * The method is protected and can only be accessed by this class. Only use it when getting results from the
     * database as there can be unintended side effects.
     *
     * @param $result
     */
    protected function setResult($result)
    {
        $this->data = $result;
        $this->original = $result;
    }

    /**
     * Find model by using primary key
     * @param $id
     * @return Model|object|mixed
     */
    public static function find($id)
    {
        $instance = new static();
        return $instance->where($instance->getPrimaryKey(), '=', $id)->first();
    }

    public function save()
    {
        if (!isset($this->primaryKey)) {
            throw new ModelMethodException("save() function only works when there is a primary key.");
        }

        $dirty = $this->getDirty();
        $builder = (new Builder())->setModel($this);

        if (count($dirty) == 0) {
            return true;
        }

        if ($this->exists()) {
            if (!isset($this->original[$this->primaryKey])) {
                throw new ModelMethodException("save() function only works if you have retrieved the primary key into the model.");
            }

            $builder->where($this->primaryKey, '=', $this->original[$this->primaryKey]);

            foreach ($dirty as $column) {
                $builder->set($column, $this->data[$column]);
            }

            $saved = $builder->update();
        } else {
            $saved = $builder->insert($this->data);
            $this->data[$this->primaryKey] = $builder->getConnection()->getDriver()->getLastInsertId();
        }

        $this->original = $this->data;

        return $saved;
    }
}
