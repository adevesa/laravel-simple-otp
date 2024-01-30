<?php

namespace adevesa\SimpleOTP\Exceptions;

class ExpiratedOtpException extends \Exception
{
    public function __construct($message = 'OTP expirated', $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
