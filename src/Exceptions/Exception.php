<?php

namespace TS\ezDB\Exceptions;

use Throwable;

class Exception extends \Exception
{
    protected $exceptionType = "";
    /**
     * Exception constructor.
     * @param string $message
     * @param int|string $code
     * @param Throwable|null $previous
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct('ezDB ' . $this->exceptionType . ' Exception: ' . $message, intval($code), $previous);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ': [{$this->code}]: {$this->message}\n';
    }
}