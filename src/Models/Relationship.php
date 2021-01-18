<?php

namespace TS\ezDB\Models;

use TS\ezDB\Connections;
use TS\ezDB\Query\RelationshipBuilder;

trait Relationship
{
    /**
     * Relationship
     *
     * Has One - One to one
     * Has Many - One to many
     * Belongs To - One to one and Many to One
     * Belong To Many - Many to Many
     *
     * For now there is no support for polymorphic relations
     */

    /**
     * @var array Contains relations that are loaded with the model
     */
    protected $with;

    /**
     * @var array Contains all the relations that are manually fetched
     */
    protected $relations;

    /**
     * Returns all relations of the model.
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Set a relation. This overrides any previous relation with same key.
     * @param $key
     * @param $data
     * @return $this
     */
    public function setRelation($key, $data)
    {
        $this->relations[$key] = $data;
        return $this;
    }

    /**
     * Used for one to one relationship. For the owner
     *
     * @param string $relation
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return RelationshipBuilder
     * @throws \TS\ezDB\Exceptions\QueryException|\TS\ezDB\Exceptions\ConnectionException
     */
    protected function hasOne($relation, $foreignKey = null, $localKey = null)
    {
        $localKey = $localKey ?? $this->getPrimaryKey();

        $foreignKey = $foreignKey ?? $this->getForeignKey();

        $builder = (new RelationshipBuilder(Connections::connection($this->connection), true))->setModel(new $relation);

        return $builder->where($foreignKey, $this->$localKey);
    }

    /**
     * Used for one to many relationship. For the owner.
     *
     * @param string $relation
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return RelationshipBuilder
     * @throws \TS\ezDB\Exceptions\QueryException|\TS\ezDB\Exceptions\ConnectionException
     */
    protected function hasMany($relation, $foreignKey = null, $localKey = null)
    {
        $localKey = $localKey ?? $this->getPrimaryKey();

        $foreignKey = $foreignKey ?? $this->getForeignKey();

        $builder = (new RelationshipBuilder(Connections::connection($this->connection)))->setModel(new $relation);

        return $builder->where($foreignKey, $this->$localKey);
    }

    /**
     * @param string $relation
     * @param string|null $foreignKey
     * @param string|null $ownerKey
     * @return RelationshipBuilder
     * @throws \TS\ezDB\Exceptions\QueryException|\TS\ezDB\Exceptions\ConnectionException
     */
    protected function belongsTo($relation, $foreignKey = null, $ownerKey = null)
    {
        $model = new $relation;

        $foreignKey = $foreignKey ?? $model->getForeignKey();

        $ownerKey = $ownerKey ?? $model->getPrimaryKey();

        $builder = (new RelationshipBuilder(Connections::connection($this->connection), true))->setModel($model);

        return $builder->where($ownerKey, $this->$foreignKey);
    }

    /**
     * @param string $relation
     * @param string|null $intermediateTable
     * @param string|null $foreignKey
     * @param string|null $relatedKey
     * @param string|null $localPrimaryKey
     * @param string|null $foreignPrimaryKey
     * @return RelationshipBuilder
     * @throws \TS\ezDB\Exceptions\QueryException|\TS\ezDB\Exceptions\ConnectionException|\TS\ezDB\Exceptions\ModelMethodException
     */
    protected function belongsToMany(
        $relation,
        $intermediateTable = null,
        $foreignKey = null,
        $relatedKey = null,
        $localPrimaryKey = null,
        $foreignPrimaryKey = null
    )
    {
        /** @var Model $model */
        $model = new $relation;

        $intermediateTable = $intermediateTable ?? $this->getIntermediateTableName($this, $model);

        $foreignKey = $foreignKey ?? $this->getForeignKey();

        $relatedKey = $relatedKey ?? $model->getForeignKey();

        $localPrimaryKey = $localPrimaryKey ?? $this->getPrimaryKey();

        $foreignPrimaryKey = $foreignPrimaryKey ?? $model->getPrimaryKey();

        $builder = (new RelationshipBuilder(Connections::connection($this->connection), false, true))->setModel($model);


        return
            $builder
                ->withPivot($foreignKey, $relatedKey) //So they are both retrieved to the pivot
                ->join(
                    $intermediateTable,
                    $this->parseAttributeName($model->getTable(), $foreignPrimaryKey),
                    '=',
                    $this->parseAttributeName($intermediateTable, $relatedKey)
                )->where(
                    $this->parseAttributeName($intermediateTable, $foreignKey),
                    '=',
                    $this->$localPrimaryKey
                );
    }

    /**
     * Generate the intermediate table name using the Model classes.
     *
     * @param $class1
     * @param $class2
     * @return string
     */
    protected function getIntermediateTableName($class1, $class2)
    {
        $name[] = strtolower(get_class($class1));
        $name[] = strtolower(get_class($class2));

        return implode('_', sort($name));
    }

    /**
     * Add table name to attributes.
     *
     * @param $table
     * @param $attribute
     * @return string
     */
    protected function parseAttributeName($table, $attribute)
    {
        return $table . '.' . $attribute;
    }
}