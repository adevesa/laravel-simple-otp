<?php

return [
    'otp_length' => env('SIMPLE_OTP_LENGTH', 6),
    'otp_format' => env('SIMPLE_OTP_FORMAT', 'alpha_numeric'),
    'otp_expiration_minutes' => env('SIMPLE_OTP_EXPIRATION_MINUTES', 5),
    'otp_max_attempts' => env('SIMPLE_OTP_MAX_ATTEMPTS', 3),
    'otp_throw_exceptions' => env('SIMPLE_OTP_THROW_EXCEPTIONS', true),
];
