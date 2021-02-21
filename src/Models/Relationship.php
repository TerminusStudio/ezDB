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
        $model = new $relation();

        $localKey = $localKey ?? $this->getPrimaryKey();

        $foreignKey = $foreignKey ?? $this->getForeignKey();

        return (new RelationshipBuilder(Connections::connection($this->connectionName)))
            ->setModel($model)
            ->hasOne($model->getTable(), $this->$localKey, $foreignKey);
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
        $model = new $relation();

        $localKey = $localKey ?? $this->getPrimaryKey();

        $foreignKey = $foreignKey ?? $this->getForeignKey();

        return (new RelationshipBuilder(Connections::connection($this->connectionName)))
            ->setModel($model)
            ->hasMany($model->getTable(), $this->$localKey, $foreignKey);
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
        $model = new $relation();

        $foreignKey = $foreignKey ?? $model->getForeignKey();

        $ownerKey = $ownerKey ?? $model->getPrimaryKey();

        return (new RelationshipBuilder(Connections::connection($this->connectionName)))
            ->setModel($model)
            ->belongsTo($model->getTable(), $this->$foreignKey, $ownerKey);
    }

    /**
     * @param string $relation
     * @param string|null $intermediateTable
     * @param string|null $foreignKey
     * @param string|null $relatedKey
     * @param string|null $localPrimaryKey
     * @param string|null $relatedPrimaryKey
     * @return RelationshipBuilder
     * @throws \TS\ezDB\Exceptions\QueryException|\TS\ezDB\Exceptions\ConnectionException|\TS\ezDB\Exceptions\ModelMethodException
     */
    protected function belongsToMany(
        $relation,
        $intermediateTable = null,
        $foreignKey = null,
        $relatedKey = null,
        $localPrimaryKey = null,
        $relatedPrimaryKey = null
    )
    {
        /** @var Model $model */
        $model = new $relation();

        $intermediateTable = $intermediateTable ?? $this->getIntermediateTableName($this, $model);

        $foreignKey = $foreignKey ?? $this->getForeignKey();

        $relatedKey = $relatedKey ?? $model->getForeignKey();

        $localPrimaryKey = $localPrimaryKey ?? $this->getPrimaryKey();

        $relatedPrimaryKey = $relatedPrimaryKey ?? $model->getPrimaryKey();

        return (new RelationshipBuilder(Connections::connection($this->connectionName)))
            ->setModel($model)
            ->belongsToMany(
                $model->getTable(),
                $intermediateTable,
                $foreignKey,
                $relatedKey,
                $relatedPrimaryKey,
                $this->$localPrimaryKey
            );
    }

    /**
     * Generate the intermediate table name using the Model classes.
     *
     * @param Model $class1
     * @param Model $class2
     * @return string
     */
    protected function getIntermediateTableName($class1, $class2)
    {
        $name[] = $class1->getTable();
        $name[] = $class2->getTable();
        sort($name);
        return implode('_', $name);
    }
}