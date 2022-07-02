<?php

namespace TS\ezDB\Exceptions;

class ProcessorException extends Exception
{
    protected string $exceptionType = "Processor";

    /**
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}