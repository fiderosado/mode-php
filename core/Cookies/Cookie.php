<?php

namespace Core\Cookies;

class Cookie
{
    public static function request(?array $source = null): Cookies
    {
        return new RequestCookies($source);
    }

    public static function response(): Cookies
    {
        return new ResponseCookies();
    }
}
