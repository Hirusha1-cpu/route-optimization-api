<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class AuditLog extends Model
{
    use BelongsToCompany;

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = ['company_id', 'actor_id', 'action', 'subject_type', 'subject_id', 'details'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('audit_logs is append-only: entries cannot be updated.'));
        static::deleting(fn () => throw new RuntimeException('audit_logs is append-only: entries cannot be deleted.'));
    }

    public static function record(string $action, Model $subject, array $details = []): self
    {
        return static::create([
            'company_id' => $subject->company_id ?? Auth::user()?->company_id,
            'actor_id' => Auth::id(),
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'details' => $details,
        ]);
    }
}