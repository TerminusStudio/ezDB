<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Models\Builder;

use Closure;
use TS\ezDB\Connection;
use TS\ezDB\Exceptions\Exception;
use TS\ezDB\Exceptions\ModelMethodException;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Models\Model;

/**
 * This builder can be used when dealing with relationships. It can be used directly, or via Models.
 *
 * Class RelationshipBuilder
 * @package TS\ezDB\Query
 */
class RelationshipBuilder extends ModelAwareBuilder
{
    /**
     * @var bool if set to true the get() will work same as first() (For hasOne and belongsTo)
     */
    protected bool $fetchFirst = false;

    /**
     * @var bool If set to true additional functions will work (For many to many)
     */
    protected bool $manyToMany = false;

    /**
     * @var string Related table name
     */
    protected string $relatedTableName;

    /**
     * @var string The local key attribute
     */
    protected string $localKey;

    /**
     * @var string The foreign key attribute
     */
    protected string $foreignKey;

    /**
     * @var string|array The foreign key value. If an array is set, the whereIn function will be used instead of where.
     */
    protected string|array $foreignKeyValue;


    /**
     * @var string The key name for the pivot table
     */
    protected string $pivotName = "pivot";

    /**
     * @var string The pivot table name
     */
    protected string $pivotTableName;

    /**
     * @var array contains the attributes that need to be selected from the pivot class
     */
    protected array $pivotAttributes = [];

    /**
     * @var bool Does the pivot table have timestamps
     */
    protected bool $pivotHasTimestamp = false;

    /**
     * RelationshipBuilder constructor.
     * @param Model $model
     * @param Connection|null $connection
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function __construct(Model $model, Connection $connection = null)
    {
        parent::__construct($model, $connection);
    }

    /**
     * @param string $relation The related table name
     * @param string $foreignKeyValue Foreign Key Value
     * @param string $foreignKey Foreign Key Name
     * @return $this
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public function hasOne(string $relation, string $foreignKeyValue, string $foreignKey): static
    {
        $this->fetchFirst = true;
        $this->relatedTableName = $relation;
        $this->foreignKey = $foreignKey;
        $this->foreignKeyValue = $foreignKeyValue;

        return $this;
    }

    /**
     * @param string $relation The related table name
     * @param string $foreignKeyValue Foreign Key Value
     * @param string $foreignKey Foreign Key Name
     * @return $this
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public function hasMany(string $relation, string $foreignKeyValue, string $foreignKey): static
    {
        $this->relatedTableName = $relation;
        $this->foreignKey = $foreignKey;
        $this->foreignKeyValue = $foreignKeyValue;

        return $this;
    }

    /**
     * @param string $relation Owner Table Name
     * @param string $ownerKeyValue Owner Key Value
     * @param string $ownerKey Owner Key Name
     * @return $this
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public function belongsTo(string $relation, string $ownerKeyValue, string $ownerKey = 'id'): static
    {
        $this->fetchFirst = true;
        $this->relatedTableName = $relation; //In this case owner is relatedTable.
        $this->foreignKey = $ownerKey; //and ownerKey is foreign key
        $this->foreignKeyValue = $ownerKeyValue;

        return $this;
    }

    /**
     * @param string $relation Related Table Name (contact)
     * @param string $intermediateTable The intermediate table name (user_contact)
     * @param string $ownerForeignKey The owner foreign key name (user_id)
     * @param string $relatedForeignKey The related table foreign key name  (contact_id)
     * @param string $relatedPrimaryKey The related table primary key name (id)
     * @param string $ownerKeyValue The owner key value (Value of user.id)
     * @return $this
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public function belongsToMany(
        string $relation,
        string $intermediateTable,
        string $ownerForeignKey,
        string $relatedForeignKey,
        string $relatedPrimaryKey,
        string $ownerKeyValue
    ): static
    {
        $this->manyToMany = true;
        $this->relatedTableName = $relation;
        $this->foreignKey = $this->parseAttributeName($intermediateTable, $ownerForeignKey);
        $this->foreignKeyValue = $ownerKeyValue;

        return $this->withPivot($ownerForeignKey, $relatedForeignKey)
            ->joinPivot(
                $intermediateTable,
                $this->parseAttributeName($relation, $relatedPrimaryKey),
                '=',
                $this->parseAttributeName($intermediateTable, $relatedForeignKey)
            );
    }

    /**
     * Fetches all the rows from the database or only one if $fetchFirst is set.
     *
     * @param string[] $columns
     * @return array|bool|mixed
     * @throws \TS\ezDB\Exceptions\ConnectionException|\TS\ezDB\Exceptions\QueryException
     */
    public function get(array|null $columns = ['*']): mixed
    {
        //setModel() method sets the table name by default so only set table name if this is called outside a model.
        if (!$this->hasModel()) {
            $this->table($this->relatedTableName);
        }

        if (is_array($this->foreignKeyValue)) { //if value is an array, then use whereIn method.
            $this->whereIn($this->foreignKey, $this->foreignKeyValue);
        } else {
            $this->where($this->foreignKey, '=', $this->foreignKeyValue);
        }

        if ($this->fetchFirst) {
            $this->limit(1, 0);
            $r = parent::get(); //calling first will not work here since first() will call back get()
            return $r[0] ?? $r;
        } elseif ($this->manyToMany) {
            if ($columns == ['*']) {
                $columns = [$this->relatedTableName . '.*'];
            }

            if ($this->hasModel() && $this->pivotHasTimestamp) {
                $this->withPivot($this->model->getCreatedAt(), $this->model->getUpdatedAt());
            }

            $pivotAttributes = [];
            foreach ($this->pivotAttributes as $attribute) {
                $parsed = $this->parsePivotAttribute($attribute);
                $pivotAttributes[] = $parsed[0];
                $columns[] = $parsed[1];
            }

            $results = parent::get($columns);

            //Process the result and separate pivot values to relation (if there is model)
            // or to a separate array if a model is not set.
            foreach ($results as &$r) {
                //This contains all the attributes from the database.
                //We extract the keys present in $pivotAttributes
                $data = $this->hasModel() ? $r->getData() : (array)$r;
                $pivotValues = [];

                foreach ($pivotAttributes as $pivotAttribute) {
                    $pivotValues[preg_replace('/^pivot_/i', '', $pivotAttribute)] = $data[$pivotAttribute] ?? null;
                    unset($data[$pivotAttribute]);
                }

                if ($this->hasModel()) {
                    $r->setRelation($this->pivotName, (object)$pivotValues); //TODO: Create a pivot class
                    $r->setData($data);
                } else {
                    $r = $data;
                    $r[$this->pivotName] = (object)$pivotValues;
                    $r = (object)$r;
                }
            }
            return $results;
        } else {
            return parent::get($columns);
        }
    }

    /**
     * Join the pivot table.
     * @param string $table
     * @param string|Closure $condition1
     * @param string|null $operator
     * @param string|null $condition2
     * @param string $joinType
     * @return $this
     * @throws QueryException
     */
    public function joinPivot(string $table, string|Closure $condition1, ?string $operator = null, ?string $condition2 = null, string $joinType = 'INNER JOIN'): static
    {
        if (!$this->manyToMany) {
            throw new QueryException(
                "This method is only available for many to many relations (belongsToMany)."
            );
        }

        if (stripos($table, ' AS ') !== false) {
            //Has an alias.
            $this->pivotTableName = preg_split('/\s+as\s+/i', $table)[0];
        } else {
            $this->pivotTableName = $table;
        }

        parent::join($table, $condition1, $operator, $condition2, $joinType);
        return $this;
    }

    /**
     * Return a default value set if there was no matches.
     * Only works when fetchFirst is set (hasOne and belongsTo)
     * @return  $this;
     */
    public function withDefaults(): static
    {
        //TODO: Complete this function
        throw new Exception('The withDefaults() method has not yet been implemented.');
    }

    /**
     * Columns to get from the pivot table
     * @param string ...$columns
     * @return  $this
     * @throws QueryException
     */
    public function withPivot(string ...$columns): static
    {
        if (!$this->manyToMany) {
            throw new QueryException(
                "This method is only available for many to many relations (belongsToMany)."
            );
        }
        $this->pivotAttributes = array_merge($this->pivotAttributes, $columns);
        return $this;
    }

    /**
     * Set whether pivot table has timestamp column
     * Only works when there is a model specified.
     * @return $this
     * @throws \TS\ezDB\Exceptions\QueryException|\TS\ezDB\Exceptions\ModelMethodException
     */
    public function withTimestamps(): static
    {
        if (!$this->manyToMany) {
            throw new QueryException(
                "This method is only available for many to many relations (belongsToMany)."
            );
        } elseif (!$this->hasModel()) {
            throw new ModelMethodException(
                "This method is only available when a Model is set."
            );
        }
        $this->pivotHasTimestamp = true;
        return $this;
    }

    /**
     * Where statement using a the pivot table.
     * @param string $column
     * @param string $operator
     * @param string|null $value
     * @param string $boolean
     * @return $this
     * @throws QueryException
     */
    public function wherePivot(string $column, string $operator, ?string $value = null, string $boolean = 'AND'): static
    {
        if (!$this->manyToMany) {
            throw new QueryException(
                "This method is only available for many to many relations (belongsToMany)."
            );
        }

        parent::where($this->pivotTableName . '.' . $column, $operator, $value, $boolean);
        return $this;
    }

    /**
     * Set the pivot name.
     * @param string $pivotName
     * @return $this
     */
    public function as(string $pivotName): static
    {
        $this->pivotName = $pivotName;
        return $this;
    }

    /**
     * Set Fetch First
     * @param bool $fetchFirst
     * @return $this
     */
    public function setFetchFirst(bool $fetchFirst): static
    {
        $this->fetchFirst = $fetchFirst;
        return $this;
    }

    /**
     * Get Fetch First
     * @return bool
     */
    public function getFetchFirst(): bool
    {
        return $this->fetchFirst;
    }

    /**
     * This function is used with Models. It sets the local primary key or the owner's foreign key.
     * @param string $localKey
     * @return $this
     */
    public function setLocalKey(string $localKey): static
    {
        $this->localKey = $localKey;
        return $this;
    }

    /**
     * This function is used with Models. It returns the local primary key or owner's foreign key
     * @return string
     */
    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    /**
     * Get foreign key column name.
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * @param string|array $value
     * @return $this
     */
    public function setForeignKeyValue(string|array $value): static
    {
        $this->foreignKeyValue = $value;
        return $this;
    }

    /**
     * @param string $attribute
     * @return string[] The first item will be the name of the attribute and the second one will be used in the SQL
     * statement
     */
    protected function parsePivotAttribute(string $attribute): array
    {
        if (stripos($attribute, ' AS ') !== false) { //Attribute already has an alias so let's not add one.
            return [$attribute, $this->pivotTableName . '.' . $attribute];
        } else {
            return ['pivot_' . $attribute, $this->pivotTableName . '.' . $attribute . ' as pivot_' . $attribute];
        }
    }

    /**
     * Add table name to attributes.
     *
     * @param string $table
     * @param string $attribute
     * @return string
     */
    protected function parseAttributeName(string $table, string $attribute): string
    {
        return $table . '.' . $attribute;
    }
}
