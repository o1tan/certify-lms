<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QaThreadStatus;
use Database\Factories\QaThreadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaThread extends Model
{
    /** @use HasFactory<QaThreadFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'certification_id',
        'user_id',
        'title',
        'body',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'status' => QaThreadStatus::class,
        'resolved_at' => 'datetime',
    ];

    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(QaReply::class)->oldest();
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('status', QaThreadStatus::Unresolved->value);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', QaThreadStatus::Resolved->value);
    }

    public function scopeForCertification(Builder $query, ?string $certificationId): Builder
    {
        if ($certificationId === null || $certificationId === '') {
            return $query;
        }

        return $query->where('certification_id', $certificationId);
    }

    public function scopeKeyword(Builder $query, ?string $keyword): Builder
    {
        if ($keyword === null || $keyword === '') {
            return $query;
        }

        return $query->where('body', 'LIKE', '%'.$keyword.'%');
    }
}