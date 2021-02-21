<?php

namespace TS\ezDB\Query;

use TS\ezDB\Connection;
use TS\ezDB\Exceptions\ModelMethodException;
use TS\ezDB\Exceptions\QueryException;

/**
 * This builder can be used when dealing with relationships. It can be used directly, or via Models.
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
     * @var string Related table name
     */
    protected $relatedTableName;

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
     */
    public function __construct(Connection $connection = null)
    {
        parent::__construct($connection);
    }

    /**
     * @param string $relation The related table name
     * @param string $foreignKeyValue Foreign Key Value
     * @param string $foreignKey Foreign Key Name
     * @return RelationshipBuilder
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public function hasOne($relation, $foreignKeyValue, $foreignKey)
    {
        $this->fetchFirst = true;
        $this->relatedTableName = $relation;
        return $this->where($foreignKey, $foreignKeyValue);
    }

    /**
     * @param string $relation The related table name
     * @param string $foreignKeyValue Foreign Key Value
     * @param string $foreignKey Foreign Key Name
     * @return RelationshipBuilder
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public function hasMany($relation, $foreignKeyValue, $foreignKey)
    {
        $this->relatedTableName = $relation;
        return $this->where($foreignKey, $foreignKeyValue);
    }

    /**
     * @param string $relation Owner Table Name
     * @param string $ownerKeyValue Owner Key Value
     * @param string $ownerKey Owner Key Name
     * @return RelationshipBuilder
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public function belongsTo($relation, $ownerKeyValue, $ownerKey = 'id')
    {
        $this->fetchFirst = true;
        $this->relatedTableName = $relation; //In this case owner is relatedTable.
        return $this->where($ownerKey, $ownerKeyValue);
    }

    /**
     * @param string $relation Related Table Name (contact)
     * @param string $intermediateTable The intermediate table name (user_contact)
     * @param string $ownerForeignKey The owner foreign key name (user_id)
     * @param string $relatedForeignKey The related table foreign key name  (contact_id)
     * @param string $relatedPrimaryKey The related table primary key name (id)
     * @param string $ownerKeyValue The owner key value (Value of user.id)
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public function belongsToMany(
        $relation,
        $intermediateTable,
        $ownerForeignKey,
        $relatedForeignKey,
        $relatedPrimaryKey,
        $ownerKeyValue
    )
    {
        $this->manyToMany = true;
        $this->relatedTableName = $relation;
        return $this->withPivot($ownerForeignKey, $relatedForeignKey)
            ->joinPivot(
                $intermediateTable,
                $this->parseAttributeName($relation, $relatedPrimaryKey),
                '=',
                $this->parseAttributeName($intermediateTable, $relatedForeignKey)
            )->where(
                $this->parseAttributeName($intermediateTable, $ownerForeignKey),
                '=',
                $ownerKeyValue
            );
    }

    /**
     * Fetches all the rows from the database or only one if $fetchFirst is set.
     *
     * @param string[] $columns
     * @return array|bool|mixed
     * @throws \TS\ezDB\Exceptions\ConnectionException|\TS\ezDB\Exceptions\QueryException
     */
    public function get($columns = ['*'])
    {
        if (!$this->hasModel()) { //setModel method sets the table name by default.
            $this->table($this->relatedTableName);
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

            //Process the result and seperate pivot values to relation (if there is model)
            // or to a seperate array if a model is not set.
            foreach ($results as &$r) {
                //This contains all the attributes from the database.
                //We extract the keys present in $pivotAttributes
                $data = $this->hasModel() ? $r->getData() : $r;
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
                    $r[$this->pivotName] = $pivotValues;
                }
            }
            return $results;
        } else {
            return parent::get($columns);
        }
    }

    /**
     * Join the pivot table.
     * @param $table
     * @param $condition1
     * @param null $operator
     * @param null $condition2
     * @param string $joinType
     * @return $this
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public function joinPivot($table, $condition1, $operator = null, $condition2 = null, $joinType = 'INNER JOIN')
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
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public function withPivot(...$columns)
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
     * Set whether pivot table has timestamp
     * Only works when there is a model specified.
     *
     * @return $this
     * @throws \TS\ezDB\Exceptions\QueryException|\TS\ezDB\Exceptions\ModelMethodException
     */
    public function withTimestamps()
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
     *
     * @param $column
     * @param null $operator
     * @param null $value
     * @param string $boolean
     * @return $this
     * @throws \TS\ezDB\Exceptions\QueryException
     */
    public function wherePivot($column, $operator = null, $value = null, $boolean = 'AND')
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
        if (stripos($attribute, ' AS ') !== false) { //Attribute already has an alias so lets not add one.
            return [$attribute, $this->pivotTableName . '.' . $attribute];
        } else {
            return ['pivot_' . $attribute, $this->pivotTableName . '.' . $attribute . ' as pivot_' . $attribute];
        }
    }

    /**
     * Add table name to attributes.
     *
     * @param $table
     * @param $attribute
     * @return string
     */
    protected function parseAttributeName($table, $attribute)
    {
        return $table . '.' . $attribute;
    }
}
