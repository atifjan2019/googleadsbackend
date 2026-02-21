<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiCache extends Model
{
    protected $table = 'api_cache';
    protected $fillable = ['cache_key', 'data', 'fetched_at'];

    /**
     * Get cached data. Returns null if no cache exists.
     */
    public static function getCached(string $key): ?array
    {
        $record = self::where('cache_key', $key)->first();
        if (!$record) return null;
        return json_decode($record->data, true);
    }

    /**
     * Store data in cache. Replaces any existing entry for the key.
     */
    public static function setCache(string $key, array $data): void
    {
        self::updateOrCreate(
            ['cache_key' => $key],
            ['data' => json_encode($data), 'fetched_at' => now()]
        );
    }

    /**
     * Check if cache is still fresh (within $minutes).
     */
    public static function isFresh(string $key, int $minutes = 60): bool
    {
        $record = self::where('cache_key', $key)->first();
        if (!$record) return false;
        return $record->fetched_at && now()->diffInMinutes($record->fetched_at) < $minutes;
    }
}
