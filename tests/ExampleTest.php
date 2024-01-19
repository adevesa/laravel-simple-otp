<?php



it('can test', function () {
    expect(true)->toBeTrue();
});

it('otp can be generated', function () {
    $identifier = "agustin@devesa.com";
    $otp = new \adevesa\SimpleOTP\SimpleOTP();
    $otpModel = $otp->create($identifier);

    expect($otpModel->identity)->toBe($identifier);
    expect($otpModel->code)->toBeString()->toHaveLength(6);
    expect($otpModel->validated_at)->toBeNull();
    expect($otpModel->expires_at)->toBeObject();
    expect($otpModel->attempts)->toBe(0);
});

it('otp with 10 length can be generated', function () {
    $identifier = "agustin@devesa.com";
    config(['simple-otp.otp_length' => 10]);
    $otp = new \adevesa\SimpleOTP\SimpleOTP();
    $otpModel = $otp->create($identifier);

    expect($otpModel->identity)->toBe($identifier);
    expect($otpModel->code)->toBeString()->toHaveLength(10);
    expect($otpModel->validated_at)->toBeNull();
    expect($otpModel->expires_at)->toBeObject();
    expect($otpModel->attempts)->toBe(0);
});

it('otp with alphanumeric format can be generated', function () {
    $identifier = "agustin@devesa.com";
    config(['simple-otp.otp_length' => 10, 'simple-otp.otp_format' => 'alpha_numeric']);
    $otp = new \adevesa\SimpleOTP\SimpleOTP();
    $otpModel = $otp->create($identifier);

    expect($otpModel->identity)->toBe($identifier);
    expect($otpModel->code)->toMatch('/^[0-9A-Z]{10}$/');
    expect($otpModel->validated_at)->toBeNull();
    expect($otpModel->expires_at)->toBeObject();
    expect($otpModel->attempts)->toBe(0);
});

it('otp with numeric format can be generated', function () {
    $identifier = "agustin@devesa.com";
    config(['simple-otp.otp_length' => 10, 'simple-otp.otp_format' => 'numeric']);
    $otp = new \adevesa\SimpleOTP\SimpleOTP();
    $otpModel = $otp->create($identifier);

    expect($otpModel->identity)->toBe($identifier);
    expect($otpModel->code)->toMatch('/^[0-9]{10}$/');
    expect($otpModel->validated_at)->toBeNull();
    expect($otpModel->expires_at)->toBeObject();
    expect($otpModel->attempts)->toBe(0);
});

it('otp with a 10 ttl can be generated', function () {
    $identifier = "agustin@devesa.com";
    config(['simple-otp.otp_expiration_minutes' => 10]);
    $otp = new \adevesa\SimpleOTP\SimpleOTP();
    $otpModel = $otp->create($identifier);

    expect($otpModel->expires_at->toString())->toBe($otpModel->created_at->addMinutes(10)->toString());
    $this->assertDatabaseHas('simple_otps', [
        'identity' => $identifier,
        'expires_at' => $otpModel->expires_at->toDateTimeString(),
    ]);
});

it('otp can be validated', function () {
    $identifier = "agustin@devesa.com";
    config(['simple-otp.otp_expiration_minutes' => 10]);
    $otp = new \adevesa\SimpleOTP\SimpleOTP();
    $otpModel = $otp->create($identifier);

    $result = $otp->verify($identifier, $otpModel->code);
    $resultAgain = $otp->verify($identifier, $otpModel->code);

    expect($result)->toBeTrue();
    expect($resultAgain)->toBeFalse();
    $otpModel = $otpModel->refresh();
    expect($otpModel->validated_at)->toBe(now()->toDateTimeString());
    expect($otpModel->attempts)->toBe(0);
});

it('otp wrong count 1 attemp can be validated', function () {
    $identifier = "agustin@devesa.com";
    config(['simple-otp.otp_expiration_minutes' => 10]);
    $otp = new \adevesa\SimpleOTP\SimpleOTP();
    $otpModel = $otp->create($identifier);

    $result = $otp->verify($identifier, 'NOTHING');

    expect($result)->toBeFalse();
    $otpModel = $otpModel->refresh();
    expect($otpModel->validated_at)->toBe(NULL);
    expect($otpModel->attempts)->toBe(1);
});
