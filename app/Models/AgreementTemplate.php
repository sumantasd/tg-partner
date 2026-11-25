<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_code',
        'title',
        'version',
        'content',
        'status',
        'is_active',
        'effective_from',
        'effective_until',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if template is currently effective.
     */
    public function isCurrentlyEffective(): bool
    {
        if (!$this->is_active || $this->status !== 'active') {
            return false;
        }

        $today = now()->toDateString();
        $starts = $this->effective_from->toDateString();
        $ends = $this->effective_until ? $this->effective_until->toDateString() : null;

        if ($today < $starts) {
            return false;
        }

        if ($ends && $today > $ends) {
            return false;
        }

        return true;
    }

    /**
     * Get active default template.
     */
    public static function getActiveTemplate(): ?self
    {
        return self::where('is_active', true)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhereDate('effective_until', '>=', now()->toDateString());
            })
            ->latest('id')
            ->first();
    }

    /**
     * Render content with replaced placeholders.
     */
    public function render(array $data): string
    {
        $rendered = $this->content;
        foreach ($data as $key => $val) {
            $valStr = (string) $val;
            $rendered = str_ireplace('{{' . $key . '}}', $valStr, $rendered);
            $rendered = str_ireplace('{' . $key . '}', $valStr, $rendered);
            $spacedKey = str_replace('_', ' ', $key);
            if ($spacedKey !== $key) {
                $rendered = str_ireplace('{{' . $spacedKey . '}}', $valStr, $rendered);
                $rendered = str_ireplace('{' . $spacedKey . '}', $valStr, $rendered);
            }
        }
        return $rendered;
    }
}
