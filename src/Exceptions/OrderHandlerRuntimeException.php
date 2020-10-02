<?php
namespace Germania\OrderDispatcher\Exceptions;

class OrderHandlerRuntimeException extends \RuntimeException implements OrderHandlerExceptionInterface
{
    public $caught_execeptions = array();

    public function getCaughtExceptions() : iterable
    {
        return $this->caught_execeptions;
    }

    public function setCaughtExceptions( iterable $exceptions )
    {
        $this->caught_execeptions = $exceptions;
        return $this;
    }
}
