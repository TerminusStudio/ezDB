<?php

namespace TS\ezDB\Tests\Data;

use TS\ezDB\Models\Model;

class Test2Model extends Model
{
    protected $connectionName = 'Connection1';

    protected $table = 'test2';

    protected $timestamps = false;

    protected $primaryKey = 'id';

    public function test()
    {
        return $this->belongsTo(TestModel::class);
    }

    /** Just making the protected funciton publicly accessible for testing. */
    public function belongsTo($relation, $foreignKey = null, $ownerKey = null)
    {
        return parent::belongsTo($relation, $foreignKey, $ownerKey);
    }
}