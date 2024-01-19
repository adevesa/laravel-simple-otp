<?php

namespace adevesa\SimpleOTP\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \adevesa\SimpleOTP\SimpleOTP
 */
class SimpleOTP extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \adevesa\SimpleOTP\SimpleOTP::class;
    }
}
