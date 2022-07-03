<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Models;

use ReflectionClass;
use TS\ezDB\Connections;
use TS\ezDB\Exceptions\ModelMethodException;
use TS\ezDB\Models\Builder\ModelAwareBuilder;
use TS\ezDB\Models\Builder\RelationshipBuilder;
use TS\ezDB\Query\Builder\Builder;

abstract class Model
{
    use Relationship;

    /**
     * @var string The connection to use. Default name is default ;)
     */
    protected string $connectionName = 'default';

    /**
     * @var string The table name
     */
    protected string $table = '';

    /**
     * @var string|null The primary key of the table
     * TODO: Support for composite keys
     */
    protected ?string $primaryKey = 'id';

    /**
     * @var bool Is auto increment enabled?
     */
    protected bool $autoIncrement = false;

    /**
     * @var bool Timestamps will automatically be managed. Create two extra columns: created_at and updated_at
     * Set this to false to disable timestamps
     */
    protected bool $timestamps = true;

    /**
     * @var array Contains the row fetched from db
     */
    protected array $data;

    /**
     * @var array Contains the original row fetched from db. This will only be updated when db is updated.
     */
    protected array $original;

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
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Magic method linking to a new builder and sets the table name.
     * @param string $method
     * @param $parameters
     * @return mixed
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function __call(string $method, $parameters)
    {
        return $this->getBuilder()->setModel($this)->$method(...$parameters);
    }

    /**
     * Magic method linking to a new builder and sets the table name.
     * @param string $method
     * @param $parameters
     * @return mixed
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public static function __callStatic(string $method, $parameters)
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
    public function __get(string $column)
    {
        return $this->getAttribute($column);
    }

    /**
     * Magic method for setting attributes
     * @param string $column The column name
     * @param mixed $value The value
     */
    public function __set(string $column, mixed $value)
    {
        $this->data[$column] = $value;
    }

    public function __isset(string $key)
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

    public static function getTableName(): string
    {
        return (new static())->getTable();
    }

    public function hasPrimaryKey(): bool
    {
        return $this->primaryKey !== false;
    }

    /**
     * Get the primary key of a model.
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Get the foreign key of this model.
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->getTable() . '_' . $this->getPrimaryKey();
    }

    /**
     * Return whether model has timestamps
     * @return bool
     */
    public function hasTimestamps(): bool
    {
        return $this->timestamps;
    }

    /**
     * Get created at column name
     * @return string
     */
    public function getCreatedAt(): string
    {
        return static::CREATED_AT;
    }

    /**
     * Get updated at column name
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return static::UPDATED_AT;
    }

    /**
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function getBuilder(): ModelAwareBuilder
    {
        return new ModelAwareBuilder(Connections::connection($this->connectionName));
    }

    /**
     * Does the row exist in the database or is it newly instantiated.
     * @return bool
     */
    public function exists(): bool

    {
        //Since original can only be set when creating the model, it means the row exists on the db.
        return isset($this->original);
    }

    /**
     * Get the data array. This only contains information about the row and nothing on the relations.
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set the data attributes. This replaces the data array.
     * @param array $data
     * @return $this
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get the data attributes from the model.
     * @param string|null $column
     * @return array|mixed
     */
    public function getAttribute(?string $column = null): mixed
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

            if (!$builder instanceof RelationshipBuilder) {
                throw new ModelMethodException(
                    "The $column() function did not return a proper instance of RelationshipBuilder"
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
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get all the attributes which have been modified.
     * @return array
     */
    public function getDirty(): array
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
     * @param string|null $column Check a specific column, if null checks all columns.
     * @return bool
     */
    public function isDirty(?string $column = null): bool
    {
        if (isset($column)) {
            return ($this->data[$column] != $this->original[$column]);
        }
        return (count($this->getDirty()) > 0);
    }

    /**
     * Generate model array from result
     * @param array $results
     * @param array $eagerLoad
     * @return $this[]
     * @throws ModelMethodException
     */
    public static function createFromResult(array $results, array $eagerLoad = []): array
    {
        $r = [];
        if (!empty($results)) {
            foreach ($results as $result) {
                $i = new static();
                $i->setResult((array)$result);
                $r[] = $i;
            }

            if (!empty($eagerLoad)) {
                return static::eagerLoadRelations($r, $eagerLoad);
            }
        }

        return $r;
    }

    /**
     * @param static[] $models
     * @param array $eagerLoad
     * @return $this[]
     * @throws ModelMethodException
     */
    public static function eagerLoadRelations(array $models, array $eagerLoad = []): array
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

            if (!$relationshipBuilder instanceof RelationshipBuilder) {
                throw new ModelMethodException("The $name() function did not return an instance of RelationshipBuilder. ");
            }

            $foreignKey = $relationshipBuilder->getForeignKey();
            $localKey = $relationshipBuilder->getLocalKey();
            $localKeyValues = static::pluck($models, $localKey);

            //If it is hasOne or belongsTo then by default the builder will only return the first row.
            $fetchFirst = $relationshipBuilder->getFetchFirst(); //We need this value later.
            $manyToMany = $relationshipBuilder->getManyToMany();

            //Set fetchFirst to false so builder fetches all values. This is to load more than one row at once for multiple models.
            $relationshipBuilder->setFetchFirst(false);

            $relatedModels = $relationshipBuilder
                ->setForeignKeyValue($localKeyValues)
                ->get();

            foreach ($models as $model) {
                $with = [];

                if (!$manyToMany) {
                    foreach ($relatedModels as $relatedModel) {
                        if ($model->$localKey == $relatedModel->$foreignKey) {
                            $with[] = $relatedModel;
                        }
                    }
                } else {
                    /**
                     * @var Model $relatedModel
                     */
                    foreach ($relatedModels as $relatedModel) {
                        $pivot = $relatedModel->getAttribute($relationshipBuilder->getAs());
                        if ($model->$localKey == $pivot->$foreignKey) {
                            $with[] = $relatedModel;
                        }
                    }
                }

                if ($fetchFirst) {
                    //If fetchFirst is true then set the first row directly.
                    $with = $with[0] ?? $with;
                }
                $model->setEagerLoaded($name, $with);
            }
        }
        return $models;
    }

    /**
     * @param array $array
     * @param string $key
     * @return array
     */
    public static function pluck(array $array, string $key): array
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
    protected function setResult(array $result): void
    {
        $this->data = $result;
        $this->original = $result;
    }

    /**
     * Set an alias. Returns a new builder instance
     *
     * @param string $alias
     * @return ModelAwareBuilder
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public static function as(string $alias): ModelAwareBuilder
    {
        $instance = new static();
        $instance->table = $instance->getTable() . ' as ' . $alias;
        return $instance->getBuilder()->setModel($instance);
    }

    /**
     * Get instance of the builder for a new query.
     *
     * @return ModelAwareBuilder
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public static function newQuery(): ModelAwareBuilder
    {
        $instance = new static();
        return $instance->getBuilder()->setModel($instance);
    }

    /**
     * Find model by using primary key
     * @param string|int|float $id
     * @return $this
     * @throws \TS\ezDB\Exceptions\ConnectionException
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public static function find(string|int|float $id): static
    {
        $instance = new static();
        /**
         * @var Model $model
         */
        $model = $instance::newQuery()->where($instance->getPrimaryKey(), '=', $id)->first();
        return $model;
    }

    public function save(): mixed
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
