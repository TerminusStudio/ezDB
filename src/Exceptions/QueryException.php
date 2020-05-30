<?php

namespace TS\ezDB\Exceptions;

class QueryException extends Exception
{
    protected $exceptionType = "Query";

    /**
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}