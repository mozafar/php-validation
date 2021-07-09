<?php

namespace Mozafar\Validation;

class RuleException extends \Exception
{
    public function __construct(string $message = 'Invalid data', int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
