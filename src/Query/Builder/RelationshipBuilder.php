<?php

namespace TS\ezDB\Query\Builder;

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
     * @var string The local key attribute
     */
    protected $localKey;

    /**
     * @var string The foreign key attribute
     */
    protected $foreignkey;

    /**
     * @var string|array The foreign key value. If an array is set, the whereIn function will be used instead of where.
     */
    protected $foreignKeyValue;


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
        $this->foreignkey = $foreignKey;
        $this->foreignKeyValue = $foreignKeyValue;

        return $this;
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
        $this->foreignkey = $foreignKey;
        $this->foreignKeyValue = $foreignKeyValue;

        return $this;
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
        $this->foreignkey = $ownerKey; //and ownerKey is foreign key
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
        $this->foreignkey = $this->parseAttributeName($intermediateTable, $ownerForeignKey);
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
    public function get($columns = ['*'])
    {
        //setModel() method sets the table name by default so only set table name if this is called outside a model.
        if (!$this->hasModel()) {
            $this->table($this->relatedTableName);
        }

        if (is_array($this->foreignKeyValue)) { //if value is an array, then use whereIn method.
            $this->whereIn($this->foreignkey, $this->foreignKeyValue);
        } else {
            $this->where($this->foreignkey, '=', $this->foreignKeyValue);
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
     * @param $table
     * @param $condition1
     * @param null $operator
     * @param null $condition2
     * @param string $joinType
     * @return RelationshipBuilder
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
     * @return RelationshipBuilder
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
     * @return RelationshipBuilder
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
     * @return RelationshipBuilder
     */
    public function as($pivotName)
    {
        $this->pivotName = $pivotName;
        return $this;
    }

    /**
     * Set Fetch First
     *
     * @param $fetchFirst
     * @return RelationshipBuilder
     */
    public function setFetchFirst($fetchFirst)
    {
        $this->fetchFirst = $fetchFirst;
        return $this;
    }

    /**
     * Get Fetch First
     *
     * @return bool
     */
    public function getFetchFirst()
    {
        return $this->fetchFirst;
    }

    /**
     * This function is used with Models. It sets the local primary key or the owner's foreign key.
     *
     * @return RelationshipBuilder
     */
    public function setLocalKey($localKey)
    {
        $this->localKey = $localKey;
        return $this;
    }

    /**
     * This function is used with Models. It returns the local primary key or owner's foreign key
     * @return string
     */
    public function getLocalKey()
    {
        return $this->localKey;
    }

    /**
     * Get foreign key column name.
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignkey;
    }

    /**
     * @param string|array $value
     * @return RelationshipBuilder
     */
    public function setForeignKeyValue($value)
    {
        $this->foreignKeyValue = $value;
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
