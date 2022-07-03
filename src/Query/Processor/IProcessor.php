<?php

namespace TS\ezDB\Query\Processor;

use TS\ezDB\Query\Builder\IBuilderInfo;
use TS\ezDB\Query\IQuery;

interface IProcessor
{
    public function process(IBuilderInfo $builderInfo): IQuery;
}