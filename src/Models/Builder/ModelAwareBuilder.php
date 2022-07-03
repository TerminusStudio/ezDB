<?php

namespace TS\ezDB\Models\Builder;

use TS\ezDB\Connection;
use TS\ezDB\Connections\Builder\ConnectionAwareBuilder;
use TS\ezDB\Exceptions\ModelMethodException;
use TS\ezDB\Models\Model;
use TS\ezDB\Query\Builder\IBuilder;

class ModelAwareBuilder extends ConnectionAwareBuilder implements IBuilder
{
    protected ?Model $model = null;

    protected array $eagerLoad = [];

    public function __construct(?Connection $connection = null, ?string $tableName = null)
    {
        parent::__construct($connection, $tableName);
    }

    /**
     * Set Model class. If called with null, it will remove the model.
     * @param Model|null $model
     * @return $this
     */
    public function setModel(Model $model = null): static
    {
        $this->model = $model;
        if ($model != null) {
            $this->addClause('from', [], replace: true);
            $this->from($model->getTable());
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
        if ($this->hasModel() && $this->model->hasTimestamps()) {
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
        if ($this->hasModel() && $this->model->hasTimestamps()) {
            $this->set($this->model->getUpdatedAt(), $this->now());
        }
        return $this;
    }

    /**
     * @inheritDoc
     * @throws \TS\ezDB\Exceptions\ModelMethodException
     */
    public function select(?array $columns = null): mixed
    {
        $result = parent::select($columns);
        if (!$this->hasModel()) {
            return $result;
        }
        return $this->model::createFromResult($result, $this->eagerLoad);
    }

    /**
     * This function should be used with the model for eager loading.
     *
     * TODO: Support loading relations in a single query.
     * SELECT users.*, '' as `with`, api.user_id is null as `exists`, api.* FROM users
     * LEFT JOIN api ON users.id = api.user_id
     *
     * @param string|array $relations
     * @return $this
     * @throws ModelMethodException
     */
    public function with(string|array $relations): static
    {
        if (!$this->hasModel()) {
            throw new ModelMethodException("with() method is only accessible when using Models.");
        }

        if (is_array($relations)) {
            $this->eagerLoad = array_merge($this->eagerLoad, $relations);
        } else {
            $this->eagerLoad[] = $relations;
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

    public function clone(): static
    {
        $query = parent::clone();
        $query->model = $this->model;
        $query->eagerLoad = $this->eagerLoad;
        return $query;
    }


}