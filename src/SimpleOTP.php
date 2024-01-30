<?php

namespace adevesa\SimpleOTP;

use adevesa\SimpleOTP\Exceptions\ExpiratedOtpException;
use adevesa\SimpleOTP\Exceptions\InvalidCodeOtpException;
use adevesa\SimpleOTP\Exceptions\MaxAttemptsValidationOtpException;
use adevesa\SimpleOTP\Exceptions\NotFoundOtpException;

class SimpleOTP
{
    protected int $ttl;

    protected int $otpLength;

    protected int $maxAttempts;
    private string $format;
    private bool $throwExceptions;

    public function __construct()
    {
        $this->ttl = (int) config('simple-otp.otp_expiration_minutes');
        $this->otpLength = (int) config('simple-otp.otp_length');
        $this->maxAttempts = (int) config('simple-otp.otp_max_attempts');
        $this->format = (string) config('simple-otp.otp_format');
        $this->throwExceptions = config('simple-otp.otp_throw_exceptions') === 'true';
    }

    protected function generateCode(): string
    {
        $code = '';

        $characters = $this->format === 'alpha_numeric' ? '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ' : '0123456789';
        $charactersLength = strlen($characters);
        for ($i = 0; $i < $this->otpLength; $i++) {
            $code .= $characters[rand(0, $charactersLength - 1)];
        }

        return $code;
    }

    public function create(string $identifier): Models\SimpleOTP
    {
        $lastOtp = $this->getLastOtp($identifier);
        if ($lastOtp && $lastOtp->expires_at > now()) {
            return $lastOtp;
        }

        $code = $this->generateCode();

        $otpModel = (new Models\SimpleOTP())->init($identifier, $code);
        $otpModel->setTtl($this->ttl);
        $otpModel->save();

        return $otpModel;
    }

    protected function getLastOtp(string $identifier): ?Models\SimpleOTP
    {
        return Models\SimpleOTP::query()
            ->where('identity', $identifier)
            ->where('validated_at', null)
            ->first();
    }

    public function verify(string $identifier, string $code): bool
    {
        $otpModel = $this->getLastOtp($identifier);

        if (! $otpModel) {
            if($this->throwExceptions) {
                throw new NotFoundOtpException();
            }
            return false;
        }

        if ($otpModel->code !== $code) {
            $otpModel->attempts++;
            $otpModel->save();

            if($this->throwExceptions) {
                throw new InvalidCodeOtpException();
            }
            return false;
        }

        if ($otpModel->attempts >= $this->maxAttempts) {
            if($this->throwExceptions) {
                throw new MaxAttemptsValidationOtpException();
            }
            return false;
        }

        if ($otpModel->expires_at < now()) {
            if($this->throwExceptions) {
                throw new ExpiratedOtpException();
            }
            return false;
        }

        $otpModel->validated_at = now();
        $otpModel->save();

        return true;
    }
}
