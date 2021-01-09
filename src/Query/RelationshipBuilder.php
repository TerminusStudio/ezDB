<?php

namespace TS\ezDB\Query;

use TS\ezDB\Connection;
use TS\ezDB\Exceptions\ConnectionException;
use TS\ezDB\Exceptions\ModelMethodException;
use TS\ezDB\Models\Model;

/**
 * This builder is only intended to be used internally when managing relationships. It adds on methods to the original
 * builder class.
 *
 * Class RelationshipBuilder
 * @package TS\ezDB\Query
 */
class RelationshipBuilder extends Builder
{
    /**
     * @var bool if set to true the get() will work same as first() (For hasOne and belongsTo)
     */
    protected $fetchFirst = false;

    /**
     * @var bool If set to true additional functions will work (For many to many)
     */
    protected $manyToMany = false;

    /**
     * @var string The key name for the pivot table
     */
    protected $pivotName = "pivot";

    /**
     * @var string The pivot table name
     */
    protected $pivotTableName;

    /**
     * @var array contains the attributes that needs to be selecred from the pivot class
     */
    protected $pivotAttributes = [];

    /**
     * @var bool Does the pivot table have timestamps
     */
    protected $pivotHasTimestamp = false;

    /**
     * RelationshipBuilder constructor.
     * @param Connection|null $connection
     * @param false $fetchFirst
     * @param false $manyToMany
     */
    public function __construct(Connection $connection = null, $fetchFirst = false, $manyToMany = false)
    {
        parent::__construct($connection);
        $this->fetchFirst = $fetchFirst;
        $this->manyToMany = $manyToMany;
    }

    /**
     * Fetches all the rows from the database or only one if $fetchFirst is set.
     *
     * @param string[] $columns
     * @return array|bool|mixed
     * @throws ConnectionException|ModelMethodException
     */
    public function get($columns = ['*'])
    {
        if ($this->fetchFirst) {
            $this->limit(1);
            $r = parent::get($columns);
            return $r[0] ?? $r;
        } else if ($this->manyToMany) {
            if ($columns == ['*']) {
                $columns = [$this->model->getTable() . '.*'];
            }

            if ($this->pivotHasTimestamp) {
                $this->withPivot($this->model->getCreatedAt(), $this->model->getUpdatedAt());
            }

            $pivotAttributes = [];
            foreach ($this->pivotAttributes as $attribute) {
                $parsed = $this->parsePivotAttribute($attribute);
                $pivotAttributes[] = $parsed[0];
                $columns[] = $parsed[1];
            }

            if ($this->pivotHasTimestamp) {
                $columns[] = $this->parsePivotAttribute($this->model->getCreatedAt());
                $columns[] = $this->parsePivotAttribute($this->model->getUpdatedAt());
            }

            /** @var Model[] $results */
            $results = parent::get($columns);

            foreach ($results as &$r) {
                //This contains all the attributes from the database. We extract the keys present in $pivotAttributes
                $data = $r->getData();
                $pivotValues = [];

                foreach ($pivotAttributes as $pivotAttribute) {
                    $pivotValues[$pivotAttribute] = $data[$pivotAttribute] ?? null;
                    unset($data[$pivotAttribute]);
                }

                $r->setRelation($this->pivotName, $pivotValues);
                $r->setData($data);
            }
            return $results;
        } else {
            return parent::get($columns);
        }
    }

    /**
     * @inheritDoc
     */
    public function join($table, $condition1, $operator = null, $condition2 = null, $joinType = 'INNER JOIN')
    {
        if ($this->manyToMany) {
            if (stripos($table, ' AS ') !== FALSE) {
                //Has an alias.
                $this->pivotTableName = preg_split('/\s+as\s+/i', $table)[0];
            } else {
                $this->pivotTableName = $table;
            }
        }

        parent::join($table, $condition1, $operator, $condition2, $joinType);
        return $this;
    }


    /**
     * Return a default value set if there was no matches.
     * Only works when fetchFirst is set (hasOne and belongsTo)
     */
    public function withDefaults()
    {
        //TODO: Complete this function
    }

    /**
     * Columns to get from the pivot table
     *
     * @param mixed ...$columns
     * @return RelationshipBuilder
     * @throws ModelMethodException
     */
    public function withPivot(...$columns)
    {
        if (!$this->manyToMany) {
            throw new ModelMethodException(
                "This method is only available for many to many relations (belongsToMany)."
            );
        }
        $this->pivotAttributes = array_merge($this->pivotAttributes, $columns);
        return $this;
    }

    /**
     * Where statement using a the pivot table.
     *
     * @param $column
     * @param null $operator
     * @param null $value
     * @param string $boolean
     * @return $this
     * @throws ModelMethodException
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public function wherePivot($column, $operator = null, $value = null, $boolean = 'AND')
    {
        if (!$this->manyToMany) {
            throw new ModelMethodException(
                "This method is only available for many to many relations (belongsToMany)."
            );
        }

        parent::where($this->pivotTableName . '.' . $column, $operator, $value, $boolean);
        return $this;
    }

    /**
     * Set the pivot name.
     * @param $pivotName
     * @return $this
     */
    public function as($pivotName)
    {
        $this->pivotName = $pivotName;
        return $this;
    }

    /**
     *
     * @param $attribute
     * @return string[] The first item will be the name of the attribute and the second one will be used in the SQL
     * statement
     */
    protected function parsePivotAttribute($attribute)
    {
        if (stripos($attribute, ' AS ') !== FALSE) { //Attribute already has an alias so lets not add one.
            return [$attribute, $this->pivotTableName . '.' . $attribute];
        } else {
            return ['pivot_' . $attribute, $this->pivotTableName . '.' . $attribute . ' as pivot_' . $attribute];
        }
    }
}