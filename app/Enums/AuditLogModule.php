<?php

namespace App\Enums;

enum AuditLogModule: string
{
    case Auth = 'auth';
    case Partner = 'partner';
    case Lead = 'lead';
    case Team = 'team';
    case Customer = 'customer';
    case Project = 'project';
    case Financial = 'financial';
    case Agreement = 'agreement';
    case Inactivity = 'inactivity';
    case Settings = 'settings';
    case SystemBackup = 'system_backup';

    public function label(): string
    {
        return match ($this) {
            self::Auth => 'Authentication & Access',
            self::Partner => 'Partner Management',
            self::Lead => 'Lead Management',
            self::Team => 'Staff & Team',
            self::Customer => 'Customer Operations',
            self::Project => 'Project Management',
            self::Financial => 'Financial & Payouts',
            self::Agreement => 'Agreements & Legal',
            self::Inactivity => 'Inactivity Monitoring',
            self::Settings => 'System Settings',
            self::SystemBackup => 'Demo Backup & Cleanup',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Auth => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::Partner => 'bg-purple-50 text-purple-700 border-purple-200',
            self::Lead => 'bg-blue-50 text-blue-700 border-blue-200',
            self::Team => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            self::Customer => 'bg-teal-50 text-teal-700 border-teal-200',
            self::Project => 'bg-sky-50 text-sky-700 border-sky-200',
            self::Financial => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::Agreement => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Inactivity => 'bg-rose-50 text-rose-700 border-rose-200',
            self::Settings => 'bg-slate-100 text-slate-700 border-slate-300',
            self::SystemBackup => 'bg-amber-100 text-amber-800 border-amber-300',
        };
    }
}
