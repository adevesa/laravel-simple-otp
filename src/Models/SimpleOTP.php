<?php

namespace adevesa\SimpleOTP\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
