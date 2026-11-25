<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get a setting value by key with a fallback.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key, tracking who updated it.
     */
    public static function set(string $key, ?string $value, ?int $updatedBy = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value'      => $value,
                'updated_by' => $updatedBy,
            ]
        );
    }
}
