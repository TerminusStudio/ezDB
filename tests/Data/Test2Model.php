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

class Test2Model extends Model
{
    protected string $connectionName = 'Connection1';

    protected string $table = 'test2';

    protected bool $timestamps = false;

    protected ?string $primaryKey = 'id';

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