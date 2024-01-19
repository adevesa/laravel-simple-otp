<?php

namespace adevesa\SimpleOTP;

class SimpleOTP
{
    protected int $ttl;
    protected int $otpLength;
    protected int $maxAttempts;
    protected bool $alphaNumeric;

    public function __construct()
    {
        $this->ttl = (int) config('simple-otp.otp_expiration_minutes');
        $this->otpLength = (int) config('simple-otp.otp_length');
        $this->maxAttempts = (int) config('simple-otp.otp_max_attempts');
        $this->format = config('simple-otp.otp_format');
    }

    protected function generateCode(): string
    {
        $code = "";

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
        if($lastOtp) {
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

        if (!$otpModel) {
            return false;
        }

        if($otpModel->code !== $code) {
            $otpModel->attempts++;
            $otpModel->save();
            return false;
        }

        if($otpModel->attempts >= $this->maxAttempts) {
            return false;
        }

        if($otpModel->expires_at < now()) {
            return false;
        }



        $otpModel->validated_at = now();
        $otpModel->save();
        return true;
    }
}
