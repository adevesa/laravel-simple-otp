<?php

namespace adevesa\SimpleOTP\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $identity
 * @property string $code
 * @property Carbon $validated_at
 * @property Carbon $expires_at
 * @property int $attempts
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SimpleOTP extends Model
{
    protected $table = 'simple_otps';

    use HasFactory;

    protected $fillable = [
        'identity',
        'code',
        'validated_at',
        'expires_at',
        'attempts',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function init(string $identifier, string $code): self
    {
        $this->identity = $identifier;
        $this->code = $code;
        $this->attempts = 0;

        return $this;
    }

    public function setTtl(int $ttl)
    {
        $this->expires_at = now()->addMinutes($ttl);
    }
}
