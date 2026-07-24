<?php

namespace App\Services;

class SettingsOptionsResolver
{
    protected static array $resolvers = [];

    public static function register(string $key, \Closure $resolver): void
    {
        static::$resolvers[$key] = $resolver;
    }

    public static function resolve(string $key): array
    {
        if (! isset(static::$resolvers[$key])) {
            return [];
        }

        return (static::$resolvers[$key])();
    }
}