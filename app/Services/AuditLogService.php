<?php

namespace App\Services;

use App\Enums\AuditLogAction;
use App\Enums\AuditLogModule;
use App\Exceptions\AuditLogWriteException;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    /**
     * Keys whose values must always be completely redacted.
     */
    protected const REDACT_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'password_hash',
        'remember_token',
        'secret',
        'secret_key',
        'api_secret',
        'token',
        'access_token',
        'auth_token',
        'api_key',
        'otp',
        'otp_code',
        'verification_code',
        'pin',
        'cvv',
        'private_key',
        'document_content',
    ];

    /**
     * Keys whose values should be masked to last 4 characters where possible.
     */
    protected const MASK_KEYS = [
        'bank_account_number',
        'account_number',
        'account_no',
        'bank_account_no',
        'bank_routing_code',
        'routing_code',
        'ifsc',
        'bank_ifsc_code',
        'tax_id',
        'pan',
        'pan_number',
        'pan_card',
        'aadhaar',
        'aadhaar_number',
        'aadhaar_card',
        'upi',
        'upi_id',
        'upi_number',
        'payout_details',
    ];

    /**
     * Record a centralized audit event.
     */
    public function record(
        string|AuditLogAction $action,
        string|AuditLogModule $module,
        string $description,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        bool $mandatory = false
    ): ?AuditLog {
        $actionValue = $action instanceof AuditLogAction ? $action->value : (string) $action;
        $moduleValue = $module instanceof AuditLogModule ? $module->value : (string) $module;

        try {
            $actor = $actor ?? Auth::user();

            $actorName = $actor ? $actor->name : 'System / Automated';
            $actorRole = $actor ? $actor->role->value : 'system';

            $request = request();
            $ip = $ipAddress ?? $request?->ip();
            $ua = $userAgent ?? $request?->userAgent();

            $sanitizedOld = $oldValues !== null ? $this->sanitizeData($oldValues) : null;
            $sanitizedNew = $newValues !== null ? $this->sanitizeData($newValues) : null;
            $sanitizedMeta = $metadata !== null ? $this->sanitizeData($metadata) : null;

            return AuditLog::create([
                'actor_user_id' => $actor?->id,
                'actor_name_snapshot' => $actorName,
                'actor_role_snapshot' => $actorRole,
                'action' => $actionValue,
                'module' => $moduleValue,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'old_values' => $sanitizedOld,
                'new_values' => $sanitizedNew,
                'metadata' => $sanitizedMeta,
                'ip_address' => $ip,
                'user_agent' => $ua ? substr($ua, 0, 500) : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('AuditLogService failed to record audit event: ' . $e->getMessage(), [
                'action' => $actionValue,
                'module' => $moduleValue,
                'exception' => $e,
            ]);

            // Financial & transactional audit consistency policy:
            // If executing within an active database transaction, or recording a financial module action,
            // or explicitly designated mandatory, do NOT suppress the failure.
            // Throw AuditLogWriteException so the database transaction rolls back atomically.
            $isFinancial = ($module instanceof AuditLogModule && $module === AuditLogModule::Financial) || $moduleValue === 'financial';
            $inTransaction = DB::transactionLevel() > 0;

            if ($mandatory || $isFinancial || $inTransaction) {
                if ($e instanceof AuditLogWriteException) {
                    throw $e;
                }
                throw new AuditLogWriteException(
                    "Mandatory audit log insertion failed for [{$actionValue}]: " . $e->getMessage(),
                    previous: $e
                );
            }

            return null;
        }
    }

    /**
     * Record a model update by calculating before/after diffs of changed attributes.
     */
    public function recordModelUpdate(
        Model $model,
        array $oldAttributes,
        string|AuditLogAction $action,
        string|AuditLogModule $module,
        string $description,
        ?array $metadata = null,
        ?User $actor = null,
        bool $mandatory = false
    ): ?AuditLog {
        $newAttributes = $model->getAttributes();
        $changedOld = [];
        $changedNew = [];

        foreach ($newAttributes as $key => $newValue) {
            // Ignore timestamps from diff noise unless explicitly relevant
            if (in_array($key, ['updated_at'])) {
                continue;
            }

            $oldValue = $oldAttributes[$key] ?? null;

            $normalizedOld = $this->normalizeAttributeValue($oldValue);
            $normalizedNew = $this->normalizeAttributeValue($newValue);

            // Accurate comparison avoiding PHP loose inequality traps
            if ($normalizedOld !== $normalizedNew) {
                $changedOld[$key] = $oldValue;
                $changedNew[$key] = $newValue;
            }
        }

        // Check if any old keys were removed
        foreach ($oldAttributes as $key => $oldValue) {
            if (! array_key_exists($key, $newAttributes) && $oldValue !== null) {
                $changedOld[$key] = $oldValue;
                $changedNew[$key] = null;
            }
        }

        // If no attributes actually changed and no metadata, skip logging
        if (empty($changedOld) && empty($changedNew) && empty($metadata)) {
            return null;
        }

        return $this->record(
            action: $action,
            module: $module,
            description: $description,
            subject: $model,
            oldValues: $changedOld,
            newValues: $changedNew,
            metadata: $metadata,
            actor: $actor,
            mandatory: $mandatory
        );
    }

    /**
     * Normalize attribute value for precise comparison.
     */
    protected function normalizeAttributeValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return is_float($value) ? (string) round($value, 4) : (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Recursively sanitize data by redacting secrets and masking sensitive identifiers.
     */
    public function sanitizeData(mixed $data): mixed
    {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $lowerKey = strtolower((string) $key);

                if ($this->shouldRedact($lowerKey)) {
                    $sanitized[$key] = '[REDACTED]';
                } elseif (is_string($value) && $this->isJsonString($value)) {
                    // Recursively sanitize nested JSON string
                    $decoded = json_decode($value, true);
                    $sanitized[$key] = is_array($decoded) ? json_encode($this->sanitizeData($decoded)) : $value;
                } elseif ($this->shouldMask($lowerKey) && is_string($value) && strlen($value) > 0) {
                    $sanitized[$key] = $this->maskValue($value);
                } elseif (is_array($value)) {
                    $sanitized[$key] = $this->sanitizeData($value);
                } else {
                    $sanitized[$key] = $value;
                }
            }
            return $sanitized;
        }

        if (is_string($data) && $this->isJsonString($data)) {
            $decoded = json_decode($data, true);
            if (is_array($decoded)) {
                return json_encode($this->sanitizeData($decoded));
            }
        }

        return $data;
    }

    /**
     * Check if a string is a valid JSON object or array.
     */
    protected function isJsonString(string $string): bool
    {
        $trimmed = trim($string);
        if ((str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) ||
            (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'))) {
            json_decode($trimmed);
            return json_last_error() === JSON_ERROR_NONE;
        }

        return false;
    }

    /**
     * Determine if a field name matches complete redaction criteria.
     */
    protected function shouldRedact(string $key): bool
    {
        foreach (self::REDACT_KEYS as $redactKey) {
            if ($key === $redactKey || str_contains($key, 'password') || str_ends_with($key, '_secret') || str_ends_with($key, '_token')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a field name matches masking criteria.
     */
    protected function shouldMask(string $key): bool
    {
        foreach (self::MASK_KEYS as $maskKey) {
            if ($key === $maskKey) {
                return true;
            }
        }

        if (str_contains($key, 'tax_id') || str_contains($key, 'routing_code') || str_contains($key, 'ifsc') || str_contains($key, 'payout_details') || str_contains($key, 'account_number')) {
            return true;
        }

        if ($key === 'account_no' || str_ends_with($key, '_account_no') || str_starts_with($key, 'account_no_')) {
            return true;
        }

        if ($key === 'upi' || $key === 'upi_id' || str_ends_with($key, '_upi_id') || str_contains($key, 'upi_number')) {
            return true;
        }

        if (str_contains($key, 'document_path') || str_contains($key, 'proof_path') || str_contains($key, 'attachment_path')) {
            return true;
        }

        if (str_starts_with($key, 'pan_') || str_ends_with($key, '_pan') || str_starts_with($key, 'aadhaar_') || str_ends_with($key, '_aadhaar')) {
            return true;
        }

        return false;
    }

    /**
     * Mask string to show only the last 4 characters, or mask file path securely.
     */
    protected function maskValue(string $value): string
    {
        $len = strlen($value);
        if ($len <= 4) {
            return str_repeat('•', $len);
        }

        // If it's a file path, preserve extension and mask the base name
        if (str_contains($value, '/') || str_contains($value, '\\')) {
            $basename = basename(str_replace('\\', '/', $value));
            $ext = pathinfo($basename, PATHINFO_EXTENSION);
            $nameWithoutExt = pathinfo($basename, PATHINFO_FILENAME);
            $maskedName = strlen($nameWithoutExt) <= 4
                ? str_repeat('•', strlen($nameWithoutExt))
                : str_repeat('•', max(4, strlen($nameWithoutExt) - 4)) . substr($nameWithoutExt, -4);
            $dirname = dirname(str_replace('\\', '/', $value));

            return ($dirname !== '.' ? $dirname . '/' : '') . $maskedName . ($ext ? '.' . $ext : '');
        }

        return str_repeat('•', max(4, $len - 4)) . substr($value, -4);
    }
}
