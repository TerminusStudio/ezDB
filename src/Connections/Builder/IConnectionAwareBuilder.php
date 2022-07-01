<?php

namespace TS\ezDB\Connections\Builder;

interface IConnectionAwareBuilder
{
    /**
     * @param array|null $values
     * @return bool
     */
    public function insert(array|null $values = null) : bool;

    /**
     * @param array|null $values
     * @return mixed
     */
    public function update(array|null $values = null): mixed;

    /**
     * @param string[]|null $columns
     * @return mixed
     */
    public function select(array|null $columns = null): mixed;

    /**
     * @param string[]|null $columns
     * @return mixed
     */
    public function get(array|null $columns = null): mixed;

    /**
     * Select only the first row
     * @param string[]|null $columns
     * @return array|object
     */
    public function first(array|null $columns = null): array|object;

    /**
     * @return bool
     */
    public function delete(): bool;

    /**
     * @return bool
     */
    public function truncate(): bool;

    /**
     * @param string[]|string $columns
     * @return mixed
     */
    public function count(array|string $columns = ['*']) : mixed;

    /**
     * @param string $column
     * @return mixed
     */
    public function sum(string $column) : mixed;

    /**
     * @param string $column
     * @return mixed
     */
    public function avg(string $column) : mixed;

    /**
     * @param string $column
     * @return mixed
     */
    public function max(string $column) : mixed;

    /**
     * @param string $column
     * @return mixed
     */
    public function min(string $column) : mixed;

    /**
     * @return mixed
     */
    public function execute(): mixed;
}