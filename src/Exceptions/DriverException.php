<?php

namespace TS\ezDB\Exceptions;

class DriverException extends Exception
{
    protected $exceptionType = "Driver";

    /**
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}