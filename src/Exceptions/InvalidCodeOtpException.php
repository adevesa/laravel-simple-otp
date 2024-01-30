<?php

namespace adevesa\SimpleOTP\Exceptions;

class InvalidCodeOtpException extends \Exception
{
    public function __construct($message = 'Invalid OTP', $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
