<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Processing = 'processing';
    case Paid = 'paid';
    case Settled = 'settled'; // Backward-compatible alias for Paid
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::Approved => 'Approved',
            self::Processing => 'Processing',
            self::Paid, self::Settled => 'Paid',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isApproved(): bool
    {
        return $this === self::Approved;
    }

    public function isProcessing(): bool
    {
        return $this === self::Processing;
    }

    public function isPaid(): bool
    {
        return $this === self::Paid || $this === self::Settled;
    }

    public function isRejected(): bool
    {
        return $this === self::Rejected;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Approved => 'bg-tg-blue-light text-tg-blue border-tg-blue-border',
            self::Processing => 'bg-tg-purple-light text-tg-purple border-tg-purple-border',
            self::Paid, self::Settled => 'bg-tg-green-light text-tg-green border-tg-green-border',
            self::Rejected => 'bg-tg-pink-light text-tg-pink border-tg-pink-border',
            self::Cancelled => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }
}
