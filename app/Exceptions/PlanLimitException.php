<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\PlanLimitKey;
use Exception;

class PlanLimitException extends Exception
{
    public function __construct(
        public readonly PlanLimitKey $limitKey,
        string $message = '',
    ) {
        parent::__construct($message ?: sprintf('Plan limit reached for: %s', $limitKey->value));
    }
}
