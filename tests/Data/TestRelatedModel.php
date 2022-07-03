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
    protected string $connectionName = 'Connection2';

    protected string $table = 'test_related';

    protected bool $timestamps = false;

    protected ?string $primaryKey = 'id';

    public function test()
    {
        return $this->belongsToMany(TestModel::class, 'test_intermediate');
    }
}