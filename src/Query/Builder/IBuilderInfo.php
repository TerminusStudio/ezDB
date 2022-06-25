<?php
/*
 * Copyright (c) 2022 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Query\Builder;

interface IBuilderInfo
{
    /**
     * Get current query builder type.
     * @return QueryBuilderType
     */
    public function getType(): QueryBuilderType;

    /**
     * @param QueryBuilderType $type
     * @return void
     */
    public function setType(QueryBuilderType $type): void;

    /**
     * Get added clauses for processing
     * @param string $type
     * @return array
     */
    public function getClauses(string $type): array|null;

    public function clone() : static;
}