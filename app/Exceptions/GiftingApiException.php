<?php

namespace App\Exceptions;

use Exception;

class GiftingApiException extends Exception
{
    /**
     * Create a new GiftingApiException instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
