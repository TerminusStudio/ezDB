<?php

namespace TS\ezDB\Query;

use TS\ezDB\Connection;

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
     * @var bool if set to true the get() will work same as first()
     */
    protected $fetchFirst = false;

    /**
     * @param $fetchFirst
     * @return $this
     */
    public function setFetchFirst($fetchFirst)
    {
        $this->fetchFirst = $fetchFirst;
        return $this;
    }

    /**
     * Fetches all the rows from the database or only one if $fetchFirst is set.
     *
     * @param string[] $columns
     * @return array|bool|mixed
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function get($columns = ['*'])
    {
        if($this->fetchFirst) {
            $this->limit(1);
            $r = parent::get($columns);
            return $r[0] ?? $r;
        } else {
            return parent::get($columns);
        }
    }
}