<?php
/*
 * Copyright (c) 2022 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Query\Builder;

enum QueryBuilderType
{
    case Unknown;
    case Insert;
    case Update;
    case Select;
    case Delete;
    case Truncate;
    case Where;
    case Aggregate;
}