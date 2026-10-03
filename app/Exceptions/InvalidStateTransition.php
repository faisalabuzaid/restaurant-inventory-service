<?php

namespace App\Exceptions;

/**
 * The requested operation is not allowed in the aggregate's current state
 * (e.g. sending an already sent purchase order, receiving against a draft).
 */
class InvalidStateTransition extends DomainException
{
    public function __construct(
        string $message,
        private readonly string $from,
        private readonly ?string $to = null,
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'invalid_state_transition';
    }

    public function context(): array
    {
        return array_filter(['from' => $this->from, 'to' => $this->to]);
    }
}
