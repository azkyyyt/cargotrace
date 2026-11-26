<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'label',
        'description',
    ];

    /**
     * Safely retrieve a configuration value from DB with fallback default.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            if (!Schema::hasTable('settings')) {
                return $default;
            }
            $setting = self::where('key', $key)->first();
            return ($setting && $setting->value !== null && $setting->value !== '') ? $setting->value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Create or update a configuration setting.
     */
    public static function setValue(string $key, mixed $value, ?string $label = null, ?string $description = null): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            array_filter([
                'value' => (string) $value,
                'label' => $label,
                'description' => $description,
            ], fn ($v) => $v !== null)
        );
    }
}
