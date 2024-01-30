<?php

namespace adevesa\SimpleOTP\Exceptions;

class MaxAttemptsValidationOtpException extends \Exception
{
    public function __construct($message = 'Max attempts reached', $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
