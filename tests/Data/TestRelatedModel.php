<?php

namespace TS\ezDB\Tests\Data;

use TS\ezDB\Models\Model;

class TestRelatedModel extends Model
{
    protected $connectionName = 'Connection2';

    protected $table = 'test_related';

    protected $timestamps = false;

    protected $primaryKey = 'id';

    public function test(){
        return $this->belongsToMany(TestModel::class, 'test_intermediate');
    }
}