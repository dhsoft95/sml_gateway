<?php

namespace App\Exceptions;

class CallbackException extends \Exception
{
    protected $context;

    public function __construct(string $message = "", array $context = [], int $code = 0, \Throwable $previous = null)
    {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getLogContext(): array
    {
        return array_merge($this->context, [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine()
        ]);
    }
}
