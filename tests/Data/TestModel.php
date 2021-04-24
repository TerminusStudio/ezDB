<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Tests\Data;

use TS\ezDB\Models\Model;

class TestModel extends Model
{
    protected $connectionName = 'Connection1';

    protected $table = 'test';

    protected $timestamps = true;

    protected $primaryKey = 'id';

    public function testRelated()
    {
        return $this->belongsToMany(TestRelatedModel::class, 'test_intermediate')
            ->as('intermediate');
    }

    public function test2()
    {
        return $this->hasMany(Test2Model::class);
    }

    /** Just making the protected funciton publicly accessible for testing. */
    public function hasOne($relation, $foreignKey = null, $localKey = null)
    {
        return parent::hasOne($relation, $foreignKey, $localKey);
    }

    public function hasMany($relation, $foreignKey = null, $localKey = null)
    {
        return parent::hasMany($relation, $foreignKey, $localKey);
    }

    public function belongsToMany(
        $relation,
        $intermediateTable = null,
        $foreignKey = null,
        $relatedForeignKey = null,
        $localPrimaryKey = null,
        $relatedPrimaryKey = null
    )
    {
        return parent::belongsToMany(
            $relation,
            $intermediateTable,
            $foreignKey,
            $relatedForeignKey,
            $localPrimaryKey,
            $relatedPrimaryKey
        );
    }
}