<?php

namespace TS\ezDB\Query\Processor;

use TS\ezDB\Query\Builder\IBuilderInfo;

class ProcessorContext
{
    protected bool $isAggregate = false;

    protected IBuilderInfo $builderInfo;

    protected array $bindings = [];

    public function __construct(IBuilderInfo $builderInfo, bool $isAggregateQuery = false)
    {
        $this->builderInfo = $builderInfo;
        $this->isAggregate = $isAggregateQuery;
    }

    public function isAggregateQuery(): bool
    {
        return $this->isAggregate;
    }

    public function getBuilder(): IBuilderInfo
    {
        return $this->builderInfo;
    }

    public function addBinding(object|string|int|bool|float $value): void
    {
        $this->bindings[] = $value;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function getClauses(string $type): array
    {
        return $this->builderInfo->getClauses($type) ?? [];
    }
}