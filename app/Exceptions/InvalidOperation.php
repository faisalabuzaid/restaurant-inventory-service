<?php

namespace App\Exceptions;

/**
 * The request is syntactically valid but violates a business rule that is
 * not a state transition (e.g. receiving more than is outstanding, selling a
 * menu item with no recipe). Rendered as 422 like validation failures.
 */
class InvalidOperation extends DomainException
{
    public function __construct(
        string $message,
        private readonly string $errorCode = 'invalid_operation',
        private readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function context(): array
    {
        return $this->context;
    }
}
