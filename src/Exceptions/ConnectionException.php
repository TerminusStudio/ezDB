<?php

namespace TS\ezDB\Exceptions;

class ConnectionException extends Exception
{
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}