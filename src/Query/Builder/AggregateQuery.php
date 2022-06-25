<?php
/*
 * Copyright (c) 2022 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Query\Builder;

class AggregateQuery extends BuilderInfo implements IAggregateQuery
{
    protected IBuilder $parent;

    public function __construct(IBuilder $parent)
    {
        $this->parent = $parent;
        $this->setType(QueryType::Aggregate);
    }

    public function getParent(): IBuilder
    {
        return $this->parent;
    }
}