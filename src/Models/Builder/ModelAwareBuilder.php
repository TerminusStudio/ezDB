<?php

namespace TS\ezDB\Models\Builder;

use TS\ezDB\Connection;
use TS\ezDB\Connections\Builder\ConnectionAwareBuilder;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Models\Model;
use TS\ezDB\Query\Builder\IBuilder;

class ModelAwareBuilder extends ConnectionAwareBuilder implements IBuilder
{
    protected ?Model $model;

    protected array $eagerLoad = [];

    public function __construct(Model $model, ?Connection $connection = null, ?string $tableName = null)
    {
        parent::__construct($connection, $tableName);
        $this->model = $model ?? throw new QueryException("Model must be set and cannot be null");
    }

    /**
     * @deprecated Needs to be removed.
     * Set Model class. If called with null, it will remove the model.
     * @param Model|null $model
     * @return $this
     */
    public function setModel(Model $model = null): static
    {
        $this->model = $model;
        if ($model != null) {
            $this->table($model->getTable());
        }
        return $this;
    }

    /**
     * Check if the model class is set
     * @return bool
     */
    public function hasModel(): bool
    {
        return ($this->model != null);
    }

    /**
     * @inheritDoc
     */
    public function asInsert(array $values): static
    {
        if ($this->model->hasTimestamps()) {
            //Only add if it's a single row. For multiple rows, just pass through since insert is a recursive call.
            if (!is_array(current($values))) {
                $values[$this->model->getCreatedAt()] = $this->now();
                $values[$this->model->getUpdatedAt()] = $this->now();
            }
        }
        parent::asInsert($values);
        return $this;
    }

    public function asUpdate(?array $values = null): static
    {
        parent::asUpdate($values);
        if ($this->model->hasTimestamps()) {
            $this->set($this->model->getUpdatedAt(), $this->now());
        }
        return $this;
    }

    /**
     * Return current datetime (to be used with mysql)
     * It returns the current time in PHP's timezone.
     * Make sure the timezone between the php server and the mysql server match.
     *
     * @return string
     */
    protected function now(): string
    {
        return date("Y-m-d H:i:s");
    }
}