<?php

namespace adevesa\SimpleOTP\Exceptions;

class NotFoundOtpException extends \Exception
{
    public function __construct($message = 'OTP not found', $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
