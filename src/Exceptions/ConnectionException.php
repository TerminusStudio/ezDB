<?php

namespace TS\ezDB\Exceptions;

class ConnectionException extends Exception
{
    /**
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}