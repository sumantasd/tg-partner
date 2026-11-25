<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\DemoBackup
 *
 * @property int $id
 * @property string $backup_code
 * @property string $name
 * @property string $filename
 * @property string $disk
 * @property string $filepath
 * @property int $file_size
 * @property string|null $sha256_checksum
 * @property int $database_tables_count
 * @property int $files_count
 * @property bool $is_encrypted
 * @property string $encryption_method
 * @property string $status
 * @property string $type
 * @property array<string, mixed>|null $metadata
 * @property string|null $error_message
 * @property int|null $created_by
 * @property Carbon|null $verified_at
 * @property int|null $verified_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $creator
 * @property-read User|null $verifier
 */
class DemoBackup extends Model
{
    use HasFactory;

    protected $table = 'demo_backups';

    protected $fillable = [
        'backup_code',
        'name',
        'filename',
        'disk',
        'filepath',
        'file_size',
        'sha256_checksum',
        'database_tables_count',
        'files_count',
        'is_encrypted',
        'encryption_method',
        'status',
        'type',
        'metadata',
        'error_message',
        'created_by',
        'verified_at',
        'verified_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'database_tables_count' => 'integer',
            'files_count' => 'integer',
            'is_encrypted' => 'boolean',
            'metadata' => 'array',
            'verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified' && $this->verified_at !== null;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'verified'], true);
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function formattedSize(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'verified' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'completed' => 'bg-blue-50 text-blue-700 border-blue-200',
            'in_progress' => 'bg-amber-50 text-amber-700 border-amber-200 animate-pulse',
            'pending' => 'bg-slate-100 text-slate-700 border-slate-300',
            'failed' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            'manual' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'pre_cleanup' => 'bg-purple-50 text-purple-700 border-purple-200',
            'pre_restore' => 'bg-amber-50 text-amber-700 border-amber-200',
            default => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'manual' => 'Manual Backup',
            'pre_cleanup' => 'Pre-Cleanup Safety',
            'pre_restore' => 'Pre-Restore Safety',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
