<?php

namespace TS\ezDB\Models;

use ReflectionClass;
use TS\ezDB\Connections;
use TS\ezDB\Exceptions\ModelMethodException;
use TS\ezDB\Query\Builder;

abstract class Model
{
    use Relationship;

    /**
     * @var string The connection to use. Default name is default ;)
     */
    protected $connectionName = "default";

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
     * @var Builder Builder class to use for new queries.
     */
    protected $builderClass;

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
        return $this->getBuilder()->setModel($this)->$method(...$parameters);
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
        return $instance->getBuilder()
            ->setModel($instance)
            ->$method(...$parameters);
    }

    /**
     * Magic method for accessing attributes and also relations
     * @param string $column The column name
     * @return mixed
     * @throws ModelMethodException
     */
    public function __get($column)
    {
        return $this->getAttribute($column);
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

    public function __isset($key)
    {
        return (array_key_exists($key, $this->data)) ||
            isset($this->with[$key]) ||
            isset($this->relations[$key]);
    }

    /**
     * Get Table Name. If table name is not set then it generates a table name using the class name.
     * The generated results are not perfect so it is recommended to set a table name.
     * @return string
     */
    public function getTable(): string
    {
        if ($this->table == '') {
            //Regex from https://stackoverflow.com/a/19533226/3126835
            trim(
                preg_replace(
                    '/[A-Z]([A-Z](?![a-z]))*/',
                    '_\L$0',
                    strrchr(get_class($this), '\\')
                ),
                '\_'
            );
        }
        return $this->table;
    }

    public static function getTableName()
    {
        return (new static())->getTable();
    }

    public function hasPrimaryKey()
    {
        return $this->primaryKey !== false;
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
        return $this->getTable() . '_' . $this->getPrimaryKey();
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

    public function getBuilder()
    {
        if ($this->builderClass == null) {
            $this->builderClass = Connections::connection($this->connectionName)->getBuilderClass();
        }
        $builder = new ReflectionClass($this->builderClass);
        return $builder->newInstanceArgs([Connections::connection($this->connectionName)]);
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
     * Get the the data attributes from the model.
     *
     * @param string|null $column
     * @return array|mixed
     */
    public function getAttribute($column = null)
    {
        if ($column == null) {
            return $this->data;
        }

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
            $builder = $this->$column();

            if ($builder == null) {
                throw new ModelMethodException(
                    "The $column() function returned null. Make sure the function returns a proper RelationshipBuilder"
                );
            }

            $this->relations[$column] = $builder->get();
            return $this->relations[$column];
        }

        return null;
    }

    /**
     * Set the data attributes.
     *
     * Use this function instead if you do not want to use the magic methods.
     *
     * @param array|string $key
     * @param mixed $value
     */
    public function setAttribute($key, $value)
    {
        $this->data[$key] = $value;
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
            if (
                !isset($this->original) ||
                !array_key_exists($column, $this->original) ||
                $this->original[$column] !== $value
            ) {
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
     * @param array $eagerLoad
     * @return array
     * @throws ModelMethodException
     */
    public static function createFromResult($results, $eagerLoad = [])
    {
        $r = [];
        if (!empty($results)) {
            foreach ($results as $result) {
                $i = new static();
                $i->setResult((array)$result);
                $r[] = $i;
            }
        }

        if (!empty($eagerLoad)) {
            return self::eagerLoadRelations($r, $eagerLoad);
        }

        return $r;
    }

    /**
     * @param $models
     * @param array $eagerLoad
     * @return array
     * @throws ModelMethodException
     */
    public static function eagerLoadRelations($models, $eagerLoad = [])
    {
        foreach ($eagerLoad as $name) {
            $instance = new static();
            if (!method_exists($instance, $name)) {
                throw new ModelMethodException(
                    $name . "() Relation not found in model and cannot be eager loaded."
                );
            }
            /** @var RelationshipBuilder $relationshipBuilder */
            $relationshipBuilder = $instance->$name();

            if ($relationshipBuilder == null) {
                throw new ModelMethodException(
                    "The $name() function returned null. " .
                    "Make sure the function returns a proper RelationshipBuilder"
                );
            }

            $foreignKey = $relationshipBuilder->getForeignKey();
            $localKey = $relationshipBuilder->getLocalKey();
            $localKeyValues = self::pluck($models, $localKey);

            //If it is hasOne or belongsTo then by default the builder will only return the first row.
            $fetchFirst = $relationshipBuilder->getFetchFirst(); //We need this value later.
            $relationshipBuilder->setFetchFirst(false); //Set fetchFirst to false so builder fetches all values.

            $relatedModels = $relationshipBuilder
                ->setForeignKeyValue($localKeyValues)
                ->get();

            /** @var Model $model */
            foreach ($models as $model) {
                $with = [];
                foreach ($relatedModels as $relatedModel) {
                    if ($model->$localKey == $relatedModel->$foreignKey) {
                        $with[] = $relatedModel;
                    }
                }
                if ($fetchFirst) {
                    //If fetchFirst is true then set the first row directly.
                    $with = $with[0] ?? $with;
                    $model->setEagerLoaded($name, $with);
                } else {
                    $model->setEagerLoaded($name, $with);
                }
            }
        }
        return $models;
    }

    public static function pluck($array, $key)
    {
        $result = [];

        foreach ($array as $a) {
            $result[] = $a->$key;
        }

        return $result;
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
     * Set an alias. Returns a new builder instance
     *
     * @param $alias
     * @return Builder
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public static function as($alias)
    {
        $instance = new static();
        $instance->table = $instance->getTable() . ' as ' . $alias;
        return $instance->getBuilder()->setModel($instance);
    }

    /**
     * Get instance of the builder for a new query.
     *
     * @return Builder
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public static function newQuery()
    {
        $instance = new static();
        return $instance->getBuilder()->setModel($instance);
    }

    /**
     * Find model by using primary key
     * @param $id
     * @return Model|$this
     */
    public static function find($id)
    {
        $instance = new static();
        return $instance->where($instance->getPrimaryKey(), '=', $id)->first();
    }

    public function save()
    {
        $dirty = $this->getDirty();
        $builder = $this->getBuilder()->setModel($this);

        if (count($dirty) == 0) {
            return true;
        }

        if ($this->exists()) {
            if (!$this->hasPrimaryKey()) {
                throw new ModelMethodException("save() function only works when there is a primary key.");
            }

            if (!isset($this->original[$this->getPrimaryKey()])) {
                throw new ModelMethodException("save() function only works if you have retrieved the primary key into the model.");
            }

            $builder->where($this->getPrimaryKey(), '=', $this->original[$this->getPrimaryKey()]);

            foreach ($dirty as $column) {
                $builder->set($column, $this->data[$column]);
            }

            $saved = $builder->update();
        } else {
            $saved = $builder->insert($this->data);
            //Check if the primary key is not already set by the user, and there is also a primary key
            if ($this->hasPrimaryKey() && !isset($this->data[$this->getPrimaryKey()])) {
                $this->data[$this->getPrimaryKey()] = $builder->getConnection()->getDriver()->getLastInsertId();
            }
        }

        $this->original = $this->data;

        return $saved;
    }
}
