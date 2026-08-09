<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    use HasFactory;

    protected $table = 'app_settings';

    protected $fillable = ['key', 'value'];

    /**
     * The `value` column stores secrets (API keys, tokens, etc.), so it is
     * encrypted at rest via Laravel's `encrypted` cast. The plaintext value
     * is only ever held in memory while the application is running.
     */
    protected $casts = [
        'value' => 'encrypted',
    ];

    public $timestamps = true;

    /**
     * Cache TTL (seconds) for resolved setting values. Short enough that an
     * update propagated via `forget()` is picked up quickly by queue workers,
     * long enough to avoid hitting the database on every Gemini request.
     */
    public const CACHE_TTL = 60;

    /**
     * Read a setting by key, falling back to the provided default when the
     * row does not exist. Result is cached per-process for CACHE_TTL seconds
     * (and also in the shared cache store so queue workers benefit).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(self::cacheKey($key), self::CACHE_TTL, function () use ($key, $default) {
            $row = static::query()->where('key', $key)->first();
            return $row?->value ?? $default;
        });
    }

    /**
     * Persist a setting value, encrypting it automatically through the
     * `encrypted` cast, and clear the cache so the new value is visible
     * immediately to web request and queue worker processes alike.
     */
    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        self::forget($key);
    }

    /**
     * Forget the cached value for a setting. Called automatically by `set`
     * and may be called manually after bulk edits.
     */
    public static function forget(string $key): void
    {
        Cache::forget(self::cacheKey($key));
    }

    private static function cacheKey(string $key): string
    {
        return 'app_setting:' . $key;
    }
}