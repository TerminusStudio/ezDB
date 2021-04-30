<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Query\Processor;

class MySQLProcessor extends Processor
{
    /**
     * @inheritDoc
     */
    protected function wrapValue($value)
    {
        if ($value == '*') {
            return $value;
        }
        return '`' . $value . '`';
    }
}