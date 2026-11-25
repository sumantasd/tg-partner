<?php

namespace App\Exceptions;

use DomainException;
use Throwable;

class AuditLogWriteException extends DomainException
{
    public function __construct(string $message = 'Failed to write mandatory audit log record.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
