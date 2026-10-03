<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Base class for business-rule violations.
 *
 * Thrown from Actions when an operation is well-formed but not allowed by the
 * domain (wrong purchase order status, over-receipt, ...). Rendered as JSON
 * with a stable machine-readable `error` code and an HTTP status chosen by
 * the concrete subclass.
 */
abstract class DomainException extends RuntimeException
{
    /** HTTP status to respond with. */
    abstract public function status(): int;

    /** Stable, machine-readable error code (snake_case). */
    abstract public function errorCode(): string;

    /** Extra context merged into the JSON body. */
    public function context(): array
    {
        return [];
    }
}
