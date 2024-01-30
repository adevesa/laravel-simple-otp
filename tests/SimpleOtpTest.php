<?php

use adevesa\SimpleOTP\Exceptions\ExpiratedOtpException;
use adevesa\SimpleOTP\Exceptions\InvalidCodeOtpException;
use adevesa\SimpleOTP\Exceptions\MaxAttemptsValidationOtpException;
use adevesa\SimpleOTP\Exceptions\NotFoundOtpException;
use adevesa\SimpleOTP\SimpleOTP;

test('otp code can be generated', function () {
    $identifier = fake()->email;
    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    expect($otpModel->identity)->toBe($identifier);
    expect($otpModel->code)->toBeString()->toHaveLength(6);
    expect($otpModel->validated_at)->toBeNull();
    expect($otpModel->expires_at)->toBeObject();
    expect($otpModel->attempts)->toBe(0);
});

test('otp code generated if is recreated returns the same', function () {
    $identifier = fake()->email;
    $otp = new SimpleOTP();

    $otp->create($identifier);

    $otpModel = $otp->create($identifier);

    expect($otpModel->identity)->toBe($identifier);
    expect($otpModel->code)->toBeString()->toHaveLength(6);
    expect($otpModel->validated_at)->toBeNull();
    expect($otpModel->expires_at)->toBe($otpModel->created_at->addMinutes(5)->toDateTimeString());
    expect($otpModel->attempts)->toBe(0);
});

test('otp with 10 length can be generated', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_length' => 10]);
    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    expect($otpModel->identity)->toBe($identifier);
    expect($otpModel->code)->toBeString()->toHaveLength(10);
    expect($otpModel->validated_at)->toBeNull();
    expect($otpModel->expires_at)->toBeObject();
    expect($otpModel->attempts)->toBe(0);
});

test('otp with alphanumeric format can be generated', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_length' => 10, 'simple-otp.otp_format' => 'alpha_numeric']);
    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    expect($otpModel->identity)->toBe($identifier);
    expect($otpModel->code)->toMatch('/^[0-9A-Z]{10}$/');
    expect($otpModel->validated_at)->toBeNull();
    expect($otpModel->expires_at)->toBeObject();
    expect($otpModel->attempts)->toBe(0);
});

test('otp with numeric format can be generated', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_length' => 10, 'simple-otp.otp_format' => 'numeric']);
    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    expect($otpModel->identity)->toBe($identifier);
    expect($otpModel->code)->toMatch('/^[0-9]{10}$/');
    expect($otpModel->validated_at)->toBeNull();
    expect($otpModel->expires_at)->toBeObject();
    expect($otpModel->attempts)->toBe(0);
});

test('otp with a 10 ttl can be generated', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_expiration_minutes' => 10]);
    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    expect($otpModel->expires_at->toString())->toBe($otpModel->created_at->addMinutes(10)->toString());
    $this->assertDatabaseHas('simple_otps', [
        'identity' => $identifier,
        'expires_at' => $otpModel->expires_at->toDateTimeString(),
    ]);
});

test('otp can be validated', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_expiration_minutes' => 10]);
    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    $result = $otp->verify($identifier, $otpModel->code);
    $resultAgain = $otp->verify($identifier, $otpModel->code);

    expect($result)->toBeTrue();
    expect($resultAgain)->toBeFalse();
    $otpModel = $otpModel->refresh();
    expect($otpModel->validated_at)->toBe(now()->toDateTimeString());
    expect($otpModel->attempts)->toBe(0);
});

test('otp wrong count 1 attemp can be validated', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_expiration_minutes' => 10]);
    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    $result = $otp->verify($identifier, 'NOTHING');

    expect($result)->toBeFalse();
    $otpModel = $otpModel->refresh();
    expect($otpModel->validated_at)->toBe(null);
    expect($otpModel->attempts)->toBe(1);
});

test('otp wrong throws exception', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_expiration_minutes' => 10, 'simple-otp.otp_throw_exceptions' => 'true']);
    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    $this->expectException(InvalidCodeOtpException::class);
    $otp->verify($identifier, 'NOTHING');
});

test('otp not exists throws exception', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_expiration_minutes' => 10, 'simple-otp.otp_throw_exceptions' => 'true']);
    $otp = new SimpleOTP();

    $this->expectException(NotFoundOtpException::class);
    $otp->verify($identifier, 'NOTHING');
});

test('otp validation get locked after 3 attemps', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_throw_exceptions' => false]);

    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    $otp->verify($identifier, 'NOTHING');
    $otp->verify($identifier, 'NOTHING');
    $otp->verify($identifier, 'NOTHING');

    $result = $otp->verify($identifier, $otpModel->code);

    expect($result)->toBeFalse();
    $otpModel = $otpModel->refresh();
    expect($otpModel->validated_at)->toBe(null);
    expect($otpModel->attempts)->toBe(3);
});

test('otp validation get locked after 3 attemps and throws exception', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_throw_exceptions' => 'true']);
    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    $otpModel->attempts = 3;
    $otpModel->save();

    $this->expectException(MaxAttemptsValidationOtpException::class);
    $result = $otp->verify($identifier, $otpModel->code);
});

test('otp validation is invalid after expiration', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_expiration_minutes' => 1, 'simple-otp.otp_throw_exceptions' => false]);
    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    $otpModel->update(['expires_at' => now()->subMinutes(2)]);
    $result = $otp->verify($identifier, $otpModel->code);

    expect($result)->toBeFalse();
    $otpModel = $otpModel->refresh();
    expect($otpModel->validated_at)->toBe(null);
    expect($otpModel->attempts)->toBe(0);
});

test('otp validation is invalid after expiration and throws exception', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_expiration_minutes' => 1, 'simple-otp.otp_throw_exceptions' => 'true']);
    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    $otpModel->update(['expires_at' => now()->subMinutes(2)]);

    $this->expectException(ExpiratedOtpException::class);

    $otp->verify($identifier, $otpModel->code);
});

test('otp can be created after expiration', function () {
    $identifier = fake()->email;
    config(['simple-otp.otp_expiration_minutes' => 1]);
    $otp = new SimpleOTP();
    $otpModel = $otp->create($identifier);

    $otpModel->update(['expires_at' => now()->subMinutes(2)]);

    $newOtpModel = $otp->create($identifier);

    expect($newOtpModel->id)->not->toBe($otpModel->id);
    expect($newOtpModel->identity)->toBe($identifier);
    expect($newOtpModel->code)->toBeString()->toHaveLength(6);
    expect($newOtpModel->validated_at)->toBeNull();
    expect($newOtpModel->expires_at)->toBeObject();
    expect($newOtpModel->attempts)->toBe(0);
});

test('otp facade can create and validate', function () {
    $identifier = fake()->email;
    $otpModel = \adevesa\SimpleOTP\Facades\SimpleOTP::create($identifier);

    $result = \adevesa\SimpleOTP\Facades\SimpleOTP::verify($identifier, $otpModel->code);

    expect($result)->toBeTrue();
    $otpModel = $otpModel->refresh();
    expect($otpModel->attempts)->toBe(0);
});
