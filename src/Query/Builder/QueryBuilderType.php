<?php

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
}