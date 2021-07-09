<?php

namespace Mozafar\Validation;

class ValidationException extends \Exception
{
    private array $errors = [];

    public function __construct(array $errors = [], string $message = 'Invalid data', int $code = 422, ?\Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    public function errors()
    {
        return $this->errors;
    }
}
