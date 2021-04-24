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