<?php

namespace TS\ezDB\Models;

use TS\ezDB\Models\Model;
use TS\ezDB\Query\RelationshipBuilder;

trait Relationship {
    /**
     * Relationship
     *
     * Has One - One to one
     * Has Many - One to many
     * Belongs To - One to one and Many to One
     * Belong To Many - Many to Many
     *
     * For now there is no support for polymorphic
     */

    protected $with;

    protected $relations;

    /**
     * Used for one to one relationship.
     *
     * @param Model $relation
     * @param string $foreignKey
     * @param string $localKey
     * @return RelationshipBuilder
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    protected function hasOne($relation, $foreignKey = null, $localKey = null)
    {
        $localKey = $localKey ?? $this->getPrimaryKey();

        $foreignKey = $foreignKey ?? $this->getForeignKey();

        $builder = (new RelationshipBuilder())->setModel(new $relation);

        return $builder->where($foreignKey, $this->$localKey)->setFetchFirst(true);
    }

    protected function hasMany($relation, $foreignKey = null, $localKey = null)
    {
        $localKey = $localKey ?? $this->getPrimaryKey();

        $foreignKey = $foreignKey ?? $this->getForeignKey();

        $builder = (new RelationshipBuilder())->setModel(new $relation);

        return $builder->where($foreignKey, $this->$localKey);
    }

    protected function belongsTo()
    {

    }

    protected function belongsToMany()
    {

    }
}