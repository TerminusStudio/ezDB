<?php

namespace TS\ezDB\Tests\Data;

use TS\ezDB\Models\Model;

class TestModel extends Model
{
    protected $connectionName = 'TestModelConnection';

    protected $table = 'test';

    protected $timestamps = true;

    protected $primaryKey = 'id';

    public function testRelated()
    {
        return $this->belongsToMany(TestRelatedModel::class, 'test_intermediate')
            ->as('intermediate');
    }
}